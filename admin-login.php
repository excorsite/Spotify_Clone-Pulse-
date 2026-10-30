<?php
require 'config.php';
if (($_SESSION['role'] ?? '') === 'admin' && !empty($_SESSION['uid'])) {
  header('Location: admin.php');
  exit;
}
$err = '';
if ($w = throttle_locked()) {
  $err = "Dherai choti galat bhayo. $w second pachhi feri try garnus.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username']);
  $st = $conn->prepare("SELECT id,password FROM users WHERE (username=? OR email=?) AND role='admin'");
  $st->bind_param('ss', $u, $u);
  $st->execute();
  $r = $st->get_result()->fetch_assoc();
  if ($r && password_verify($_POST['password'], $r['password'])) {
    session_regenerate_id(true);
    throttle_reset();
    $_SESSION['uid'] = $r['id'];
    $_SESSION['role'] = 'admin';
    track_login($conn, (int)$r['id']);
    header('Location: admin.php');
    exit;
  }
  throttle_fail();
  $err = 'Admin username/email ya password milena.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin sign in — Pulse</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="split admin-auth"><?php include 'bg.php'; ?>
  <div class="wrap">
    <?php include 'hero.php'; ?>
    <form method="post" class="panel glass admin-login-panel">
      <span class="tag"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12 2l8 3v6c0 5-3.5 9-8 11-4.5-2-8-6-8-11V5z" />
        </svg> Restricted area</span>
      <h1>Admin sign in</h1>
      <p class="sub">Sign in to manage your music library and dashboard.</p>
      <?php if (isset($_GET['created'])) echo '<p class="ok">Admin account bhayo ✓ ahile login garnus.</p>';
      if ($err) echo '<p class="err" role="alert">' . h($err) . '</p>'; ?>
      <label for="username">Admin username or email</label>
      <input id="username" name="username" type="text" autocomplete="username" placeholder="you@example.com" required autofocus>
      <label for="pw">Password</label>
      <div class="field">
        <input id="pw" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required>
        <button type="button" class="eye" aria-label="Show password" aria-pressed="false" onclick="var p=document.getElementById('pw');var show=p.type==='password';p.type=show?'text':'password';this.textContent=show?'Hide':'Show';this.setAttribute('aria-pressed',show)">Show</button>
      </div>
      <button class="btn wide" type="submit">Sign in to dashboard</button>
      <p class="sub center"><a href="login.php">← Listener login</a></p>
    </form>
  </div>
</body>

</html>