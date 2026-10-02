-- Dump van de database voor de video-comments opdracht
-- Importeren: via phpMyAdmin (tabblad "Importeren") of: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS video_comments
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE video_comments;

DROP TABLE IF EXISTS comments;

CREATE TABLE comments (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,           -- uniek nummer, telt vanzelf op
  naam          VARCHAR(50)  NOT NULL,                          -- max. 50 tekens
  email         VARCHAR(100) NOT NULL,                          -- max. 100 tekens
  commentaar    TEXT         NOT NULL,                          -- lange tekst (PHP begrenst op 1000 tekens)
  aangemaakt_op DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP, -- moment van plaatsen, automatisch
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Voorbeeldcomments
INSERT INTO comments (naam, email, commentaar, aangemaakt_op) VALUES
  ('Sanne', 'sanne@example.com', 'Wat een leuke video!', NOW() - INTERVAL 2 HOUR),
  ('Mo', 'mo@example.com', 'Dank voor het delen, heel helder uitgelegd.', NOW() - INTERVAL 1 DAY);
