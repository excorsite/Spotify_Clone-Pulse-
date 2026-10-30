<?php
require 'config.php';
require_login();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$added = 0;
$alreadyAdded = 0;
$scanned = 0;
$error = '';
$scanComplete = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf'] ?? '';
    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf'], $submittedToken)) {
        $error = 'Request verify garna milena. Page refresh garera feri try garnuhos.';
    } else {
        $songsDirectory = __DIR__ . '/uploads/songs';
        if (!is_dir($songsDirectory)) {
            $error = 'uploads/songs folder bhetiyena. Pahila yo folder banaunuhos.';
        } else {
            $files = glob($songsDirectory . '/*.{mp3,wav,ogg,m4a}', GLOB_BRACE);
            if ($files === false) {
                $error = 'Folder scan garna sakiyena. Folder permission check garnuhos.';
            } else {
                $scanComplete = true;
                $exists = $conn->prepare('SELECT 1 FROM songs WHERE file=? LIMIT 1');
                $insert = $conn->prepare("INSERT INTO songs(title,artist,file,source) VALUES(?,?,?,'local')");

                foreach ($files as $path) {
                    $scanned++;
                    $file = basename($path);
                    $exists->bind_param('s', $file);
                    $exists->execute();

                    if ($exists->get_result()->num_rows > 0) {
                        $alreadyAdded++;
                        continue;
                    }

                    $name = pathinfo($file, PATHINFO_FILENAME);
                    $parts = explode(' - ', $name, 2);
                    $artist = count($parts) === 2 ? trim($parts[0]) : 'Unknown';
                    $title = count($parts) === 2 ? trim($parts[1]) : $name;
                    $artist = $artist !== '' ? $artist : 'Unknown';
                    $title = $title !== '' ? $title : $name;

                    $insert->bind_param('sss', $title, $artist, $file);
                    $insert->execute();
                    $added++;
                }

                $exists->close();
                $insert->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Scan local songs — Pulse</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="auth"><?php include 'bg.php'; ?>
    <main class="box scan-box glass">
        <div class="logo"><span class="dot"></span>Pulse</div>
        <span class="tag">ADMIN TOOL</span>
        <h1>Add songs from folder</h1>
        <p class="sub">Yo tool le server ko local music folder scan garera naya audio haru player ko song list ma add garchha. File upload gardaina.</p>

        <section class="scan-guide" aria-labelledby="scan-guide-title">
            <h2 id="scan-guide-title">Kasari use garne?</h2>
            <ol>
                <li>Audio files <code>uploads/songs/</code> folder bhitra rakhnu.</li>
                <li>Artist ra title chinna file ko naam <code>Artist - Title.mp3</code> format ma rakhnu.</li>
                <li><code>Scan folder</code> button thichera naya songs list ma halnu.</li>
            </ol>
            <p class="scan-example">Example: <code>Sugam Pokhrel - Aama.mp3</code></p>
            <p class="scan-note">mp3, wav, ogg ra m4a file support hunchha. Artist ko naam chhaina bhane “Unknown” dekhauchha. Pahile nai list ma bhayeko file feri add hudaina.</p>
        </section>

        <?php if ($error !== '') { ?>
            <p class="err scan-message" role="alert"><?= h($error) ?></p>
        <?php } elseif ($scanComplete) { ?>
            <div class="scan-result" role="status">
                <b>Scan complete</b>
                <span><?= $scanned ?> audio file bhetiyo.</span>
                <span><?= $added ?> naya song add bhayo; <?= $alreadyAdded ?> pahile nai list ma thiyo.</span>
            </div>
        <?php } ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
            <button class="btn" type="submit">Scan folder</button>
        </form>
        <p class="scan-links"><a href="upload.php">Euta song upload garne</a><a href="index.php">← Back to player</a></p>
    </main>
</body>

</html>
