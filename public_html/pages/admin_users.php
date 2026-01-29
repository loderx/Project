<div class="split">
    <div>
        <h3>Съществуващи потребители</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Име</th>
                    <th>Потребител</th>
                    <th>Имейл</th>
                    <th>Телефон</th>
                    <th>Роля</th>
                    <th>Абонамент</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $member) : ?>
                    <tr>
                        <td><?= htmlspecialchars($member['full_name']) ?></td>
                        <td><?= htmlspecialchars($member['username']) ?></td>
                        <td><?= htmlspecialchars($member['email']) ?></td>
                        <td><?= htmlspecialchars($member['phone']) ?></td>
                        <td><?= htmlspecialchars($member['role']) ?></td>
                        <td><?= htmlspecialchars($member['subscription_end'] ?? '-') ?></td>
                        <td><?= $member['is_active'] ? 'Активен' : 'Неактивен' ?></td>
                        <td>
                            <?php if ($member['role'] !== 'admin') : ?>
                                <form method="post" class="inline" action="index.php?page=admin_users">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="user_id" value="<?= (int) $member['id'] ?>">
                                    <button class="button ghost" type="submit"><?= $member['is_active'] ? 'Деактивирай' : 'Активирай' ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <h3>Добави потребител</h3>
        <form method="post" class="form" action="index.php?page=admin_users">
            <input type="hidden" name="action" value="create">
            <label>
                Потребителско име
                <input type="text" name="username" required>
            </label>
            <label>
                Парола
                <input type="password" name="password" required>
            </label>
            <label>
                Имейл
                <input type="email" name="email" required>
            </label>
            <label>
                Имена
                <input type="text" name="full_name" required>
            </label>
            <label>
                Телефон
                <input type="text" name="phone" required>
            </label>
            <label>
                Абонамент в дни
                <input type="number" name="subscription_days" min="1" value="90" required>
            </label>
            <label>
                Роля
                <select name="role">
                    <option value="user">Потребител</option>
                    <option value="admin">Администратор</option>
                </select>
            </label>
            <button type="submit" class="button primary">Запази</button>
        </form>
    </div>
</div>
