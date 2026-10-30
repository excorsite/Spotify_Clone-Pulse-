<?php
require 'config.php';
require_login();
if (($_SESSION['role'] ?? '') !== 'admin') die('Admin matra upload garna sakchha.');
$msg = '';
$ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $artist = trim($_POST['artist']);
    $audioExt = strtolower(pathinfo($_FILES['song']['name'], PATHINFO_EXTENSION));
    if (!in_array($audioExt, ['mp3', 'wav', 'ogg', 'm4a']) || $title === '' || $artist === '') {
        $msg = 'Title, artist ra valid audio file (mp3/wav/ogg/m4a) chahiyo.';
    } else {
        $songName = uniqid('s_') . '.' . $audioExt;
        move_uploaded_file($_FILES['song']['tmp_name'], "uploads/songs/$songName");
        $coverName = null;
        if (!empty($_FILES['cover']['name'])) {
            $ce = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            if (in_array($ce, ['jpg', 'jpeg', 'png', 'webp'])) {
                $coverName = uniqid('c_') . '.' . $ce;
                move_uploaded_file($_FILES['cover']['tmp_name'], "uploads/covers/$coverName");
            }
        }
        $st = $conn->prepare("INSERT INTO songs(title,artist,cover,file,source) VALUES(?,?,?,?,'local')");
        $st->bind_param('ssss', $title, $artist, $coverName, $songName);
        $st->execute();
        $msg = 'Song upload bhayo ✅';
        $ok = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Upload — Pulse</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="auth"><?php include 'bg.php'; ?>
    <form method="post" enctype="multipart/form-data" class="box glass">
        <div class="logo"><span class="dot"></span>Pulse</div>
        <h1>Upload song</h1>
        <?php if ($msg) echo '<p class="' . ($ok ? 'ok' : 'err') . '">' . h($msg) . '</p>'; ?>
        <input name="title" placeholder="Song title" required>
        <input name="artist" placeholder="Artist" required>
        <label>Audio file (mp3, wav, ogg, m4a)</label><input type="file" name="song" accept="audio/*" required>
        <label>Cover image (optional)</label><input type="file" name="cover" accept="image/*">
        <button class="btn">Upload</button>
        <p class="sub"><a href="index.php">← Back to player</a></p>
    </form>
</body>

</html>