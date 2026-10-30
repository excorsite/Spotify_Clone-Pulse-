<?php
require 'config.php';
require_login();
$st = $conn->prepare('SELECT username FROM users WHERE id=?');
$st->bind_param('i', $_SESSION['uid']);
$st->execute();
$uname = $st->get_result()->fetch_assoc()['username'] ?? 'User';
$totalUsers = $_SESSION['role'] === 'admin' ? (int)$conn->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'] : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pulse — Music Player</title>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <?php include 'bg.php'; ?>
  <svg width="0" height="0" style="position:absolute">
    <defs>
      <symbol id="i-home" viewBox="0 0 24 24">
        <path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z" />
      </symbol>
      <symbol id="i-search" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="7" />
        <line x1="21" y1="21" x2="16.5" y2="16.5" />
      </symbol>
      <symbol id="i-library" viewBox="0 0 24 24">
        <path d="M4 4v16M9 4v16M14 5l5 15" />
      </symbol>
      <symbol id="i-play" viewBox="0 0 24 24">
        <path d="M7 4l13 8-13 8z" />
      </symbol>
      <symbol id="i-pause" viewBox="0 0 24 24">
        <rect x="6" y="4" width="4" height="16" rx="1" />
        <rect x="14" y="4" width="4" height="16" rx="1" />
      </symbol>
      <symbol id="i-prev" viewBox="0 0 24 24">
        <path d="M19 5L9 12l10 7z" />
        <rect x="5" y="5" width="2.5" height="14" rx="1" />
      </symbol>
      <symbol id="i-next" viewBox="0 0 24 24">
        <path d="M5 5l10 7-10 7z" />
        <rect x="16.5" y="5" width="2.5" height="14" rx="1" />
      </symbol>
      <symbol id="i-shuffle" viewBox="0 0 24 24">
        <polyline points="16 3 21 3 21 8" />
        <line x1="4" y1="20" x2="21" y2="3" />
        <polyline points="21 16 21 21 16 21" />
        <line x1="15" y1="15" x2="21" y2="21" />
        <line x1="4" y1="4" x2="9" y2="9" />
      </symbol>
      <symbol id="i-repeat" viewBox="0 0 24 24">
        <polyline points="17 1 21 5 17 9" />
        <path d="M3 11V9a4 4 0 0 1 4-4h14" />
        <polyline points="7 23 3 19 7 15" />
        <path d="M21 13v2a4 4 0 0 1-4 4H3" />
      </symbol>
      <symbol id="i-repeat1" viewBox="0 0 24 24">
        <polyline points="17 1 21 5 17 9" />
        <path d="M3 11V9a4 4 0 0 1 4-4h14" />
        <polyline points="7 23 3 19 7 15" />
        <path d="M21 13v2a4 4 0 0 1-4 4H3" />
        <path d="M11 10l1.5-1v6" />
      </symbol>
      <symbol id="i-heart" viewBox="0 0 24 24">
        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z" />
      </symbol>
      <symbol id="i-volume" viewBox="0 0 24 24">
        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" />
        <path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14" />
      </symbol>
      <symbol id="i-plus" viewBox="0 0 24 24">
        <line x1="12" y1="5" x2="12" y2="19" />
        <line x1="5" y1="12" x2="19" y2="12" />
      </symbol>
      <symbol id="i-trash" viewBox="0 0 24 24">
        <polyline points="3 6 5 6 21 6" />
        <path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2" />
      </symbol>
      <symbol id="i-x" viewBox="0 0 24 24">
        <line x1="6" y1="6" x2="18" y2="18" />
        <line x1="18" y1="6" x2="6" y2="18" />
      </symbol>
      <symbol id="i-upload" viewBox="0 0 24 24">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
        <polyline points="17 8 12 3 7 8" />
        <line x1="12" y1="3" x2="12" y2="15" />
      </symbol>
      <symbol id="i-folder" viewBox="0 0 24 24">
        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
      </symbol>
      <symbol id="i-globe" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" />
        <line x1="2" y1="12" x2="22" y2="12" />
        <path d="M12 2a15 15 0 0 1 0 20a15 15 0 0 1 0-20" />
      </symbol>
      <symbol id="i-logout" viewBox="0 0 24 24">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
        <polyline points="16 17 21 12 16 7" />
        <line x1="21" y1="12" x2="9" y2="12" />
      </symbol>
      <symbol id="i-music" viewBox="0 0 24 24">
        <path d="M9 18V5l12-2v13" />
        <circle cx="6" cy="18" r="3" />
        <circle cx="18" cy="16" r="3" />
      </symbol>
      <symbol id="i-users" viewBox="0 0 24 24">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
      </symbol>
    </defs>
  </svg>

  <div class="shell">
    <aside class="side glass">
      <div class="logo"><span class="dot"></span>Pulse</div>
      <nav>
        <a href="#" data-view="all" class="nav active"><svg class="ic">
            <use href="#i-home" />
          </svg>Home</a>
        <a href="#" data-view="online" class="nav"><svg class="ic">
            <use href="#i-globe" />
          </svg>Discover</a>
        <a href="#" data-view="liked" class="nav"><svg class="ic">
            <use href="#i-heart" />
          </svg>Liked Songs</a>
        <?php if ($_SESSION['role'] === 'admin') { ?>
          <a href="upload.php" class="nav"><svg class="ic">
              <use href="#i-upload" />
            </svg>Upload song</a>
          <a href="scan.php" class="nav"><svg class="ic">
              <use href="#i-folder" />
            </svg>Add songs from folder</a>
          <a href="admin.php" class="nav"><svg class="ic">
              <use href="#i-users" />
            </svg>Dashboard<span class="pill"><?= $totalUsers ?></span></a>
        <?php } ?>
      </nav>
      <div class="libhead"><span><svg class="ic">
            <use href="#i-library" />
          </svg>Your Library</span>
        <button id="newPl" class="ibtn" title="New playlist"><svg class="ic">
            <use href="#i-plus" />
          </svg></button>
      </div>
      <div id="playlists" class="plist"></div>
      <a href="logout.php" class="nav logout"><svg class="ic">
          <use href="#i-logout" />
        </svg>Log out</a>
    </aside>

    <main class="main glass">
      <div class="top">
        <div class="mnav">
          <a href="#" data-view="all" class="active">Home</a><a href="#" data-view="online">Discover</a><a href="#" data-view="liked">Liked</a><a href="logout.php">Logout</a>
        </div>
        <label class="search"><svg class="ic">
            <use href="#i-search" />
          </svg><input id="search" placeholder="Search songs or artists..." autocomplete="off">
          <div id="sugg" class="sugg" hidden></div>
        </label>
        <div class="user"><span class="av"><?= h(initial_char($uname)) ?></span><?= h($uname) ?></div>
      </div>
      <div class="head">
        <h1 id="title">Hello</h1>
        <select id="sort" class="sort" title="Sort (Merge Sort algorithm)">
          <option value="new">Sort: Newest</option>
          <option value="title">Title A–Z</option>
          <option value="titleDesc">Title Z–A</option>
          <option value="artist">Artist A–Z</option>
        </select>
        <button id="plDel" class="btn danger" hidden><svg class="ic">
            <use href="#i-trash" />
          </svg> Delete playlist</button>
      </div>
      <div id="content"></div>
    </main>

    <aside class="np glass">
      <div class="label">Now Playing</div>
      <div class="disc-wrap">
        <div class="glow"></div>
        <div class="disc">
          <div id="discImg">
            <div class="cv full"><svg class="ic">
                <use href="#i-music" />
              </svg></div>
          </div><span class="hole"></span>
        </div>
      </div>
      <div id="npTitle" class="np-title">Nothing playing</div>
      <div id="npArtist" class="np-artist">Pick a track to start</div>
      <div id="eq" class="eq"></div>
      <div class="label up">Up next</div>
      <div id="upnext"></div>
    </aside>

    <footer class="bar glass">
      <div class="fl">
        <div id="fCover">
          <div class="cv md"><svg class="ic">
              <use href="#i-music" />
            </svg></div>
        </div>
        <div class="fmeta"><b id="fTitle">—</b><small id="fArtist"></small></div>
        <button id="fLike" class="ibtn like" title="Like"><svg class="ic">
            <use href="#i-heart" />
          </svg></button>
      </div>
      <div class="fc">
        <div class="ctrl">
          <button id="shuffle" class="ibtn" title="Shuffle"><svg class="ic">
              <use href="#i-shuffle" />
            </svg></button>
          <button id="prev" class="ibtn" title="Previous"><svg class="ic fill">
              <use href="#i-prev" />
            </svg></button>
          <button id="play" class="playbtn" title="Play / Pause (Space)"><svg class="ic fill">
              <use href="#i-play" />
            </svg></button>
          <button id="next" class="ibtn" title="Next"><svg class="ic fill">
              <use href="#i-next" />
            </svg></button>
          <button id="repeat" class="ibtn" title="Repeat"><svg class="ic">
              <use href="#i-repeat" />
            </svg></button>
        </div>
        <div class="pbar"><span id="cur">0:00</span><input type="range" id="seek" class="rng" value="0" min="0" max="100" step="0.1"><span id="dur">0:00</span></div>
      </div>
      <div class="vol"><svg class="ic">
          <use href="#i-volume" />
        </svg><input type="range" id="vol" class="rng" min="0" max="1" step="0.01" value="0.8" style="--p:80%"></div>
    </footer>
  </div>

  <div id="modal" class="modal" hidden>
    <div id="mbody" class="mcard glass"></div>
  </div>
  <div id="toast" class="toast"></div>
  <script>
    window.UID = <?= (int)$_SESSION['uid'] ?>;
  </script>
  <script src="js/algorithms.js"></script>
  <script src="js/app.js"></script>
</body>

</html>