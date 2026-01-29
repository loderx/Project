<?php
$page = $params['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script defer src="assets/js/app.js"></script>
</head>
<body class="theme-auto <?= $isMobile ? 'is-mobile' : '' ?>">
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" class="brand-logo">
                <div>
                    <div class="brand-title"><?= htmlspecialchars($appName) ?></div>
                    <div class="brand-subtitle">Професионална система</div>
                </div>
            </div>
            <nav class="nav">
                <a href="index.php" class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>">Табло</a>
                <?php if ($user && $user['role'] === 'admin') : ?>
                    <a href="index.php?page=admin_users" class="nav-link <?= $page === 'admin_users' ? 'active' : '' ?>">Потребители</a>
                    <a href="index.php?page=admin_modules" class="nav-link <?= $page === 'admin_modules' ? 'active' : '' ?>">Модули</a>
                    <a href="index.php?page=admin_settings" class="nav-link <?= $page === 'admin_settings' ? 'active' : '' ?>">Настройки</a>
                <?php endif; ?>
                <?php foreach ($modules as $module) : ?>
                    <a href="index.php?page=module&slug=<?= urlencode($module['slug']) ?>" class="nav-link"><?= htmlspecialchars($module['menu_label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <?php if ($user) : ?>
                    <div class="user-mini">
                        <div><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></div>
                        <small><?= htmlspecialchars($user['email']) ?></small>
                    </div>
                    <a href="index.php?page=logout" class="button ghost">Изход</a>
                <?php endif; ?>
            </div>
        </aside>
        <main class="main">
            <header class="topbar">
                <div>
                    <h1><?= htmlspecialchars($params['title'] ?? 'Табло') ?></h1>
                    <p><?= htmlspecialchars($params['subtitle'] ?? '') ?></p>
                </div>
                <div class="topbar-actions">
                    <button class="button ghost" type="button" data-theme-toggle>Смени тема</button>
                </div>
            </header>
            <?php foreach ($flashes as $flash) : ?>
                <div class="alert <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
            <?php endforeach; ?>
            <section class="content">
                <?php include __DIR__ . '/' . $view; ?>
            </section>
        </main>
    </div>
</body>
</html>
