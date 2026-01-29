<?php
session_start();

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/config.sample.php';
}

$config = require $configFile;

date_default_timezone_set($config['timezone'] ?? 'Europe/Sofia');

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset']);
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function is_installed(): bool
{
    return file_exists(__DIR__ . '/config.php');
}

function setting(string $key, $default = null)
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = :key');
    $stmt->execute(['key' => $key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    if ($user['role'] !== 'admin' && $user['subscription_end'] && strtotime($user['subscription_end']) < time()) {
        $stmt = db()->prepare('UPDATE users SET is_active = 0 WHERE id = :id');
        $stmt->execute(['id' => $user['id']]);
        return null;
    }

    if (!$user['is_active']) {
        return null;
    }

    return $user;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

function is_mobile(): bool
{
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $agent) === 1;
}

function active_modules(): array
{
    $stmt = db()->query('SELECT * FROM modules WHERE is_active = 1 ORDER BY name');
    return $stmt->fetchAll();
}

function available_modules(): array
{
    $modules = [];
    $dir = __DIR__ . '/modules';
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $manifest = $dir . '/' . $entry . '/manifest.json';
        if (is_file($manifest)) {
            $data = json_decode(file_get_contents($manifest), true);
            if ($data) {
                $data['path'] = $entry;
                $modules[$data['slug']] = $data;
            }
        }
    }
    return $modules;
}

function module_is_active(string $slug): bool
{
    $stmt = db()->prepare('SELECT is_active FROM modules WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch();
    return $row ? (bool) $row['is_active'] : false;
}

function activate_module(array $module): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM modules WHERE slug = :slug');
    $stmt->execute(['slug' => $module['slug']]);
    $exists = $stmt->fetch();

    if ($exists) {
        $update = $pdo->prepare('UPDATE modules SET is_active = 1, name = :name, menu_label = :menu_label, menu_path = :menu_path WHERE slug = :slug');
        $update->execute([
            'slug' => $module['slug'],
            'name' => $module['name'],
            'menu_label' => $module['menu_label'],
            'menu_path' => $module['menu_path'],
        ]);
    } else {
        $insert = $pdo->prepare('INSERT INTO modules (slug, name, menu_label, menu_path, is_active) VALUES (:slug, :name, :menu_label, :menu_path, 1)');
        $insert->execute([
            'slug' => $module['slug'],
            'name' => $module['name'],
            'menu_label' => $module['menu_label'],
            'menu_path' => $module['menu_path'],
        ]);
    }

    $migration = __DIR__ . '/modules/' . $module['path'] . '/migrate.php';
    if (is_file($migration)) {
        require $migration;
    }
}

function deactivate_module(string $slug): void
{
    $stmt = db()->prepare('UPDATE modules SET is_active = 0 WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
}

function base_url(): string
{
    $config = require __DIR__ . '/config.php';
    return rtrim($config['base_url'] ?? '', '/');
}

function render(string $view, array $params = []): void
{
    extract($params);
    $flashes = get_flashes();
    $user = current_user();
    $appName = setting('app_name', 'Modern System');
    $logoPath = setting('logo_path', 'assets/img/logo.svg');
    $isMobile = is_mobile();
    $modules = active_modules();

    include __DIR__ . '/pages/layout.php';
}
