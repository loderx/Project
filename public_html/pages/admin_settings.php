<form method="post" class="form" enctype="multipart/form-data" action="index.php?page=admin_settings">
    <label>
        Име на системата
        <input type="text" name="app_name" value="<?= htmlspecialchars($appName) ?>" required>
    </label>
    <label>
        Лого
        <input type="file" name="logo" accept="image/*">
    </label>
    <button type="submit" class="button primary">Запази</button>
</form>
<div class="card">
    <h3>Текущо лого</h3>
    <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" class="preview-logo">
</div>
