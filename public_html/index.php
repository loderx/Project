<?php
require __DIR__ . '/bootstrap.php';

if (!is_installed()) {
    header('Location: install.php');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';

if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                flash('error', 'Профилът е деактивиран.');
            } else {
                $_SESSION['user_id'] = $user['id'];
                header('Location: index.php');
                exit;
            }
        } else {
            flash('error', 'Невалидни данни за вход.');
        }
    }

    $appName = setting('app_name', 'Modern System');
    $logoPath = setting('logo_path', 'assets/img/logo.svg');
    $flashes = get_flashes();
    $view = 'login.php';
    include __DIR__ . '/pages/login_layout.php';
    exit;
}

if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

require_login();

switch ($page) {
    case 'dashboard':
        render('dashboard.php', [
            'title' => 'Табло',
            'subtitle' => 'Преглед на системата',
            'page' => $page,
        ]);
        break;
    case 'admin_users':
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_POST['action'] === 'create') {
                $subscriptionDays = max(1, (int) ($_POST['subscription_days'] ?? 1));
                $subscriptionEnd = (new DateTime())->modify('+' . $subscriptionDays . ' days')->format('Y-m-d');
                $stmt = db()->prepare('INSERT INTO users (username, password_hash, email, full_name, phone, role, subscription_end, is_active) VALUES (:username, :password_hash, :email, :full_name, :phone, :role, :subscription_end, 1)');
                $stmt->execute([
                    'username' => trim($_POST['username']),
                    'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'email' => trim($_POST['email']),
                    'full_name' => trim($_POST['full_name']),
                    'phone' => trim($_POST['phone']),
                    'role' => $_POST['role'] === 'admin' ? 'admin' : 'user',
                    'subscription_end' => $subscriptionEnd,
                ]);
                flash('success', 'Потребителят е добавен.');
            }
            if ($_POST['action'] === 'toggle') {
                $userId = (int) $_POST['user_id'];
                $stmt = db()->prepare('UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id AND role != "admin"');
                $stmt->execute(['id' => $userId]);
                flash('success', 'Статусът е обновен.');
            }
            header('Location: index.php?page=admin_users');
            exit;
        }
        $users = db()->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
        render('admin_users.php', [
            'title' => 'Потребители',
            'subtitle' => 'Управление на достъпа',
            'page' => $page,
            'users' => $users,
        ]);
        break;
    case 'admin_modules':
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $slug = $_POST['slug'] ?? '';
            $modules = available_modules();
            if ($_POST['action'] === 'activate' && isset($modules[$slug])) {
                activate_module($modules[$slug]);
                flash('success', 'Модулът е активиран.');
            }
            if ($_POST['action'] === 'deactivate') {
                deactivate_module($slug);
                flash('success', 'Модулът е деактивиран.');
            }
            header('Location: index.php?page=admin_modules');
            exit;
        }
        $moduleList = [];
        foreach (available_modules() as $module) {
            $module['active'] = module_is_active($module['slug']);
            $moduleList[] = $module;
        }
        render('admin_modules.php', [
            'title' => 'Модули',
            'subtitle' => 'Активирайте или деактивирайте функционалности',
            'page' => $page,
            'moduleList' => $moduleList,
        ]);
        break;
    case 'admin_settings':
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['app_name']);
            $stmt = db()->prepare('UPDATE settings SET value = :value WHERE `key` = "app_name"');
            $stmt->execute(['value' => $name]);

            if (!empty($_FILES['logo']['tmp_name'])) {
                $target = __DIR__ . '/uploads/logo_' . time() . '.png';
                move_uploaded_file($_FILES['logo']['tmp_name'], $target);
                $relative = 'uploads/' . basename($target);
                $stmt = db()->prepare('UPDATE settings SET value = :value WHERE `key` = "logo_path"');
                $stmt->execute(['value' => $relative]);
            }
            flash('success', 'Настройките са запазени.');
            header('Location: index.php?page=admin_settings');
            exit;
        }
        render('admin_settings.php', [
            'title' => 'Настройки',
            'subtitle' => 'Персонализирайте системата',
            'page' => $page,
        ]);
        break;
    case 'module':
        $slug = $_GET['slug'] ?? '';
        $modules = available_modules();
        if (!isset($modules[$slug]) || !module_is_active($slug)) {
            http_response_code(404);
            echo 'Module not found.';
            exit;
        }
        $module = $modules[$slug];
        render('module.php', [
            'title' => $module['menu_label'],
            'subtitle' => $module['description'] ?? '',
            'page' => $page,
            'module' => $module,
        ]);
        break;
    default:
        http_response_code(404);
        echo 'Page not found.';
}
