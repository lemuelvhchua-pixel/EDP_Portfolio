-- Lemuel D. Chua - Portfolio database
-- Import this in phpMyAdmin (Import tab) or run: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS lemuel_portfolio
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lemuel_portfolio;

-- Accounts. The admin is created below; sign-ups are always normal accounts.
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(80)  NOT NULL,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB;

-- Admin account (login details are in README.md). INSERT IGNORE: importing again won't duplicate it.
INSERT IGNORE INTO users (name, email, password_hash, role) VALUES
 ('Lemuel D. Chua', 'admin@portfolio.local', '$2y$10$e1.LVw3HNq15Dev6ejOmtev3QZTm6T.2uCCYQ4eBH4hwfWhH3J.fO', 'admin');

-- Messages sent from the contact form. Admins read them in the Inbox.
CREATE TABLE IF NOT EXISTS messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NULL,
    name       VARCHAR(80)  NOT NULL,
    email      VARCHAR(190) NOT NULL,
    subject    VARCHAR(150) NOT NULL DEFAULT '',
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (is_read),
    CONSTRAINT fk_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
--  Dynamic portfolio content (edit it in the site: MANAGE page)
-- ---------------------------------------------------------------

-- The sections of the portfolio page. "kind" decides how a section is shown.
CREATE TABLE IF NOT EXISTS sections (
    section_key VARCHAR(30)  NOT NULL PRIMARY KEY,
    kind        VARCHAR(20)  NOT NULL,
    title       VARCHAR(100) NOT NULL,
    nav_label   VARCHAR(40)  NOT NULL,
    intro       TEXT         NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    is_visible  TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Write-ups for projects and groups (web projects, Figma projects, certificate paths).
CREATE TABLE IF NOT EXISTS projects (
    slug        VARCHAR(30)  NOT NULL PRIMARY KEY,
    title       VARCHAR(100) NOT NULL,
    description TEXT         NULL,
    logo        VARCHAR(255) NULL,
    logo_light  TINYINT(1)   NOT NULL DEFAULT 0,
    meta        TEXT         NULL COMMENT 'one "LABEL: value" per line',
    features    TEXT         NULL COMMENT 'one feature per line',
    tech        VARCHAR(255) NULL COMMENT 'comma separated',
    site_url    VARCHAR(255) NULL,
    code_path   VARCHAR(100) NULL COMMENT 'folder in projects/ for the VIEW CODE button',
    case_study  TEXT         NULL COMMENT 'one "LABEL: text" per line, e.g. PROBLEM: ...'
) ENGINE=InnoDB;

-- Older copies of this database don't have case_study yet; this adds it safely.
ALTER TABLE projects ADD COLUMN IF NOT EXISTS case_study TEXT NULL COMMENT 'one "LABEL: text" per line, e.g. PROBLEM: ...';

-- Every picture or video shown on the portfolio page.
CREATE TABLE IF NOT EXISTS items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    list_key    VARCHAR(30)  NOT NULL COMMENT 'which list it belongs to, e.g. photography, graphics, certs_sql',
    title       VARCHAR(150) NOT NULL,
    description TEXT         NULL,
    image       VARCHAR(255) NOT NULL,
    thumb       VARCHAR(255) NULL,
    media       VARCHAR(255) NULL COMMENT 'YouTube link (or older MP4 path) for the video list',
    is_featured TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'certificates: the learning path certificate',
    is_visible  TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order  INT          NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_list (list_key, sort_order)
) ENGINE=InnoDB;

