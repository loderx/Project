<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName) ?> - Вход</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script defer src="assets/js/app.js"></script>
</head>
<body class="login-page theme-auto">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo">
            </div>
            <h1>Добре дошли</h1>
            <p class="login-subtitle">Влезте, за да управлявате системата си.</p>
            <?php foreach ($flashes as $flash) : ?>
                <div class="alert <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
            <?php endforeach; ?>
            <?php include __DIR__ . '/' . $view; ?>
        </div>
        <div class="login-panel">
            <h2>Модерен контролен център</h2>
            <ul>
                <li>Светъл и тъмен режим с едно кликване</li>
                <li>Модулна архитектура с бързо активиране</li>
                <li>Пълен контрол над абонаментите</li>
            </ul>
        </div>
    </div>
</body>
</html>
