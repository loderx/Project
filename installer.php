<?php
session_start();

if (file_exists(__DIR__ . '/config.php')) {
    header('Location: public/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? 'photocalendar');
    $dbUser = trim($_POST['db_user'] ?? 'root');
    $dbPass = $_POST['db_pass'] ?? '';

    $studio = trim($_POST['studio_name'] ?? 'Main Studio');
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@example.com');
    $adminPass = $_POST['admin_password'] ?? '';

    try {
        $pdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($schema);

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO studios(name, subscription_end, created_at) VALUES(?, DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW())');
        $stmt->execute([$studio]);
        $studioId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO users(studio_id, name, email, password_hash, role, theme, active_until, created_at) VALUES(?, ?, ?, ?, "admin", "light", DATE_ADD(NOW(), INTERVAL 1 YEAR), NOW())');
        $stmt->execute([$studioId, 'Administrator', $adminEmail, $hash]);

        $config = "<?php\nreturn [\n    'db' => [\n        'host' => '{$dbHost}',\n        'name' => '{$dbName}',\n        'user' => '{$dbUser}',\n        'pass' => '" . addslashes($dbPass) . "',\n    ],\n    'app' => [\n        'name' => 'PhotoCalendar',\n        'company' => 'LOD Corporation',\n        'version' => '2.4'\n    ]\n];\n";
        file_put_contents(__DIR__ . '/config.php', $config);

        header('Location: public/index.php?installed=1');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>PhotoCalendar Installer</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="install-body"><div class="card"><h1>PhotoCalendar Installer</h1>
<p>LOD Corporation • v2.4</p>
<?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="grid">
<input name="db_host" placeholder="DB Host" value="localhost" required>
<input name="db_name" placeholder="DB Name" value="photocalendar" required>
<input name="db_user" placeholder="DB User" value="root" required>
<input type="password" name="db_pass" placeholder="DB Password">
<input name="studio_name" placeholder="Studio Name" required>
<input type="email" name="admin_email" placeholder="Admin Email" required>
<input type="password" name="admin_password" placeholder="Admin Password" required>
<button type="submit">Install</button>
</form></div></body></html>
