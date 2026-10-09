<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Version;

$ownerExists = Auth::ownerExists();
$loggedIn = Auth::isLoggedIn();
$username = Auth::currentUser();

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка — emizh</title>
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">

<div class="admin-container">

    <?php if ($flashError !== null): ?>
        <div class="admin-alert admin-alert-error"><?= htmlspecialchars((string)$flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($flashSuccess !== null): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars((string)$flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$ownerExists): ?>

        <div class="admin-card">
            <h1 class="admin-title">Первый запуск</h1>
            <p class="admin-subtitle">Создайте владельца сайта. Логин и пароль сохранятся навсегда — их нельзя будет изменить через панель управления.</p>

            <form method="post" action="register.php" class="admin-form">
                <label class="admin-label">
                    <span>Логин</span>
                    <input type="text" name="username" required minlength="3" maxlength="32" autocomplete="username" autofocus>
                </label>

                <label class="admin-label">
                    <span>Пароль</span>
                    <input type="password" name="password" required minlength="6" autocomplete="new-password">
                </label>

                <label class="admin-label">
                    <span>Повторите пароль</span>
                    <input type="password" name="password_repeat" required minlength="6" autocomplete="new-password">
                </label>

                <button type="submit" class="admin-btn">Создать владельца</button>
            </form>
        </div>

    <?php elseif (!$loggedIn): ?>

        <div class="admin-card">
            <h1 class="admin-title">Вход в админку</h1>
            <p class="admin-subtitle">Введите логин и пароль владельца сайта.</p>

            <form method="post" action="login.php" class="admin-form">
                <label class="admin-label">
                    <span>Логин</span>
                    <input type="text" name="username" required autocomplete="username" autofocus>
                </label>

                <label class="admin-label">
                    <span>Пароль</span>
                    <input type="password" name="password" required autocomplete="current-password">
                </label>

                <button type="submit" class="admin-btn">Войти</button>
            </form>
        </div>

    <?php else: ?>

        <div class="admin-card">
            <h1 class="admin-title">Вы вошли</h1>
            <p class="admin-subtitle">
                Логин: <strong><?= htmlspecialchars((string)$username, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>

            <p>Редактирование страниц будет добавлено в следующей версии.</p>

            <form method="post" action="logout.php" class="admin-form-inline">
                <a href="../" class="admin-btn admin-btn-secondary">На сайт</a>
                <a href="dashboard.php" class="admin-btn">Открыть панель</a>
            </form>
        </div>

    <?php endif; ?>

    <div class="admin-footer">
        emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>

</body>
</html>