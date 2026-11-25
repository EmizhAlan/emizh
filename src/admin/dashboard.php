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
            <a href="update.php" class="admin-btn admin-btn-small admin-btn-secondary">Обновление</a>
            <a href="settings.php" class="admin-btn admin-btn-small admin-btn-secondary">Настройки сайта</a>
            <a href="../" target="_blank" class="admin-btn admin-btn-small admin-btn-secondary">Открыть сайт</a>
            <form method="post" action="logout.php" style="display:inline">
                <?= csrfField() ?>
                <button type="submit" class="admin-btn admin-btn-small admin-btn-secondary">Выйти</button>
            </form>
        </div>
    </div>

    <?= adminFlash() ?>

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

        <?php if ($pages === []): ?>

            <div class="admin-empty">
                <p>Пока пусто. Создайте первую страницу, чтобы начать.</p>
                <a href="new.php" class="admin-btn">Создать первую страницу</a>
            </div>

        <?php else: ?>

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
                                <?php
                                $allSlugs = array_keys($pages);
                                $pageIndex = array_search($slug, $allSlugs, true);
                                $isFirst = ($pageIndex === 0);
                                $isLast = ($pageIndex === count($allSlugs) - 1);
                                $isHome = ($slug === $site->homePage());
                                ?>
                                
                                <div class="admin-table-actions-group">
                                    <!-- Порядок -->
                                    <div class="admin-table-order">
                                        <form method="post" action="reorder.php" class="admin-order-form">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="up">
                                            <button type="submit" class="admin-btn-icon" title="Вверх" <?= $isFirst ? 'disabled' : '' ?>>↑</button>
                                        </form>
                                        <form method="post" action="reorder.php" class="admin-order-form">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="down">
                                            <button type="submit" class="admin-btn-icon" title="Вниз" <?= $isLast ? 'disabled' : '' ?>>↓</button>
                                        </form>
                                    </div>

                                    <!-- Главная -->
                                    <?php if (!$isHome): ?>
                                        <form method="post" action="reorder.php" class="admin-order-form">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="make_home">
                                            <button type="submit" class="admin-btn admin-btn-small admin-btn-secondary" title="Назначить главной">Сделать главной</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Редактировать и удалить -->
                                    <a href="edit.php?slug=<?= urlencode($slug) ?>" class="admin-btn admin-btn-small admin-btn-secondary">Редактировать</a>
                                    <a href="delete.php?slug=<?= urlencode($slug) ?>"
                                       class="admin-btn admin-btn-small admin-btn-danger"
                                       data-confirm="#admin-confirm-modal"
                                       data-confirm-title="Удалить страницу?"
                                       data-confirm-message="Страница «<?= htmlspecialchars($page->title(), ENT_QUOTES, 'UTF-8') ?>» (адрес /<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>) будет удалена навсегда. Действие нельзя отменить."
                                       <?= $isHome ? 'data-confirm-warning="Это главная страница сайта. После удаления главной станет другая страница (если они есть)."' : '' ?>
                                       data-confirm-submit="Удалить навсегда">Удалить</a>
                                    <!-- Добавь это рядом с кнопкой "Сделать главной" -->
                                    <form method="post" action="toggle_nav.php" class="admin-order-form">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="admin-btn admin-btn-small admin-btn-secondary" title="<?= $page->showInNav() ? 'Скрыть из меню' : 'Показать в меню' ?>">
                                            <?= $page->showInNav() ? 'В меню ✓' : 'В меню ✗' ?>
                                        </button>
                                    </form>
                                </div>
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

<!-- Модалка подтверждения удаления -->
<div class="admin-modal" id="admin-confirm-modal" hidden role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title">
    <div class="admin-modal-backdrop" data-modal-close></div>
    <div class="admin-modal-dialog">
        <h2 class="admin-modal-title" id="admin-confirm-title">Удалить страницу?</h2>
        <div class="admin-modal-body">
            <p data-confirm-message></p>
            <div class="admin-alert admin-alert-error" data-confirm-warning hidden></div>
        </div>
        <form method="post" action="delete.php" data-confirm-form>
            <?= csrfField() ?>
            <input type="hidden" name="slug" value="">
            <div class="admin-modal-actions">
                <button type="button" class="admin-btn admin-btn-secondary" data-modal-close>Отмена</button>
                <button type="button" class="admin-btn admin-btn-danger" data-confirm-submit-btn>Удалить навсегда</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/admin-ui.js"></script>

</body>
</html>