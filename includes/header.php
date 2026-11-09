<?php
// Expects: $title, $page (home|about|portfolio|contact|login|register|admin), $bodyClass,
// optional $description and $crumb (breadcrumb label; skipped on the home page).
$user = current_user();
$links = [
    'home'      => ['index.php', 'HOME'],
    'about'     => ['about.php', 'ABOUT'],
    'portfolio' => ['portfolio.php', 'PORTFOLIO'],
    'contact'   => ['contact.php', 'CONTACT'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($title) ?></title>
    <?php if (!empty($description)): ?><meta name="description" content="<?= e($description) ?>" /><?php endif; ?>
    <?php
    // Link previews (Messenger, Discord, LinkedIn, X...). These need full URLs.
    $ogBase = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    if (basename($ogBase) === 'admin') $ogBase = dirname($ogBase);
    $ogDesc = $description ?? 'Lemuel D. Chua - IT student, photographer, designer and web developer from Valenzuela City.';
    ?>
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Lemuel D. Chua" />
    <meta property="og:title" content="<?= e($title) ?>" />
    <meta property="og:description" content="<?= e($ogDesc) ?>" />
    <meta property="og:url" content="<?= e($ogBase . '/' . basename($_SERVER['SCRIPT_NAME'])) ?>" />
    <meta property="og:image" content="<?= e($ogBase . '/images/intro-poster.jpg') ?>" />
    <meta property="og:image:width" content="1920" />
    <meta property="og:image:height" content="1080" />
    <meta property="og:image:alt" content="LC logo - Lemuel Chua Portfolio" />
    <meta name="twitter:card" content="summary_large_image" />
    <link rel="stylesheet" href="<?= url('css/style.css') ?>" />
    <link rel="stylesheet" href="<?= url('css/v2.css') ?>" />
    <link rel="stylesheet" href="<?= url('css/php.css') ?>" />
    <link rel="icon" type="image/png" href="<?= url('images/logo.png') ?>" />
</head>
<body class="<?= e($bodyClass) ?>"<?= ($user && $user['role'] === 'admin') ? ' data-feed="' . url('admin/messages.php') . '"' : '' ?>>
    <a class="skip-link" href="#main">SKIP TO CONTENT</a>

    <!-- NAVBAR -->
    <nav>
        <a class="logo" href="<?= url('index.php') ?>">
            <img src="<?= url('images/logo.png') ?>" alt="" />
            <span class="logo-text">Lemuel D. Chua</span>
        </a>
        <button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false"><span></span><span></span><span></span></button>
        <ul class="nav-links">
            <?php foreach ($links as $key => [$href, $label]): ?>
                <li><a href="<?= url($href) ?>"<?= $page === $key ? ' class="active" aria-current="page"' : '' ?>><?= $label ?></a></li>
            <?php endforeach; ?>
            <?php if ($user): ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="<?= url('admin/messages.php') ?>"<?= $page === 'admin' ? ' class="active" aria-current="page"' : '' ?>>INBOX<?php
                        $unread = (int) db()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
                        if ($unread) echo ' <span class="nav-badge">' . $unread . '</span>';
                    ?></a></li>
                    <li><a href="<?= url('admin/portfolio.php') ?>"<?= $page === 'manage' ? ' class="active" aria-current="page"' : '' ?>>MANAGE</a></li>
                <?php endif; ?>
                <li class="nav-user">
                    <span class="nav-hello">HI, <?= e(strtoupper(strtok($user['name'], ' '))) ?></span>
                    <form action="<?= url('logout.php') ?>" method="post" class="nav-logout">
                        <?= csrf_field() ?>
                        <button type="submit">LOGOUT</button>
                    </form>
                </li>
            <?php else: ?>
                <li><a href="<?= url('login.php') ?>" class="nav-login<?= in_array($page, ['login', 'register'], true) ? ' active' : '' ?>">&#9654; LOGIN</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php if (!empty($crumb)): ?>
    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="<?= url('index.php') ?>">HOME</a>
        <span class="sep">&#9658;</span>
        <span class="current"><?= e($crumb) ?></span>
    </div>
    <?php endif; ?>
