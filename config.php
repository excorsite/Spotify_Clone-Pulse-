<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'spotify_clone'); // XAMPP default
if ($conn->connect_error) die('Database connect bhayena: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');

// user_activity table afai banchha (database ma kei import garnu pardaina)
if (empty($_SESSION['act_tbl'])) {
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS user_activity(
            user_id INT PRIMARY KEY, last_login DATETIME NULL, last_seen DATETIME NULL, logged_in TINYINT(1) DEFAULT 0,
            logins INT DEFAULT 0, plays INT DEFAULT 0, last_song_id INT NULL, last_played_at DATETIME NULL,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(last_song_id) REFERENCES songs(id) ON DELETE SET NULL)");
        $_SESSION['act_tbl'] = 1;
    } catch (Throwable $e) {
    }
}
function require_login()
{
    if (empty($_SESSION['uid'])) {
        header('Location: login.php');
        exit;
    }
}
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ---- brute-force rokne: 5 choti galat bhaye 60 second lock ----
function throttle_locked()
{
    return ($_SESSION['lock_until'] ?? 0) > time() ? $_SESSION['lock_until'] - time() : 0;
}
function throttle_fail()
{
    $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
    if ($_SESSION['fails'] >= 5) {
        $_SESSION['lock_until'] = time() + 60;
        $_SESSION['fails'] = 0;
    }
    usleep(500000);
}
function throttle_reset()
{
    unset($_SESSION['fails'], $_SESSION['lock_until']);
}

// ---- activity tracking (admin dashboard ma dekhinchha) ----
function track_login(mysqli $conn, int $uid)
{
    try {
        $st = $conn->prepare("INSERT INTO user_activity(user_id,last_login,last_seen,logged_in,logins) VALUES(?,NOW(),NOW(),1,1)
            ON DUPLICATE KEY UPDATE last_login=NOW(), last_seen=NOW(), logged_in=1, logins=logins+1");
        $st->bind_param('i', $uid);
        $st->execute();
    } catch (Throwable $e) {
    }
}
function track_seen(mysqli $conn, int $uid)
{
    try {
        $st = $conn->prepare("INSERT INTO user_activity(user_id,last_seen,logged_in) VALUES(?,NOW(),1)
            ON DUPLICATE KEY UPDATE last_seen=NOW(), logged_in=1");
        $st->bind_param('i', $uid);
        $st->execute();
    } catch (Throwable $e) {
    }
}
function track_play(mysqli $conn, int $uid, int $sid)
{
    try {
        $st = $conn->prepare("INSERT INTO user_activity(user_id,last_seen,logged_in,plays,last_song_id,last_played_at) VALUES(?,NOW(),1,1,?,NOW())
            ON DUPLICATE KEY UPDATE last_seen=NOW(), logged_in=1, plays=plays+1, last_song_id=VALUES(last_song_id), last_played_at=NOW()");
        $st->bind_param('ii', $uid, $sid);
        $st->execute();
    } catch (Throwable $e) {
    }
}
function track_logout(mysqli $conn, int $uid)
{
    try {
        $conn->query("UPDATE user_activity SET logged_in=0 WHERE user_id=" . (int)$uid);
    } catch (Throwable $e) {
    }
}

// user ko naam ko pahilo akshyar (mbstring extension bina pani kaam garchha)
function initial_char($s)
{
    return preg_match('/./u', (string)$s, $m) ? strtoupper($m[0]) : '?';
}
