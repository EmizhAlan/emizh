<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Version;

// Защита: если не залогинен — редирект на главную админки
Auth::requireLogin();

$username = Auth::currentUser();

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления — emizh</title>
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">

<div class="admin-container admin-container-wide">

    <div class="admin-topbar">
        <div class="admin-topbar-title">Панель управления</div>
        <div class="admin-topbar-user">
            <?= htmlspecialchars((string)$username, ENT_QUOTES, 'UTF-8') ?>
            <form method="post" action="logout.php" style="display:inline">
                <?= csrfField() ?>
                <button type="submit" class="admin-btn admin-btn-small admin-btn-secondary">Выйти</button>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <h1 class="admin-title">Панель управления</h1>
        <p class="admin-subtitle">
            Эта страница доступна только владельцу. Все последующие админские страницы
            будут устроены так же: сначала <code>_init.php</code>, затем <code>Auth::requireLogin()</code>,
            затем обычная логика.
        </p>

        <p>В следующей версии здесь появится список страниц сайта.</p>
    </div>

    <div class="admin-footer">
        emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>

</body>
</html>