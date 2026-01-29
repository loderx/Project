<?php
$moduleDir = __DIR__ . '/../modules/' . $module['path'];
$entry = $moduleDir . '/index.php';
?>
<div class="card">
    <h3><?= htmlspecialchars($module['name']) ?></h3>
    <p><?= htmlspecialchars($module['description'] ?? '') ?></p>
</div>
<?php if (is_file($entry)) : ?>
    <?php include $entry; ?>
<?php else : ?>
    <div class="card">
        <p>Този модул няма визуална част. Добавете <code>index.php</code> в модула.</p>
    </div>
<?php endif; ?>
