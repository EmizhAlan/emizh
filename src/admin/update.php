<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Version;

Auth::requireLogin();

$username = Auth::currentUser();

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обновление — emizh</title>
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">

<div class="admin-container">

    <div class="admin-topbar">
        <div class="admin-topbar-title">
            <a href="dashboard.php" class="admin-back">← Панель управления</a>
        </div>
        <div class="admin-topbar-user">
            <?= htmlspecialchars((string)$username, ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <?php if ($flashError !== null): ?>
        <div class="admin-alert admin-alert-error"><?= htmlspecialchars((string)$flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($flashSuccess !== null): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars((string)$flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="admin-card">
        <h1 class="admin-title">Обновление системы</h1>
        <p class="admin-subtitle">
            Текущая версия: <strong>emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?></strong>
        </p>

        <div class="admin-alert admin-alert-error" style="margin-bottom: 24px;">
            <strong>Внимание:</strong> Перед обновлением обязательно сделайте резервную копию
            папки <code>data/</code> и файла <code>owner.json</code>.
            Вы отвечаете за сохранность своих данных.
        </div>

        <form method="post" action="update_save.php" enctype="multipart/form-data" class="admin-form">
            <?= csrfField() ?>

            <label class="admin-label">
                <span>Архив с новой версией (.zip)</span>
                <input type="file" name="archive" accept=".zip" required>
            </label>

            <p class="admin-subtitle" style="margin-top: 8px;">
                Загружайте только официальные архивы <code>emizh-vX.Y.Z-dist.zip</code> из репозитория проекта.
            </p>

            <div class="admin-form-inline" style="margin-top: 24px;">
                <a href="dashboard.php" class="admin-btn admin-btn-secondary">Отмена</a>
                <button type="submit" class="admin-btn admin-btn-danger">Обновить и перезаписать</button>
            </div>
        </form>
    </div>

    <div class="admin-footer">
        emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>

</body>
</html>