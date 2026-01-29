<?php
$user = current_user();
$stmt = db()->prepare('SELECT * FROM example_items WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute(['user_id' => $user['id']]);
$items = $stmt->fetchAll();
?>
<div class="card">
    <h4>Вашите примерни записи</h4>
    <?php if (!$items) : ?>
        <p>Все още няма записи.</p>
    <?php else : ?>
        <ul>
            <?php foreach ($items as $item) : ?>
                <li><?= htmlspecialchars($item['title']) ?> (<?= htmlspecialchars($item['created_at']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
