<?php
require 'config.php';
$admin = ($_SESSION['role'] ?? '') === 'admin';
if (!empty($_SESSION['uid'])) track_logout($conn, (int)$_SESSION['uid']);
session_destroy();
header('Location: ' . ($admin ? 'admin-login.php' : 'login.php'));
