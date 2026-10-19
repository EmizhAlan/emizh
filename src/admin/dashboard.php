<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Site;
use Emizh\Classes\Version;

Auth::requireLogin();

$username = Auth::currentUser();

// Загружаем сайт и все страницы
$site = Site::load();
$pages = $site->pages(); // ['slug' => Page]

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
            <a href="password.php" class="admin-btn admin-btn-small admin-btn-secondary">Сменить пароль</a>
            <form method="post" action="logout.php" style="display:inline">
                <?= csrfField() ?>
                <button type="submit" class="admin-btn admin-btn-small admin-btn-secondary">Выйти</button>
            </form>
        </div>
    </div>

    <?php if ($flashError !== null): ?>
        <div class="admin-alert admin-alert-error"><?= htmlspecialchars((string)$flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($flashSuccess !== null): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars((string)$flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h1 class="admin-title">Страницы</h1>
                <p class="admin-subtitle">
                    <?php if ($pages === []): ?>
                        На сайте пока нет ни одной страницы.
                    <?php else: ?>
                        Всего страниц: <strong><?= count($pages) ?></strong>.
                    <?php endif; ?>
                </p>
            </div>
            <div class="admin-card-actions">
                <a href="new.php" class="admin-btn">Создать страницу</a>
            </div>
        </div>

        <?php if ($pages !== []): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Заголовок</th>
                        <th>Адрес</th>
                        <th>Обновлена</th>
                        <th class="admin-table-actions">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $slug => $page): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($page->title(), ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if ($slug === $site->homePage()): ?>
                                    <span class="admin-badge">главная</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code>/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></code>
                            </td>
                            <td class="admin-table-muted">
                                <?= htmlspecialchars(self_formatDate($page->updated()), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="admin-table-actions">
                                <a href="edit.php?slug=<?= urlencode($slug) ?>" class="admin-btn admin-btn-small admin-btn-secondary">Редактировать</a>
                                <a href="delete.php?slug=<?= urlencode($slug) ?>" class="admin-btn admin-btn-small admin-btn-secondary">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="admin-footer">
        emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>

</body>
</html>