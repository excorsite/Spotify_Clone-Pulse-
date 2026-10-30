<?php
require 'config.php';
if (empty($_SESSION['uid']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: admin-login.php');
  exit;
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

// user delete (admin le normal user matra hataunna sakchha)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
  $uid = (int)($_POST['uid'] ?? 0);
  if ($uid !== (int)$_SESSION['uid']) {
    $st = $conn->prepare("DELETE FROM users WHERE id=? AND role='user'");
    $st->bind_param('i', $uid);
    $st->execute();
  }
  header('Location: admin.php?deleted=1');
  exit;
}
function ago($s)
{
  if ($s === null) return '—';
  $s = (int)$s;
  if ($s < 60) return 'just now';
  if ($s < 3600) return floor($s / 60) . ' min ago';
  if ($s < 86400) return floor($s / 3600) . ' hr ago';
  return floor($s / 86400) . ' day ago';
}
$n = fn($sql) => (int)$conn->query($sql)->fetch_assoc()['c'];
$totalUsers  = $n('SELECT COUNT(*) c FROM users');
$admins      = $n("SELECT COUNT(*) c FROM users WHERE role='admin'");
$songsAll    = $n('SELECT COUNT(*) c FROM songs');
$songsLocal  = $n("SELECT COUNT(*) c FROM songs WHERE source='local'");
$likes       = $n('SELECT COUNT(*) c FROM likes');
$playlists   = $n('SELECT COUNT(*) c FROM playlists');
$users = $conn->query("SELECT u.id,u.username,u.email,u.role,
    (SELECT COUNT(*) FROM likes l WHERE l.user_id=u.id) likes,
    (SELECT COUNT(*) FROM playlists p WHERE p.user_id=u.id) pls,
    a.logins, a.plays, a.logged_in,
    TIMESTAMPDIFF(SECOND,a.last_seen,NOW()) seen_s,
    TIMESTAMPDIFF(SECOND,a.last_played_at,NOW()) played_s,
    DATE_FORMAT(a.last_login,'%d %b %Y, %H:%i') last_login_at,
    s.title song_title, s.artist song_artist
    FROM users u
    LEFT JOIN user_activity a ON a.user_id=u.id
    LEFT JOIN songs s ON s.id=a.last_song_id
    ORDER BY (a.logged_in=1 AND TIMESTAMPDIFF(SECOND,a.last_seen,NOW())<=120) DESC, a.last_seen DESC, u.id DESC")->fetch_all(MYSQLI_ASSOC);
// online = logout gareko chhaina ra pichhillo 2 minute bhitra active
$isOn = fn($u) => !empty($u['logged_in']) && $u['seen_s'] !== null && (int)$u['seen_s'] <= 120;
$online = array_values(array_filter($users, $isOn));
require_once 'algorithms.php';
$top = top_k_songs($conn, (int)$_SESSION['uid'], 5);   // Min-Heap Top-K
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard — Pulse</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="page"><?php include 'bg.php'; ?>
  <div class="dwrap">
    <header class="dtop glass">
      <div class="logo"><span class="dot"></span>Pulse <small>&nbsp;Admin</small></div>
      <div style="display:flex;gap:10px"><a class="btn ghost" href="index.php">Open player</a><a class="btn ghost" href="logout.php">Log out</a></div>
    </header>

    <section class="stats">
      <div class="stat glass hot"><span>Total users</span><b><?= $totalUsers ?></b><small><?= $admins ?> admin · <?= $totalUsers - $admins ?> listeners</small></div>
      <div class="stat glass"><span><i class="live"></i> Online now</span><b><?= count($online) ?></b><small>pichhillo 2 min bhitra active</small></div>
      <div class="stat glass"><span>Total songs</span><b><?= $songsAll ?></b><small><?= $songsLocal ?> uploaded · <?= $songsAll - $songsLocal ?> online</small></div>
      <div class="stat glass"><span>Total likes</span><b><?= $likes ?></b><small>sabai user ko</small></div>
      <div class="stat glass"><span>Playlists</span><b><?= $playlists ?></b><small>banaieka</small></div>
    </section>

    <section class="glass tbl">
      <div class="tblhead">
        <h2>Online now</h2><small>Hal app chalairaheka users</small>
      </div>
      <?php if (!$online) {
        echo '<p class="sub">Hal kunai user online chhaina.</p>';
      } else { ?>
        <div class="online-grid">
          <?php foreach ($online as $u) { ?>
            <div class="ucard">
              <span class="av"><?= h(initial_char($u['username'])) ?></span>
              <div class="ubody"><b><?= h($u['username']) ?></b> <span class="badge <?= h($u['role']) ?>"><?= h($u['role']) ?></span><br>
                <small><?= $u['song_title'] ? 'Playing: ' . h($u['song_title']) . ' — ' . h($u['song_artist']) . ' · ' . ago($u['played_s']) : 'Kunai song bajayeko chhaina' ?></small>
              </div>
              <i class="live"></i>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    </section>

    <section class="glass tbl">
      <div class="tblhead">
        <h2>Top 5 most liked songs</h2><small>Min-Heap (Top-K algorithm)</small>
      </div>
      <?php if (!$top) {
        echo '<p class="sub">Abhai kunai song like bhayeko chhaina.</p>';
      } else { ?>
        <div class="scroll">
          <table>
            <tr>
              <th>Rank</th>
              <th>Song</th>
              <th>Artist</th>
              <th>Likes</th>
            </tr>
            <?php foreach ($top as $i => $t) { ?>
              <tr>
                <td>#<?= $i + 1 ?></td>
                <td><?= h($t['title']) ?></td>
                <td><?= h($t['artist']) ?></td>
                <td><?= (int)$t['likes'] ?></td>
              </tr>
            <?php } ?>
          </table>
        </div>
      <?php } ?>
    </section>

    <section class="glass tbl">
      <div class="tblhead">
        <h2>All users</h2>
        <?php if (isset($_GET['deleted'])) echo '<span class="ok">User hatayo ✓</span>'; ?>
        <label class="auto"><input type="checkbox" id="auto" checked> Auto-refresh (30s)</label>
        <input id="uq" placeholder="Search user...">
      </div>
      <div class="scroll">
        <table id="ut">
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last login</th>
            <th>Logins</th>
            <th>Songs played</th>
            <th>Last played</th>
            <th>Liked</th>
            <th>Playlists</th>
            <th></th>
          </tr>
          <?php foreach ($users as $u) {
            $on = $isOn($u); ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><span class="av sm"><?= h(initial_char($u['username'])) ?></span> <?= h($u['username']) ?></td>
              <td><?= h($u['email']) ?></td>
              <td><span class="badge <?= h($u['role']) ?>"><?= h($u['role']) ?></span></td>
              <td><span class="status"><i class="<?= $on ? 'live' : 'off' ?>"></i><?= $on ? 'Online' : 'Seen ' . ago($u['seen_s']) ?></span></td>
              <td><?= $u['last_login_at'] ? h($u['last_login_at']) : '—' ?></td>
              <td><?= (int)$u['logins'] ?></td>
              <td><?= (int)$u['plays'] ?></td>
              <td><?= $u['song_title'] ? h($u['song_title']) . ' <small>— ' . h($u['song_artist']) . '</small>' : '—' ?></td>
              <td><?= (int)$u['likes'] ?></td>
              <td><?= (int)$u['pls'] ?></td>
              <td><?php if ($u['role'] === 'user') { ?>
                  <form method="post" onsubmit="return confirm('Yo user lai hataune?')">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                    <button class="btn danger">Remove</button>
                  </form>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </table>
      </div>
    </section>
  </div>
  <script>
    const uq = document.getElementById('uq');
    uq.oninput = e => {
      const q = e.target.value.toLowerCase();
      document.querySelectorAll('#ut tr:not(:first-child)').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none');
    };
    setInterval(() => {
      if (document.getElementById('auto').checked && !uq.value) location.reload();
    }, 30000);
  </script>
</body>

</html>