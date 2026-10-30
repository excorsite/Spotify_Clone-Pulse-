<?php
require 'config.php';
header('Content-Type: application/json');
if (empty($_SESSION['uid'])) {
    http_response_code(401);
    echo '[]';
    exit;
}
$uid = (int)$_SESSION['uid'];
$a = $_GET['a'] ?? '';
track_seen($conn, $uid);                       // har request ma 'last seen' update

// ---- online music (Audius public API, key chahidaina) ----
function http_get($url)
{
    // SSL verify off: XAMPP ma CA bundle hudaina, local development ko lagi matra
    if (!function_exists('curl_init')) {
        $c = stream_context_create(['http' => ['timeout' => 15, 'header' => "User-Agent: MusicClone\r\n"], 'ssl' => ['verify_peer' => false]]);
        return @file_get_contents($url, false, $c);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'MusicClone',
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}
function import_audius($q)
{
    global $conn;
    $base = 'https://api.audius.co/v1/tracks/';
    $url = $q !== '' ? $base . 'search?query=' . urlencode($q) . '&limit=25&app_name=musicclone'
        : $base . 'trending?limit=25&app_name=musicclone';
    $j = json_decode(http_get($url), true);
    if (empty($j['data'])) return false;
    $st = $conn->prepare("INSERT IGNORE INTO songs(title,artist,cover,file,source,ext_id) VALUES(?,?,?,?,'audius',?)");
    $ids = [];
    foreach ($j['data'] as $t) {
        if (isset($t['is_streamable']) && !$t['is_streamable']) continue;
        $id = (string)$t['id'];
        $title = $t['title'];
        $artist = $t['user']['name'] ?? 'Unknown';
        $cover = $t['artwork']['480x480'] ?? null;
        $file = $base . $id . '/stream?app_name=musicclone';
        $st->bind_param('sssss', $title, $artist, $cover, $file, $id);
        $st->execute();
        $ids[] = $id;
    }
    return $ids;
}
function song_query($where)
{
    global $conn, $uid;
    $sql = "SELECT s.id,s.title,s.artist,s.cover,s.file,(l.user_id IS NOT NULL) AS liked
            FROM songs s LEFT JOIN likes l ON l.song_id=s.id AND l.user_id=$uid
            WHERE $where ORDER BY s.id DESC";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

switch ($a) {
    case 'songs':
        $where = '1';
        $q = trim($_GET['q'] ?? '');
        $view = $_GET['view'] ?? 'all';
        // pahilo choti kei song chhaina bhane trending songs afai aaunchha
        if ($view === 'all' && $q === '' && $conn->query('SELECT 1 FROM songs LIMIT 1')->num_rows == 0) import_audius('');
        if ($q !== '') {
            $q = $conn->real_escape_string($q);
            $where .= " AND (s.title LIKE '%$q%' OR s.artist LIKE '%$q%')";
        }
        if ($view === 'liked') $where .= ' AND l.user_id IS NOT NULL';
        if ($view === 'pl') {
            $pid = (int)($_GET['id'] ?? 0);
            $where .= " AND s.id IN (SELECT ps.song_id FROM playlist_songs ps JOIN playlists p ON p.id=ps.playlist_id WHERE p.id=$pid AND p.user_id=$uid)";
        }
        echo json_encode(song_query($where));
        break;

    case 'discover':
        $ids = import_audius(trim($_GET['q'] ?? ''));
        if (!$ids) {
            echo json_encode(['error' => 'Online songs aauna sakena. Internet check garnus, ya pachhi feri try garnus.']);
            break;
        }
        $in = "'" . implode("','", array_map([$conn, 'real_escape_string'], $ids)) . "'";
        echo json_encode(song_query("s.ext_id IN ($in)"));
        break;

    case 'like':
        $sid = (int)($_POST['song_id'] ?? 0);
        if ($conn->query("SELECT 1 FROM likes WHERE user_id=$uid AND song_id=$sid")->num_rows) {
            $conn->query("DELETE FROM likes WHERE user_id=$uid AND song_id=$sid");
            echo '{"liked":0}';
        } else {
            $conn->query("INSERT IGNORE INTO likes(user_id,song_id) VALUES($uid,$sid)");
            echo '{"liked":1}';
        }
        break;

    case 'playlists':
        echo json_encode($conn->query("SELECT id,name FROM playlists WHERE user_id=$uid ORDER BY id")->fetch_all(MYSQLI_ASSOC));
        break;

    case 'pl_create':
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            echo '{"ok":0}';
            break;
        }
        $st = $conn->prepare('INSERT INTO playlists(user_id,name) VALUES(?,?)');
        $st->bind_param('is', $uid, $name);
        $st->execute();
        echo '{"ok":1}';
        break;

    case 'pl_add':
    case 'pl_remove':
        $pid = (int)($_POST['playlist_id'] ?? 0);
        $sid = (int)($_POST['song_id'] ?? 0);
        if ($conn->query("SELECT 1 FROM playlists WHERE id=$pid AND user_id=$uid")->num_rows) {
            $conn->query($a === 'pl_add' ? "INSERT IGNORE INTO playlist_songs VALUES($pid,$sid)"
                : "DELETE FROM playlist_songs WHERE playlist_id=$pid AND song_id=$sid");
            echo '{"ok":1}';
        } else echo '{"ok":0}';
        break;

    case 'pl_delete':
        $pid = (int)($_POST['playlist_id'] ?? 0);
        $conn->query("DELETE FROM playlists WHERE id=$pid AND user_id=$uid");
        echo '{"ok":1}';
        break;

    case 'top':
        require_once 'algorithms.php';
        echo json_encode(top_k_songs($conn, $uid, 5));
        break;

    case 'recommend':
        require_once 'algorithms.php';
        echo json_encode(recommend_songs($conn, $uid, 12));
        break;

    case 'ping':
        echo '{"ok":1}';
        break;

    case 'played':
        track_play($conn, $uid, (int)($_POST['song_id'] ?? 0));
        echo '{"ok":1}';
        break;

    default:
        echo '[]';
}
