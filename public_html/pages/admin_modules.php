<div class="card">
    <h3>Модули</h3>
    <p>Поставете нов модул в папка <code>public_html/modules</code>, за да се появи тук.</p>
</div>
<div class="modules-grid">
    <?php foreach ($moduleList as $module) : ?>
        <div class="card">
            <h4><?= htmlspecialchars($module['name']) ?></h4>
            <p><?= htmlspecialchars($module['description'] ?? '') ?></p>
            <div class="module-meta">
                <span>Slug: <?= htmlspecialchars($module['slug']) ?></span>
                <span>Версия: <?= htmlspecialchars($module['version'] ?? '1.0') ?></span>
            </div>
            <form method="post" action="index.php?page=admin_modules">
                <input type="hidden" name="slug" value="<?= htmlspecialchars($module['slug']) ?>">
                <button type="submit" class="button <?= $module['active'] ? 'ghost' : 'primary' ?>" name="action" value="<?= $module['active'] ? 'deactivate' : 'activate' ?>">
                    <?= $module['active'] ? 'Деактивирай' : 'Активирай' ?>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