INSERT IGNORE INTO sections (section_key, kind, title, nav_label, intro, sort_order, is_visible) VALUES
 ('photography', 'slideshow', 'PHOTOGRAPHY', 'PHOTOGRAPHY', 'Night walks around Intramuros and the Pasig River, plus a few favorites from home. Use the arrows or thumbnails, or click a photo to see it full size.', 10, 1),
 ('likhub', 'case', 'LIKHUB - WEB PROJECT', 'LIKHUB', NULL, 20, 1),
 ('kabakal', 'case', 'KABAKAL - WEB PROJECT', 'KABAKAL', NULL, 30, 1),
 ('ui', 'figma', 'UI DESIGN - FIGMA', 'UI DESIGN', 'Two Figma projects. Click any screen to see it bigger, swipe or use the arrows to go through MicroMML.', 40, 1),
 ('graphics', 'cards', 'GRAPHIC DESIGN', 'GRAPHIC DESIGN', NULL, 50, 1),
 ('art', 'cards', 'ART', 'ART', NULL, 60, 1),
 ('networking', 'cards', 'NETWORKING', 'NETWORKING', 'Network setups made in Cisco Packet Tracer for Networking 1.', 70, 1),
 ('certs', 'certs', 'CERTIFICATES', 'CERTIFICATES', 'CodeSignal learning paths I finished in 2026. Each path ends with its own certificate.', 80, 1),
 ('video', 'video', 'FEATURED VIDEO', 'VIDEO', NULL, 90, 1);

