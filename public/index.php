<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/pdf.php';

$page = $_GET['page'] ?? 'dashboard';

if ($page === 'logout') { session_destroy(); header('Location: index.php?page=login'); exit; }

if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = db()->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$_POST['email'] ?? '']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid login credentials';
}

if ($page === 'api_toggle_theme') {
    $u = requireAuth();
    $theme = ($_POST['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
    db()->prepare('UPDATE users SET theme=? WHERE id=?')->execute([$theme, $u['id']]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

if ($page === 'api_add_event') {
    $u = requireAuth();
    $stmt = db()->prepare('INSERT INTO events(studio_id,user_id,client_id,title,event_type,location,event_date,start_time,end_time,price,notes,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$u['studio_id'], $u['id'], $_POST['client_id'] ?: null, $_POST['title'], $_POST['event_type'], $_POST['location'], $_POST['event_date'], $_POST['start_time'], $_POST['end_time'], $_POST['price'], $_POST['notes']]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

if ($page === 'offer_pdf') {
    $u = requireAuth();
    $stmt = db()->prepare('SELECT * FROM offers WHERE id=? AND studio_id=?');
    $stmt->execute([(int)($_GET['id'] ?? 0), $u['studio_id']]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$offer) { http_response_code(404); exit('Offer not found'); }
    $pdf = simpleOfferPdf($offer, $u['studio_name']);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="offer-'.$offer['id'].'.pdf"');
    echo $pdf;
    exit;
}

if ($page === 'offer_email' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = requireAuth();
    $id = (int)($_POST['offer_id'] ?? 0);
    $stmt = db()->prepare('SELECT o.*, c.email as client_email FROM offers o LEFT JOIN clients c ON c.id=o.client_id WHERE o.id=? AND o.studio_id=?');
    $stmt->execute([$id, $u['studio_id']]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($offer && $offer['client_email']) {
        @mail($offer['client_email'], 'Photo offer: '.$offer['title'], $offer['body']);
    }
    header('Location: index.php?page=offers');
    exit;
}

$user = $page === 'login' ? currentUser() : requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'users' && isAdmin($user)) {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    db()->prepare('INSERT INTO users(studio_id,name,email,password_hash,role,theme,active_until,created_at) VALUES(?,?,?,?,?,?,?,NOW())')
        ->execute([$user['studio_id'], $_POST['name'], $_POST['email'], $hash, $_POST['role'], 'light', $_POST['active_until']]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'settings' && isAdmin($user)) {
    if (!empty($_FILES['logo']['tmp_name'])) {
        $path = '../views/uploads/logo_' . $user['studio_id'] . '.png';
        move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/' . $path);
        db()->prepare('UPDATE studios SET logo_path=? WHERE id=?')->execute([$path, $user['studio_id']]);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'clients') {
    db()->prepare('INSERT INTO clients(studio_id,name,email,phone,notes,created_at) VALUES(?,?,?,?,?,NOW())')
        ->execute([$user['studio_id'], $_POST['name'], $_POST['email'], $_POST['phone'], $_POST['notes']]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'finances') {
    db()->prepare('INSERT INTO finances(studio_id,user_id,kind,category,amount,entry_date,description,created_at) VALUES(?,?,?,?,?,?,?,NOW())')
        ->execute([$user['studio_id'], $user['id'], $_POST['kind'], $_POST['category'], $_POST['amount'], $_POST['entry_date'], $_POST['description']]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'categories') {
    db()->prepare('INSERT INTO service_categories(studio_id,name) VALUES(?,?)')->execute([$user['studio_id'], $_POST['name']]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'offers') {
    db()->prepare('INSERT INTO offers(studio_id,client_id,title,body,total_amount,valid_until,created_by,created_at) VALUES(?,?,?,?,?,?,?,NOW())')
        ->execute([$user['studio_id'], $_POST['client_id'] ?: null, $_POST['title'], $_POST['body'], $_POST['total_amount'], $_POST['valid_until'], $user['id']]);
}

$logo = $user['logo_path'] ?? null;
?><!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>PhotoCalendar</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="theme-<?= e($user['theme'] ?? 'light') ?>">
<?php if ($page === 'login'): ?>
<div class="login-wrap"><div class="card"><?php if ($logo): ?><img src="<?= e($logo) ?>" class="logo"><?php endif; ?><h1>PhotoCalendar</h1><?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="email" name="email" placeholder="Email" required><input type="password" name="password" placeholder="Password" required><button>Login</button></form></div></div>
<?php else: ?>
<nav><strong>PhotoCalendar v2.4</strong><span><?= e($user['studio_name']) ?></span>
<a href="?">Dashboard</a><a href="?page=calendar">Calendar</a><a href="?page=clients">Clients</a><a href="?page=finances">Finances</a><a href="?page=categories">Categories</a><a href="?page=offers">Offers</a><?php if (isAdmin($user)): ?><a href="?page=users">Users</a><a href="?page=settings">Settings</a><?php endif; ?><a href="?page=logout">Logout</a>
<button id="themeBtn">Theme</button></nav>
<main>
<?php
if ($page === 'dashboard' || $page === '') {
    $st = db()->prepare('SELECT COUNT(*) c FROM events WHERE studio_id=?'); $st->execute([$user['studio_id']]); $eventsCount = $st->fetchColumn();
    $st = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM finances WHERE studio_id=? AND kind="income"'); $st->execute([$user['studio_id']]); $income = $st->fetchColumn();
    $st = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM finances WHERE studio_id=? AND kind="expense"'); $st->execute([$user['studio_id']]); $expense = $st->fetchColumn();
    echo "<h2>Dashboard</h2><div class='stats'><div class='card'>Events: {$eventsCount}</div><div class='card'>Income: {$income} BGN</div><div class='card'>Expenses: {$expense} BGN</div></div>";
    echo "<canvas id='finChart'></canvas><script>new Chart(document.getElementById('finChart'),{type:'bar',data:{labels:['Income','Expenses'],datasets:[{data:[{$income},{$expense}],backgroundColor:['#2ecc71','#e74c3c']}]}})</script>";
}
if ($page === 'calendar') {
    $clients = db()->prepare('SELECT id,name FROM clients WHERE studio_id=?'); $clients->execute([$user['studio_id']]); $cl = $clients->fetchAll(PDO::FETCH_ASSOC);
    $events = db()->prepare('SELECT * FROM events WHERE studio_id=? AND user_id=? ORDER BY event_date,start_time'); $events->execute([$user['studio_id'], isAdmin($user)&&isset($_GET['all'])? $user['id'] : $user['id']]);
    echo "<h2>My Calendar</h2><form id='eventForm' class='grid'>";
    echo "<input name='title' placeholder='Event title' required><input name='event_type' placeholder='Type' required><input name='location' placeholder='Location'><input type='date' name='event_date' required><input type='time' name='start_time' required><input type='time' name='end_time' required><input type='number' step='0.01' name='price' placeholder='Price'><select name='client_id'><option value=''>Client</option>";
    foreach($cl as $c) echo "<option value='{$c['id']}'>".e($c['name'])."</option>";
    echo "</select><textarea name='notes' placeholder='Notes'></textarea><button>Add Event</button></form><div id='eventMsg'></div><table><tr><th>Date</th><th>Time</th><th>Title</th><th>Price</th></tr>";
    foreach($events as $ev) echo '<tr><td>'.e($ev['event_date']).'</td><td>'.e(substr($ev['start_time'],0,5)).' - '.e(substr($ev['end_time'],0,5)).'</td><td>'.e($ev['title']).'</td><td>'.e($ev['price']).'</td></tr>';
    echo '</table>';
}
if ($page === 'clients') {
    $clients = db()->prepare('SELECT * FROM clients WHERE studio_id=? ORDER BY id DESC'); $clients->execute([$user['studio_id']]);
    echo "<h2>Clients</h2><form method='post' class='grid'><input name='name' placeholder='Name' required><input name='email' placeholder='Email'><input name='phone' placeholder='Phone'><textarea name='notes' placeholder='Notes'></textarea><button>Save client</button></form><table><tr><th>Name</th><th>Contact</th></tr>";
    foreach($clients as $c) echo '<tr><td>'.e($c['name']).'</td><td>'.e(($c['email']??'').' '.$c['phone']).'</td></tr>';
    echo '</table>';
}
if ($page === 'finances') {
    $rows = db()->prepare('SELECT * FROM finances WHERE studio_id=? ORDER BY entry_date DESC'); $rows->execute([$user['studio_id']]);
    echo "<h2>Income & Expenses</h2><form method='post' class='grid'><select name='kind'><option value='income'>Income</option><option value='expense'>Expense</option></select><input name='category' placeholder='Category' required><input type='number' step='0.01' name='amount' placeholder='Amount' required><input type='date' name='entry_date' required><textarea name='description' placeholder='Description'></textarea><button>Save entry</button></form><table><tr><th>Date</th><th>Kind</th><th>Category</th><th>Amount</th></tr>";
    foreach($rows as $r) echo '<tr><td>'.e($r['entry_date']).'</td><td>'.e($r['kind']).'</td><td>'.e($r['category']).'</td><td>'.e($r['amount']).'</td></tr>';
    echo '</table>';
}
if ($page === 'categories') {
    $rows = db()->prepare('SELECT * FROM service_categories WHERE studio_id=?'); $rows->execute([$user['studio_id']]);
    echo "<h2>Service Categories</h2><form method='post'><input name='name' placeholder='Category name' required><button>Add</button></form><ul>";
    foreach($rows as $r) echo '<li>'.e($r['name']).'</li>'; echo '</ul>';
}
if ($page === 'offers') {
    $clients = db()->prepare('SELECT id,name FROM clients WHERE studio_id=?'); $clients->execute([$user['studio_id']]); $cl = $clients->fetchAll(PDO::FETCH_ASSOC);
    $rows = db()->prepare('SELECT * FROM offers WHERE studio_id=? ORDER BY id DESC'); $rows->execute([$user['studio_id']]);
    echo "<h2>Offers</h2><form method='post' class='grid'><input name='title' placeholder='Offer title' required><select name='client_id'><option value=''>Select client</option>";
    foreach($cl as $c) echo "<option value='{$c['id']}'>".e($c['name'])."</option>";
    echo "</select><input type='number' step='0.01' name='total_amount' placeholder='Total'><input type='date' name='valid_until'><textarea name='body' placeholder='Offer details'></textarea><button>Create offer</button></form><table><tr><th>Title</th><th>Total</th><th>Actions</th></tr>";
    foreach($rows as $r) echo '<tr><td>'.e($r['title']).'</td><td>'.e($r['total_amount']).' BGN</td><td><a href="?page=offer_pdf&id='.$r['id'].'">PDF</a> <form method="post" action="?page=offer_email" style="display:inline"><input type="hidden" name="offer_id" value="'.$r['id'].'"><button>Email</button></form></td></tr>';
    echo '</table>';
}
if ($page === 'users' && isAdmin($user)) {
    $rows = db()->prepare('SELECT * FROM users WHERE studio_id=?'); $rows->execute([$user['studio_id']]);
    echo "<h2>User access</h2><form method='post' class='grid'><input name='name' placeholder='Name' required><input type='email' name='email' placeholder='Email' required><input type='password' name='password' placeholder='Password' required><select name='role'><option value='user'>User</option><option value='admin'>Admin</option></select><input type='datetime-local' name='active_until' required><button>Add user</button></form><table><tr><th>Name</th><th>Role</th><th>Active until</th></tr>";
    foreach($rows as $r) echo '<tr><td>'.e($r['name']).'</td><td>'.e($r['role']).'</td><td>'.e($r['active_until']).'</td></tr>'; echo '</table>';
}
if ($page === 'settings' && isAdmin($user)) {
    echo "<h2>Studio settings</h2><form method='post' enctype='multipart/form-data'><input type='file' name='logo' accept='image/*'><button>Upload logo</button></form>";
}
?>
</main>
<script src="../assets/js/app.js"></script>
<?php endif; ?>
</body></html>
