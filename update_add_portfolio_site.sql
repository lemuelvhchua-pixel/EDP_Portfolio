-- Adds "THIS SITE" as a third web project (section, write-up and screenshots).
-- For a database that was imported before this was added: in phpMyAdmin, select
-- lemuel_portfolio, click Import, choose this file, Go. Safe to run more than once.

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
