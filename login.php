<?php
require 'config.php';
$err = '';
if ($w = throttle_locked()) {
    $err = "Dherai choti galat bhayo. $w second pachhi feri try garnus.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username']);
    // yo page bata normal user matra login hunchha. Admin ko lagi admin-login.php chha.
    $st = $conn->prepare("SELECT id,password,role FROM users WHERE (username=? OR email=?) AND role='user'");
    $st->bind_param('ss', $u, $u); $st->execute();
    $r = $st->get_result()->fetch_assoc();
    if ($r && password_verify($_POST['password'], $r['password'])) {
        session_regenerate_id(true); throttle_reset();
        $_SESSION['uid'] = $r['id']; $_SESSION['role'] = 'user';
        track_login($conn, (int)$r['id']);
        header('Location: index.php'); exit;
    }
    throttle_fail();
    $err = 'Username/email ya password milena.';
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log in — Pulse</title><link rel="stylesheet" href="css/style.css"></head>
<body class="split"><?php include 'bg.php'; ?>
<div class="wrap">
  <?php include 'hero.php'; ?>
  <form method="post" class="panel glass">
    <h1>Welcome back</h1>
    <p class="sub">Log in to continue listening.</p>
    <?php if (isset($_GET['registered'])) echo '<p class="ok">Account bhayo ✓ ahile login garnus.</p>';
    if ($err) echo '<p class="err">' . h($err) . '</p>'; ?>
    <label>Username or email</label>
    <input name="username" placeholder="you@example.com" required autofocus>
    <label>Password</label>
    <div class="field"><input name="password" id="pw" type="password" placeholder="••••••••" required>
      <button type="button" class="eye" onclick="var p=document.getElementById('pw');p.type=p.type=='password'?'text':'password'">Show</button></div>
    <button class="btn wide">Log in</button>
    <p class="sub center">New here? <a href="register.php">Create an account</a></p>
  </form>
</div></body></html>
