<?php
// Yo page admin account nabhayeko bela matra kaam garchha. Ek choti admin bhaepachhi afai lock hunchha.
require 'config.php';
$has = (int)$conn->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$err = '';
if (!$has && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username']);
  $e = trim($_POST['email']);
  $p = $_POST['password'];
  if (strlen($u) < 3 || !filter_var($e, FILTER_VALIDATE_EMAIL) || strlen($p) < 8) {
    $err = 'Username 3+, valid email, password 8+ character hunu parchha.';
  } else {
    $st = $conn->prepare('SELECT id FROM users WHERE username=? OR email=?');
    $st->bind_param('ss', $u, $e);
    $st->execute();
    if ($st->get_result()->num_rows) {
      $err = 'Username ya email already chha.';
    } else {
      $hash = password_hash($p, PASSWORD_DEFAULT);
      $st = $conn->prepare("INSERT INTO users(username,email,password,role) VALUES(?,?,?,'admin')");
      $st->bind_param('sss', $u, $e, $hash);
      $st->execute();
      header('Location: admin-login.php?created=1');
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
  <title>Create admin — Pulse</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="auth admin-auth"><?php include 'bg.php'; ?>
  <form method="post" class="box glass">
    <div class="logo"><span class="dot"></span>Pulse <small>&nbsp;Setup</small></div>
    <?php if ($has) { ?>
      <h1>Already set up</h1>
      <p class="sub">Admin account pahila nai chha, tesaile yo page lock bhayo.</p>
      <p class="sub"><a href="admin-login.php">→ Admin sign in</a></p>
    <?php } else { ?>
      <h1>Create admin</h1>
      <p class="sub">Pahilo admin account yaha bata banaunus.</p>
      <?php if ($err) echo '<p class="err">' . h($err) . '</p>'; ?>
      <input name="username" placeholder="Admin username" required autofocus>
      <input name="email" type="email" placeholder="Admin email" required>
      <input name="password" type="password" placeholder="Password (8+ characters)" required>
      <button class="btn">Create admin</button>
    <?php } ?>
  </form>
</body>

</html>