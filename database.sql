CREATE DATABASE IF NOT EXISTS spotify_clone CHARACTER SET utf8mb4;
USE spotify_clone;
CREATE TABLE IF NOT EXISTS users(
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') DEFAULT 'user'
);
CREATE TABLE IF NOT EXISTS songs(
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  artist VARCHAR(150) NOT NULL,
  cover VARCHAR(500) DEFAULT NULL,
  file VARCHAR(500) NOT NULL,
  source VARCHAR(20) DEFAULT 'local',
  ext_id VARCHAR(60) DEFAULT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS likes(
  user_id INT, song_id INT,
  PRIMARY KEY(user_id, song_id),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(song_id) REFERENCES songs(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS playlists(
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS playlist_songs(
  playlist_id INT, song_id INT,
  PRIMARY KEY(playlist_id, song_id),
  FOREIGN KEY(playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
  FOREIGN KEY(song_id) REFERENCES songs(id) ON DELETE CASCADE
);

-- Admin dashboard ko lagi: kun user ko login, last seen, kati song bajayo
CREATE TABLE IF NOT EXISTS user_activity(
  user_id INT PRIMARY KEY,
  last_login DATETIME NULL,
  last_seen DATETIME NULL,
  logged_in TINYINT(1) DEFAULT 0,
  logins INT DEFAULT 0,
  plays INT DEFAULT 0,
  last_song_id INT NULL,
  last_played_at DATETIME NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(last_song_id) REFERENCES songs(id) ON DELETE SET NULL
);
