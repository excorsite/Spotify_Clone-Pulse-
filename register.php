<?php
require 'config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username']);
  $e = trim($_POST['email']);
  $p = $_POST['password'];
  if (strlen($u) < 3 || !filter_var($e, FILTER_VALIDATE_EMAIL) || strlen($p) < 6) {
    $err = 'Username 3+, valid email, password 6+ character hunu parchha.';
  } else {
    $st = $conn->prepare('SELECT id FROM users WHERE username=? OR email=?');
    $st->bind_param('ss', $u, $e);
    $st->execute();
    if ($st->get_result()->num_rows) {
      $err = 'Username ya email already chha.';
    } else {
      $role = 'user'; // register bata hamesha normal user matra banchha
      $hash = password_hash($p, PASSWORD_DEFAULT);
      $st = $conn->prepare('INSERT INTO users(username,email,password,role) VALUES(?,?,?,?)');
      $st->bind_param('ssss', $u, $e, $hash, $role);
      $st->execute();
      header('Location: login.php?registered=1');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign up — Pulse</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="split"><?php include 'bg.php'; ?>
  <div class="wrap">
    <?php include 'hero.php'; ?>
    <form method="post" class="panel glass">
      <h1>Create account</h1>
      <p class="sub">Free ma start garnus, 1 minute lagchha.</p>
      <?php if ($err) echo '<p class="err">' . h($err) . '</p>'; ?>
      <label>Username</label><input name="username" placeholder="yourname" required autofocus>
      <label>Email</label><input name="email" type="email" placeholder="you@example.com" required>
      <label>Password</label>
      <div class="field"><input name="password" id="pw" type="password" placeholder="At least 6 characters" required>
        <button type="button" class="eye" onclick="var p=document.getElementById('pw');p.type=p.type=='password'?'text':'password'">Show</button>
      </div>
      <button class="btn wide">Sign up</button>
      <p class="sub center">Already have an account? <a href="login.php">Log in</a></p>
    </form>
  </div>
</body>

</html>