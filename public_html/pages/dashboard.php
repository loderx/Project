<div class="cards">
    <div class="card">
        <h3>Статус на профила</h3>
        <p><?= $user['is_active'] ? 'Активен' : 'Деактивиран' ?></p>
        <?php if ($user['subscription_end']) : ?>
            <small>Абонамент до: <?= htmlspecialchars($user['subscription_end']) ?></small>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3>Модули</h3>
        <p>Активни: <?= count($modules) ?></p>
        <small>Управлявайте модулите от администрацията.</small>
    </div>
</div>
<div class="card large">
    <h3>Добре дошли обратно</h3>
    <p>Това е вашият персонализиран контролен панел. Всички настройки са достъпни според ролята ви.</p>
</div>
