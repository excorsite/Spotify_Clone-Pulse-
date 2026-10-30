-- Optional: yo table app le afai banauchha. Manual banaunu paryo bhane matra import garnus.
USE spotify_clone;
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
