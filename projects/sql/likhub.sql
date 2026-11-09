-- LikHub local demo database (for XAMPP)
-- Creates the tables the code doesn't create by itself, plus demo artists and tutorials.
CREATE DATABASE IF NOT EXISTS likhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE likhub_db;

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(20)  NOT NULL DEFAULT 'learner',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    avatar        VARCHAR(255) DEFAULT NULL,
    bio           TEXT         DEFAULT NULL,
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tutorials (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    instructor_id INT          NOT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    content       MEDIUMTEXT,
    topic         VARCHAR(30)  DEFAULT 'Drawing',
    skill_level   VARCHAR(30)  DEFAULT 'Beginner',
    style         VARCHAR(50)  DEFAULT 'Traditional',
    duration_mins INT          DEFAULT 10,
    thumbnail     VARCHAR(255) DEFAULT NULL,
    is_published  TINYINT(1)   DEFAULT 1,
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX (instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    subject    VARCHAR(200) DEFAULT '',
    message    TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS commissions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    commission_ref VARCHAR(20),
    client_id      INT NOT NULL,
    artist_id      INT NOT NULL,
    status         VARCHAR(50) DEFAULT 'Submitted',
    rating         INT DEFAULT NULL,
    review_text    TEXT DEFAULT NULL,
    completed_at   DATETIME DEFAULT NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- demo data (password for every account: likhub-demo) ----------
DELETE FROM tutorials; DELETE FROM users WHERE email LIKE '%@likhub.local';
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Admin', 'admin@likhub.local', '$2y$10$0O1.Hd98WOVYFg.MPrikz.nLH44CuKVlnFGhU0eJdtbv8Qc04S/F2', 'admin', 1, '/likhub/images/logo-cropped.png', 'LikHub team account.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Lemuel D. Chua', 'lemuel@likhub.local', '$2y$10$90eXbYMa6B0.G0e9LjEx7usPyB/T8JTn65h6KRv0.2.0OtgXGrX3i', 'admin', 1, '/likhub/images/Lemuel_Chua.jpg', 'Co-Founder & Developer of LikHub. BSIT, Pamantasan ng Lungsod ng Valenzuela.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Rex Jean Mikel Villa', 'rex@likhub.local', '$2y$10$L7EIgoAd7EWH85tR/Pg7SOE2u7HHYk4KOV3zljYYr3J9j4Uq.5sqK', 'admin', 1, '/likhub/images/Rex_Jean_Villa.jpg', 'Co-Founder & Designer of LikHub. BSIT, Pamantasan ng Lungsod ng Valenzuela.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Andrea', 'andrea@likhub.local', '$2y$10$CBa2X6RH03x4Ffeob4ydqePVEwy5.NhEO1tOcZuPpSNzrksuQSIc6', 'artist', 1, '/likhub/images/Andrea1_digital.jpg', 'Digital artist who loves soft colors and anime styles.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('DRex', 'drex@likhub.local', '$2y$10$C6k7J.k4o55Qy7P7/nZMfOKOhTdnXDS7x0rS/Qbd5i7rfPvfJzmii', 'artist', 1, '/likhub/images/DRex_pfp.jpg', 'Traditional, digital and painting. Mecha and dark fantasy.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Keiose VB', 'keiose@likhub.local', '$2y$10$LXaLkg7zKjXUS/zj0w4Ke.nxlBlGAkajHKRtDzMBj6PYxw1Wfedq6', 'artist', 1, '/likhub/images/Keiose VB_pfp.jpg', 'Traditional artist, pencil and ink.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Sena', 'sena@likhub.local', '$2y$10$8wT5Uz480.PWXH1bEeh2CucUB2aM/5kcpmVLHTISyZeZY2SSsogiC', 'artist', 1, '/likhub/images/Sena_pfp.jpg', 'Chibi characters, digital illustrations and figure studies.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Yeoj', 'yeoj@likhub.local', '$2y$10$NIFNI3ve2hs4mmOMmfSjQeTrUeKQBB7l81rd4A262DBJ.FLYow3rC', 'artist', 1, '/likhub/images/Yeoj_pfp.jpg', 'Sci-fi characters and full digital pieces.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Yuhan', 'yuhan@likhub.local', '$2y$10$5kMUUnppgoMvxNVS8RAALedMXrLF.4.0I88MDpYVd2kFcOTzMgh4.', 'artist', 1, '/likhub/images/Yuhan1_digital.jpg', 'Line art and clean digital character work.');
INSERT INTO users (name, email, password_hash, role, is_active, avatar, bio) VALUES ('Demo Client', 'client@likhub.local', '$2y$10$FeW.IqbgJc/uLhUOqSnmXeb1PXxh0sKjfFMChkLTl4lk8rH480IZu', 'client', 1, NULL, NULL);

CREATE TABLE IF NOT EXISTS artist_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL UNIQUE, bio TEXT,
    commission_status VARCHAR(20) DEFAULT 'open', price_min INT DEFAULT 0, price_max INT DEFAULT 0,
    price_small INT DEFAULT 0, price_medium INT DEFAULT 0, price_large INT DEFAULT 0, shipping_fee INT DEFAULT 0,
    mediums VARCHAR(255) DEFAULT '', styles VARCHAR(255) DEFAULT '', avg_rating DECIMAL(3,2) DEFAULT NULL,
    total_commissions INT DEFAULT 0, INDEX idx_ap_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO artist_profiles (user_id, bio, commission_status, price_min, price_max, price_small, price_medium, price_large, shipping_fee, mediums, styles)
  SELECT id, bio, 'open', 300, 3000, 300, 900, 3000, 0, 'Digital', 'Anime,Chibi' FROM users WHERE name = 'Andrea'
  ON DUPLICATE KEY UPDATE commission_status = VALUES(commission_status), mediums = VALUES(mediums);
INSERT INTO artist_profiles (user_id, bio, commission_status, price_min, price_max, price_small, price_medium, price_large, shipping_fee, mediums, styles)
  SELECT id, bio, 'open', 250, 650, 250, 450, 650, 80, 'Digital,Traditional,Painting', 'Mecha,Fantasy' FROM users WHERE name = 'DRex'
  ON DUPLICATE KEY UPDATE commission_status = VALUES(commission_status), mediums = VALUES(mediums);
INSERT INTO artist_profiles (user_id, bio, commission_status, price_min, price_max, price_small, price_medium, price_large, shipping_fee, mediums, styles)
  SELECT id, bio, 'open', 300, 3000, 300, 900, 3000, 80, 'Digital,Traditional', 'Line art' FROM users WHERE name = 'Keiose VB'
  ON DUPLICATE KEY UPDATE commission_status = VALUES(commission_status), mediums = VALUES(mediums);
INSERT INTO artist_profiles (user_id, bio, commission_status, price_min, price_max, price_small, price_medium, price_large, shipping_fee, mediums, styles)
  SELECT id, bio, 'open', 300, 3000, 300, 900, 3000, 0, 'Digital,Traditional,Painting', 'Chibi,Portrait' FROM users WHERE name = 'Sena'
  ON DUPLICATE KEY UPDATE commission_status = VALUES(commission_status), mediums = VALUES(mediums);
INSERT INTO artist_profiles (user_id, bio, commission_status, price_min, price_max, price_small, price_medium, price_large, shipping_fee, mediums, styles)
  SELECT id, bio, 'open', 300, 3000, 300, 900, 3000, 0, 'Digital', 'Sci-fi' FROM users WHERE name = 'Yeoj'
  ON DUPLICATE KEY UPDATE commission_status = VALUES(commission_status), mediums = VALUES(mediums);
INSERT INTO artist_profiles (user_id, bio, commission_status, price_min, price_max, price_small, price_medium, price_large, shipping_fee, mediums, styles)
  SELECT id, bio, 'closed', 300, 3000, 300, 900, 3000, 0, 'Digital', 'Line art' FROM users WHERE name = 'Yuhan'
  ON DUPLICATE KEY UPDATE commission_status = VALUES(commission_status), mediums = VALUES(mediums);
INSERT INTO tutorials (instructor_id, title, description, content, topic, skill_level, style, duration_mins, is_published)
  SELECT id, 'Fundamentals of Art: Composition and Visual Storytelling', 'How to arrange your frame so the eye goes where you want it.', 'You already know how to draw shapes and you understand light and shadow. But a drawing can still feel empty if the elements are placed without intention. That is what composition solves.

Rule of thirds: divide your canvas into a 3x3 grid and place your subject on one of the four intersections.

Leading lines: roads, rivers, an arm or a row of trees all act like arrows. Point them toward your subject.

Negative space: the empty areas define your subject from the outside and give the eye somewhere to rest.', 'Drawing', 'Beginner', 'Traditional', 20, 1 FROM users WHERE email = 'admin@likhub.local';
INSERT INTO tutorials (instructor_id, title, description, content, topic, skill_level, style, duration_mins, is_published)
  SELECT id, 'Fundamentals of Art: Shading and Light', 'Turn flat shapes into forms with one light source.', 'Pick one light source and keep it the same for the whole drawing.

Find the five values: highlight, light, core shadow, reflected light and cast shadow.

Practice on a sphere, a cube and a cylinder before moving to faces and objects.', 'Drawing', 'Beginner', 'Traditional', 15, 1 FROM users WHERE email = 'admin@likhub.local';
INSERT INTO tutorials (instructor_id, title, description, content, topic, skill_level, style, duration_mins, is_published)
  SELECT id, 'Fundamentals of Art: Shapes', 'Everything you draw starts as simple shapes.', 'Break any subject into circles, squares and triangles first.

Sketch lightly, compare sizes, then add details only when the big shapes feel right.', 'Drawing', 'Beginner', 'Traditional', 10, 1 FROM users WHERE email = 'admin@likhub.local';
