<?php
$configPath = dirname(__DIR__) . '/config.php';
if (!file_exists($configPath)) {
    header('Location: ../installer.php');
    exit;
}
$config = require $configPath;

function db(): PDO {
    static $pdo = null;
    global $config;
    if ($pdo === null) {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['name']),
            $config['db']['user'],
            $config['db']['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}

function currentUser(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT u.*, s.name as studio_name, s.logo_path FROM users u JOIN studios s ON s.id=u.studio_id WHERE u.id=?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || ($user['active_until'] && strtotime($user['active_until']) < time())) {
        session_destroy();
        return null;
    }
    return $user;
}

function requireAuth(): array {
    $u = currentUser();
    if (!$u) {
        header('Location: index.php?page=login');
        exit;
    }
    return $u;
}

function isAdmin(array $u): bool { return $u['role'] === 'admin'; }

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
