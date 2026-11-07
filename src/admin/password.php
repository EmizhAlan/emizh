<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;
use Emizh\Classes\Version;

Auth::requireLogin();

$username = Auth::currentUser();

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Смена пароля — emizh</title>
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">

<div class="admin-container admin-container-wide">

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
        <h1 class="admin-title">Смена пароля</h1>
        <p class="admin-subtitle">
            После смены пароля все сессии на других устройствах будут завершены.
        </p>

        <form method="post" action="password_save.php" class="admin-form">
            <?= csrfField() ?>

            <label class="admin-label">
                <span>Текущий пароль</span>
                <input type="password" name="current_password" required autocomplete="current-password" autofocus>
            </label>

            <label class="admin-label">
                <span>Новый пароль</span>
                <input type="password" name="new_password" required minlength="6" autocomplete="new-password">
            </label>

            <label class="admin-label">
                <span>Повторите новый пароль</span>
                <input type="password" name="new_password_repeat" required minlength="6" autocomplete="new-password">
            </label>

            <div class="admin-form-inline">
                <a href="dashboard.php" class="admin-btn admin-btn-secondary">Отмена</a>
                <button type="submit" class="admin-btn">Сменить пароль</button>
            </div>
        </form>
    </div>

    <div class="admin-footer">
        emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>

</body>
</html>