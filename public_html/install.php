<?php
if (file_exists(__DIR__ . '/config.php')) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $appName = trim($_POST['app_name'] ?? 'Modern System');
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = $_POST['admin_pass'] ?? '';
    $adminEmail = trim($_POST['admin_email'] ?? '');

    if (!$dbName || !$dbUser || !$adminPass || !$adminEmail) {
        $errors[] = 'Моля, попълнете всички задължителни полета.';
    } else {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $pdo->exec('CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(120) NOT NULL,
                full_name VARCHAR(120) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT "user",
                subscription_end DATE NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            $pdo->exec('CREATE TABLE IF NOT EXISTS modules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(100) UNIQUE NOT NULL,
                name VARCHAR(120) NOT NULL,
                menu_label VARCHAR(120) NOT NULL,
                menu_path VARCHAR(120) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                `key` VARCHAR(100) UNIQUE NOT NULL,
                value TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            $adminStmt = $pdo->prepare('INSERT INTO users (username, password_hash, email, full_name, phone, role, is_active) VALUES (:username, :password_hash, :email, :full_name, :phone, "admin", 1)');
            $adminStmt->execute([
                'username' => $adminUser,
                'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT),
                'email' => $adminEmail,
                'full_name' => 'Администратор',
                'phone' => 'N/A',
            ]);

            $settingStmt = $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (:key, :value)');
            $settingStmt->execute(['key' => 'app_name', 'value' => $appName]);
            $settingStmt->execute(['key' => 'logo_path', 'value' => 'assets/img/logo.svg']);

            $config = <<<CONFIG
<?php
return [
    'db' => [
        'host' => '{$dbHost}',
        'name' => '{$dbName}',
        'user' => '{$dbUser}',
        'pass' => '{$dbPass}',
        'charset' => 'utf8mb4',
    ],
    'base_url' => '',
    'app_name' => '{$appName}',
    'timezone' => 'Europe/Sofia',
];
CONFIG;

            file_put_contents(__DIR__ . '/config.php', $config);
            $success = true;
        } catch (PDOException $exception) {
            $errors[] = 'Грешка при свързване или създаване на базата: ' . $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Инсталация</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="install-page">
    <div class="install-wrapper">
        <div class="install-card">
            <h1>Инсталиране на системата</h1>
            <?php if ($success) : ?>
                <div class="alert success">Инсталацията е готова. <a href="index.php">Вход</a></div>
            <?php else : ?>
                <?php foreach ($errors as $error) : ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
                <form method="post" class="form">
                    <div class="form-grid">
                        <label>
                            DB Host
                            <input type="text" name="db_host" value="localhost" required>
                        </label>
                        <label>
                            DB Име
                            <input type="text" name="db_name" required>
                        </label>
                        <label>
                            DB Потребител
                            <input type="text" name="db_user" required>
                        </label>
                        <label>
                            DB Парола
                            <input type="password" name="db_pass">
                        </label>
                        <label>
                            Име на системата
                            <input type="text" name="app_name" value="Modern System" required>
                        </label>
                        <label>
                            Админ потребител
                            <input type="text" name="admin_user" value="admin" required>
                        </label>
                        <label>
                            Админ парола
                            <input type="password" name="admin_pass" required>
                        </label>
                        <label>
                            Админ имейл
                            <input type="email" name="admin_email" required>
                        </label>
                    </div>
                    <button class="button primary" type="submit">Инсталирай</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
