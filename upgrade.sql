-- Pahila ko database.sql import gariseko bhaye matra yo import garnus (spotify_clone select gareko hunuparchha)
USE spotify_clone;
ALTER TABLE songs MODIFY title VARCHAR(200) NOT NULL, MODIFY artist VARCHAR(150) NOT NULL,
  MODIFY cover VARCHAR(500) DEFAULT NULL, MODIFY file VARCHAR(500) NOT NULL,
  ADD COLUMN source VARCHAR(20) DEFAULT 'local',
  ADD COLUMN ext_id VARCHAR(60) DEFAULT NULL UNIQUE;
