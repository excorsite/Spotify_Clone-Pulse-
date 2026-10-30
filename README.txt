PULSE — Music Player (HTML + CSS + JavaScript + PHP + MySQL)

PAGES
  User login ........ http://localhost/spotify_clone/login.php   (normal user matra)
  User sign up ...... http://localhost/spotify_clone/register.php (hamesha normal user)
  Admin login ....... http://localhost/spotify_clone/admin-login.php (admin matra, alag page)
  Admin dashboard ... http://localhost/spotify_clone/admin.php

NAYA INSTALL (XAMPP)
1. Folder "spotify_clone" ko C:\xampp\htdocs\ bhitra rakhnus.
2. XAMPP Control Panel ma Apache ra MySQL Start garnus.
3. http://localhost/phpmyadmin -> Import -> database.sql -> Go.
4. http://localhost/spotify_clone/create-admin.php ma pahilo ADMIN account banaunus
   (admin bhaepachhi yo page afai lock hunchha).
5. http://localhost/spotify_clone/admin-login.php bata admin login garnus -> Dashboard.
6. Normal user le register.php bata account banaera login.php bata login garchha.

PURANO PROJECT UPDATE GARNE (database ma kei change chhaina)
Replace: config.php, login.php, register.php, logout.php, admin.php, css/style.css
Naya files: admin-login.php, create-admin.php, hero.php, bg.php
Tapai ko purano admin account (pahilo register gareko) admin-login.php bata login hunchha.
uploads/ folder ma kei nachhunus.

ADMIN DASHBOARD: Online now, har user ko last login, kati choti login, kati song bajayo, last k bajayo (user_activity table afai banchha).
ALGORITHMS (8): ALGORITHMS.txt padhnus — Collaborative Filtering, Levenshtein, Merge Sort, Fisher-Yates, Trie, Binary Search, LRU Cache, Min-Heap Top-K.
UPDATE ma replace (config.php, login.php, admin-login.php, logout.php pani): index.php, api.php, admin.php, algorithms.php, css/style.css, js/app.js, js/algorithms.js, ALGORITHMS.txt

SHORTCUTS: Space = play/pause, <- -> = prev/next