INSERT IGNORE INTO projects (slug, title, description, logo, logo_light, meta, features, tech, site_url, code_path) VALUES
 ('likhub', 'LikHub', 'LikHub is our site for student artists. You can learn drawing lessons, post your work in the gallery, and request commissions from other student artists. Made by students, for students.', 'images/likhub/logo.png', 1, 'ROLE: Co-Founder & Developer
TEAM: With Rex Jean Mikel Villa (Co-Founder & Designer)
YEAR: 2026', 'Learn page with art tutorials
Create gallery for traditional, digital and painting work
Commission page with artist profiles, price ranges and inquiries
Accounts with email verification and password reset
Order tracking for commissions', 'PHP, MySQL, JavaScript, HTML / CSS', 'https://likhub.online/likhub/', 'likhub'),
 ('kabakal', 'Kabakal', 'Kabakal Fitness Equipment is a shop management and online shopping system. Customers browse gym gear, add to cart and pay through GCash, while the admin side runs the whole store.', 'images/kabakal/kabakal-logo.png', 0, 'TYPE: Online shop + admin system
BUILT WITH: Plain PHP (PDO) and MySQL, no frameworks
YEAR: 2026', 'Cart, checkout and GCash payment proof upload
Admin verifies payments, partial payments show the remaining balance
Stock moves from available to reserved to sold as orders update
Dashboard groups products into high, moderate and low performing
Products, inventory, orders, reviews, promo videos and export', 'PHP, MySQL, JavaScript, HTML / CSS', NULL, 'kabakal'),
 ('login_app', 'LOGIN APP', 'A by three project for figma activity that required login credentials. Four screens from logging in to the home screen.', NULL, 0, NULL, NULL, NULL, NULL, NULL),
 ('micromml', 'MICROMML', 'A by three project for creating a business proposal using figma. An app for managing small businesses: invoices, expenses, loans and marketing.', NULL, 0, NULL, NULL, NULL, NULL, NULL),
 ('certs_sql', 'SQL', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
 ('certs_python', 'PYTHON DATA', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL);

-- Case studies for the web projects (only filled in if still empty, so edits made on the MANAGE page are kept).
UPDATE projects SET case_study = 'PROBLEM: Student artists had no single place to learn, show their work and take paid commissions, so it all happened across scattered posts and group chats.
APPROACH: Built one site with three parts: a free lesson library, a gallery for traditional, digital and painting work, and a commission page with artist profiles and price ranges.
HARDEST PART: Making commissions feel safe. Accounts need email verification and can reset passwords, and every commission has an order tracker so the buyer and the artist see the same status from request to delivery.
RESULT: A working platform for student artists, built by two students: I did the development while my co-founder handled the design.'
WHERE slug = 'likhub' AND (case_study IS NULL OR case_study = '');

UPDATE projects SET case_study = 'PROBLEM: A fitness equipment shop selling online can easily oversell stock or lose track of who has paid, especially when customers pay through GCash and send proof by hand.
APPROACH: Built the shop and the admin side as one system in plain PHP and MySQL: customers browse, add to cart and upload their GCash proof, and the admin verifies payments and runs the store.
HARDEST PART: Keeping stock accurate while orders wait for payment. Items move from available to reserved to sold as each order is verified, and partial payments show the balance still owed.
RESULT: One place to manage products, inventory, orders, reviews and promo videos, with a dashboard that sorts products into high, moderate and low performers and an export for records.'
WHERE slug = 'kabakal' AND (case_study IS NULL OR case_study = '');

-- Starting content. INSERT IGNORE means importing this file again won't create duplicates.
INSERT IGNORE INTO items (id, list_key, title, description, image, thumb, media, is_featured, is_visible, sort_order) VALUES
 (1, 'photography', 'OLD WALLS AT NIGHT', 'The ruins lit up at night while we were exploring Intramuros', 'images/photos/intramuros-walls-night.jpg', 'images/photos/thumbs/intramuros-walls-night.jpg', NULL, 0, 1, 10),
 (2, 'photography', 'INTRAMUROS ENTRANCE', 'Walking through the gate into Intramuros at night', 'images/photos/intramuros-gate.jpg', 'images/photos/thumbs/intramuros-gate.jpg', NULL, 0, 1, 20),
 (3, 'photography', 'INTRAMUROS MURALLA', 'The Intramuros sign on the old stone wall', 'images/photos/intramuros-sign.jpg', 'images/photos/thumbs/intramuros-sign.jpg', NULL, 0, 1, 30),
 (4, 'photography', 'INTRAMUROS SCENERY', 'An evening view while exploring Intramuros', 'images/photos/intramuros-moat.jpg', 'images/photos/thumbs/intramuros-moat.jpg', NULL, 0, 1, 40),
 (5, 'photography', 'INTRAMUROS BENCHES', 'A quiet row of park benches inside Intramuros at night', 'images/photos/intramuros-park-night.jpg', 'images/photos/thumbs/intramuros-park-night.jpg', NULL, 0, 1, 50),
 (6, 'photography', 'PASIG RIVER', 'A night walk along the Pasig River', 'images/photos/pasig-river.jpg', 'images/photos/thumbs/pasig-river.jpg', NULL, 0, 1, 60),
 (7, 'photography', 'PASIG RIVER BRIDGE', 'The bridge over the Pasig River, lit up after dark', 'images/photos/pasig-bridge.jpg', 'images/photos/thumbs/pasig-bridge.jpg', NULL, 0, 1, 70),
 (8, 'photography', 'FOOD STALLS', 'Food stalls along the Pasig riverside at night', 'images/photos/riverside-food-stalls.jpg', 'images/photos/thumbs/riverside-food-stalls.jpg', NULL, 0, 1, 80),
 (9, 'photography', 'CITY LIGHTS', 'The buildings across the river reflecting on the water', 'images/photos/city-lights.jpg', 'images/photos/thumbs/city-lights.jpg', NULL, 0, 1, 90),
 (10, 'photography', 'CANDID', 'A candid shot of someone photographing the view', 'images/photos/candid.jpg', 'images/photos/thumbs/candid.jpg', NULL, 0, 1, 100),
 (11, 'photography', 'FULL MOON', 'Caught the moon right in between the buildings', 'images/photos/full-moon.jpg', 'images/photos/thumbs/full-moon.jpg', NULL, 0, 1, 110),
 (12, 'photography', 'SUNSET', 'An orange evening sunset', 'images/photos/sunset.jpg', 'images/photos/thumbs/sunset.jpg', NULL, 0, 1, 120),
 (13, 'photography', 'CATTO', 'Cute catto named bella', 'images/photos/bella.jpg', 'images/photos/thumbs/bella.jpg', NULL, 0, 1, 130),
 (14, 'likhub', 'HOME', 'The landing page: lessons, the artist gallery and commissions in one place', 'images/likhub/home.jpg', NULL, NULL, 0, 1, 10),
 (15, 'likhub', 'COMMISSION AN ARTIST', 'Browse student artists by medium and price, then send a commission request', 'images/likhub/commission.jpg', NULL, NULL, 0, 1, 20),
 (16, 'likhub', 'ARTWORK GALLERY', 'Student artists post their work and get likes and feedback', 'images/likhub/gallery.jpg', NULL, NULL, 0, 1, 30),
 (17, 'likhub', 'TUTORIAL LIBRARY', 'Free beginner lessons filtered by level and topic', 'images/likhub/tutorials.jpg', NULL, NULL, 0, 1, 40),
 (18, 'likhub', 'ABOUT', 'Founded by students, for students', 'images/likhub/about.jpg', NULL, NULL, 0, 1, 50),
 (19, 'kabakal', 'SHOP HOMEPAGE', 'Build your strength, build your gym: the first thing customers see', 'images/kabakal/home.jpg', NULL, NULL, 0, 1, 10),
 (20, 'kabakal', 'PRODUCTS', 'Gym equipment by category with stock status and quick buy', 'images/kabakal/products.jpg', NULL, NULL, 0, 1, 20),
 (21, 'kabakal', 'CUSTOMER LOGIN', 'Customers log in to check out, upload GCash proof and track orders', 'images/kabakal/login.jpg', NULL, NULL, 0, 1, 30),
 (22, 'kabakal', 'STAFF LOGIN', 'Separate login for the admin side that runs the store', 'images/kabakal/staff-login.jpg', NULL, NULL, 0, 1, 40),
 (23, 'login_app', 'LOGIN', 'Logging in with email and password', 'images/ui/login-app/1-login.jpg', NULL, NULL, 0, 1, 10),
 (24, 'login_app', 'CREATE ACCOUNT', 'Registering with a verification code', 'images/ui/login-app/2-create-account.jpg', NULL, NULL, 0, 1, 20),
 (25, 'login_app', 'ACCOUNT CREATED', 'Confirmation after signing up', 'images/ui/login-app/3-account-created.jpg', NULL, NULL, 0, 1, 30),
 (26, 'login_app', 'LOGIN SUCCESS', 'Landing on the home screen', 'images/ui/login-app/4-login-success.jpg', NULL, NULL, 0, 1, 40),
 (27, 'micromml', 'SPLASH', 'Splash screen', 'images/ui/micromml/01-splash.jpg', NULL, NULL, 0, 1, 10),
 (28, 'micromml', 'WELCOME', 'Welcome screen', 'images/ui/micromml/02-welcome.jpg', NULL, NULL, 0, 1, 20),
 (29, 'micromml', 'LOGIN', 'Login', 'images/ui/micromml/03-login.jpg', NULL, NULL, 0, 1, 30),
 (30, 'micromml', 'CREATE ACCOUNT', 'Creating a business account', 'images/ui/micromml/04-create-account.jpg', NULL, NULL, 0, 1, 40),
 (31, 'micromml', 'BUSINESS TYPE', 'Picking the business type', 'images/ui/micromml/05-business-type.jpg', NULL, NULL, 0, 1, 50),
 (32, 'micromml', 'RESET PASSWORD', 'Resetting the password with an email code', 'images/ui/micromml/06-reset-password.jpg', NULL, NULL, 0, 1, 60),
 (33, 'micromml', 'DASHBOARD', 'Dashboard with revenue, expenses and quick actions', 'images/ui/micromml/07-dashboard.jpg', NULL, NULL, 0, 1, 70),
 (34, 'micromml', 'DASHBOARD MENU', 'Account menu', 'images/ui/micromml/08-dashboard-menu.jpg', NULL, NULL, 0, 1, 80),
 (35, 'micromml', 'SETTINGS', 'Settings: dark mode, text size, screen reader and voice input', 'images/ui/micromml/09-settings.jpg', NULL, NULL, 0, 1, 90),
 (36, 'micromml', 'MY ACCOUNT', 'Account page', 'images/ui/micromml/10-my-account.jpg', NULL, NULL, 0, 1, 100),
 (37, 'micromml', 'FINANCE', 'Finance overview', 'images/ui/micromml/11-finance.jpg', NULL, NULL, 0, 1, 110),
 (38, 'micromml', 'CREATE INVOICE', 'Creating an invoice', 'images/ui/micromml/12-create-invoice.jpg', NULL, NULL, 0, 1, 120),
 (39, 'micromml', 'TRACK EXPENSES', 'Tracking expenses by category', 'images/ui/micromml/13-track-expenses.jpg', NULL, NULL, 0, 1, 130),
 (40, 'micromml', 'LEND MONEY', 'Lending money to other businesses', 'images/ui/micromml/14-lend-money.jpg', NULL, NULL, 0, 1, 140),
 (41, 'micromml', 'BORROW MONEY', 'Borrowing and paying back loans', 'images/ui/micromml/15-borrow-money.jpg', NULL, NULL, 0, 1, 150),
 (42, 'micromml', 'MARKET TOOLS', 'Marketing tools', 'images/ui/micromml/16-market-tools.jpg', NULL, NULL, 0, 1, 160),
 (43, 'micromml', 'SUSTAINABILITY', 'Sustainability score and tips', 'images/ui/micromml/17-sustainability.jpg', NULL, NULL, 0, 1, 170),
 (44, 'graphics', 'COMFFEESHOP BANNER', 'A coffee shop banner, project in Multimedia', 'images/graphics/comffeeshop-banner.jpg', NULL, NULL, 0, 1, 10),
 (45, 'graphics', 'ARTIST PROFILE', 'My graphic illustrator profile, project in Multimedia', 'images/graphics/artist-profile.jpg', NULL, NULL, 0, 1, 20),
 (46, 'graphics', 'LET\'S GO JAPAN', 'An activity for Contemporary World about my dream destination', 'images/graphics/lets-go-japan.jpg', NULL, NULL, 0, 1, 30),
 (47, 'graphics', 'IMPRINT OF TECHNOLOGY', 'A poster about how technology shows up in school, news and social life', 'images/graphics/imprint-of-technology.jpg', NULL, NULL, 0, 1, 40),
 (48, 'art', 'SOAP SCULPTURE', 'A soap carving using perla for Art Appreciation', 'images/art/soap-carving.jpg', NULL, NULL, 0, 1, 10),
 (49, 'art', 'FIBONACCI', 'A fibonacci sequence drawing which I took so many tries on for Mathematics in the Modern World', 'images/art/fibonacci.jpg', NULL, NULL, 0, 1, 20),
 (50, 'networking', 'CAMPUS NETWORK', 'A network for three campuses with offices like registrar, library and comlab, Networking 1', 'images/network/campus-network.jpg', NULL, NULL, 0, 1, 10),
 (51, 'networking', 'BRANCH WAN', 'Malinta as the main router connecting the other Valenzuela branches, Networking 1', 'images/network/valenzuela-wan.jpg', NULL, NULL, 0, 1, 20),
 (52, 'networking', 'SERVER NETWORK', 'Three routers with a server on the main side, Networking 1', 'images/network/server-network.jpg', NULL, NULL, 0, 1, 30),
 (53, 'networking', 'THREE ROUTER LAN', 'Three routers each with their own LANs, Networking 1', 'images/network/three-router-lan.jpg', NULL, NULL, 0, 1, 40),
 (54, 'certs_sql', 'JOURNEY INTO SQL', 'Learning path certificate, CodeSignal, Nov 2026', 'images/certs/journey-into-sql.jpg', NULL, NULL, 1, 1, 10),
 (55, 'certs_sql', 'INTRO TO SQL', 'Feb 2026', 'images/certs/intro-to-sql.jpg', NULL, NULL, 0, 1, 20),
 (56, 'certs_sql', 'SQL JOINS', 'Feb 2026', 'images/certs/sql-joins.jpg', NULL, NULL, 0, 1, 30),
 (57, 'certs_sql', 'FUNCTIONS & CLAUSES', 'Feb 2026', 'images/certs/sql-functions-clauses.jpg', NULL, NULL, 0, 1, 40),
 (58, 'certs_sql', 'ADVANCED QUERIES', 'Nov 2026', 'images/certs/advanced-queries.jpg', NULL, NULL, 0, 1, 50),
 (59, 'certs_python', 'DATA ANALYSIS WITH PYTHON', 'Learning path certificate, CodeSignal, Apr 2026', 'images/certs/data-analysis-python.jpg', NULL, NULL, 1, 1, 10),
 (60, 'certs_python', 'PYTHON LIBRARIES', 'Mar 2026', 'images/certs/python-libraries.jpg', NULL, NULL, 0, 1, 20),
 (61, 'certs_python', 'STATISTICS', 'Apr 2026', 'images/certs/statistics-python.jpg', NULL, NULL, 0, 1, 30),
 (62, 'certs_python', 'DATA CLEANING', 'Apr 2026', 'images/certs/data-cleaning.jpg', NULL, NULL, 0, 1, 40),
 (63, 'certs_python', 'PREPROCESSING', 'Apr 2026', 'images/certs/advanced-preprocessing.jpg', NULL, NULL, 0, 1, 50),
 (64, 'certs_python', 'HYPOTHESIS TESTING', 'Apr 2026', 'images/certs/hypothesis-testing.jpg', NULL, NULL, 0, 1, 60),
 (65, 'certs_python', 'REPORTING & VISUALS', 'Apr 2026', 'images/certs/reporting-visualization.jpg', NULL, NULL, 0, 1, 70),
 (66, 'video', 'Group Video / Estranghero', 'FEATURED', 'images/estranghero-poster.jpg', NULL, 'https://www.youtube.com/embed/VvwvFlpmBM4', 0, 1, 10);


-- ---------------------------------------------------------------
-- THIS SITE as a third web project.
-- Database already set up (like the live site)? Copy from this line to the end
-- into phpMyAdmin > SQL and click Go. Safe to run more than once.

INSERT IGNORE INTO sections (section_key, kind, title, nav_label, intro, sort_order, is_visible) VALUES
 ('portfolio_site', 'case', 'THIS SITE - WEB PROJECT', 'THIS SITE', NULL, 35, 1);

INSERT IGNORE INTO projects (slug, title, description, logo, logo_light, meta, features, tech, site_url, code_path) VALUES
 ('portfolio_site', 'THIS PORTFOLIO', 'The site you are on. It has its own content manager, so I can add, edit, hide and reorder everything on this page and read contact messages from the browser, without touching the code.', NULL, 0, 'ROLE: Design and development (solo)
BUILT WITH: Plain PHP (PDO) and MySQL, no frameworks
YEAR: 2026', 'MANAGE page: upload, edit, hide, reorder and delete work
Section titles, intros and project write-ups editable from the browser
Contact form with an admin inbox that updates live
Hashed passwords, CSRF tokens and a lockout after 5 wrong logins
Slideshows, lightbox and YouTube embeds
Works on phones and respects reduced-motion settings', 'PHP, MySQL, JavaScript, HTML / CSS', NULL, '.');

UPDATE projects SET case_study = 'PROBLEM: With a plain HTML portfolio, every new photo, certificate or project meant editing code and uploading files again.
APPROACH: Moved the content into MySQL (sections, items and project write-ups). The public pages are built from the database, and an admin-only MANAGE page edits all of it.
HARDEST PART: Keeping the admin side safe on a public host: hashed passwords, a CSRF token on every form, a login lockout, checked uploads, and PHP errors hidden once the site is online.
RESULT: Adding new work takes a minute from the browser, and messages from the contact form land in an inbox instead of getting lost.'
WHERE slug = 'portfolio_site' AND (case_study IS NULL OR case_study = '');

INSERT IGNORE INTO items (id, list_key, title, description, image, thumb, media, is_featured, is_visible, sort_order) VALUES
 (67, 'portfolio_site', 'HOME', 'The home page: featured work first, then what I do', 'images/portfolio-site/home.png', NULL, NULL, 0, 1, 10),
 (68, 'portfolio_site', 'PORTFOLIO PAGE', 'Every section is built from the database', 'images/portfolio-site/portfolio.png', NULL, NULL, 0, 1, 20),
 (69, 'portfolio_site', 'MANAGE PAGE', 'Admin only: add, edit, hide and reorder work', 'images/portfolio-site/manage.png', NULL, NULL, 0, 1, 30),
 (70, 'portfolio_site', 'INBOX', 'Contact messages with a live unread count', 'images/portfolio-site/inbox.png', NULL, NULL, 0, 1, 40),
 (71, 'portfolio_site', 'CONTACT', 'The contact form that feeds the inbox', 'images/portfolio-site/contact.png', NULL, NULL, 0, 1, 50);
