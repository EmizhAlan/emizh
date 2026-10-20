<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Site;
use Emizh\Classes\Page;
use Emizh\Classes\Version;

Auth::requireLogin();

$username = Auth::currentUser();
$slug = trim((string)($_GET['slug'] ?? ''));

if ($slug === '' || !Page::isValidSlug($slug)) {
    $_SESSION['flash_error'] = 'Некорректный адрес страницы.';
    adminRedirect('dashboard.php');
}

$page = Page::load($slug);

if ($page === null) {
    $_SESSION['flash_error'] = 'Страница не найдена.';
    adminRedirect('dashboard.php');
}

$site = Site::load();

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование: <?= htmlspecialchars($page->title(), ENT_QUOTES, 'UTF-8') ?> — emizh</title>
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

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h1 class="admin-title">Редактирование страницы</h1>
                <p class="admin-subtitle">
                    Адрес: <code>/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></code>
                    <?php if ($slug === $site->homePage()): ?>
                        <span class="admin-badge">главная</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="admin-card-actions">
                <a href="../?page=<?= urlencode($slug) ?>" target="_blank" class="admin-btn admin-btn-small admin-btn-secondary">Посмотреть на сайте</a>
                <a href="dashboard.php" class="admin-btn admin-btn-small admin-btn-secondary">Отмена</a>
            </div>
        </div>

        <form method="post" action="save.php" class="admin-editor-form" id="editor-form">
            <?= csrfField() ?>
            <input type="hidden" name="original_slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">

            <div class="admin-editor-meta">

                <label class="admin-checkbox">
                    <input
                        type="checkbox"
                        name="show_in_nav"
                        value="1"
                        <?= $page->showInNav() ? 'checked' : '' ?>
                    >
                    <span>Показывать в навигации сайта</span>
                </label>

                <label class="admin-label">
                    <span>Заголовок</span>
                    <input
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars($page->title(), ENT_QUOTES, 'UTF-8') ?>"
                        required
                        maxlength="200"
                        autocomplete="off"
                    >
                </label>

                <label class="admin-label">
                    <span>Адрес (слаг)</span>
                    <input
                        type="text"
                        name="slug"
                        value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"
                        required
                        maxlength="64"
                        pattern="[a-z0-9-]{1,64}"
                        autocomplete="off"
                        title="Только строчные латинские буквы, цифры и дефис"
                    >
                </label>
            </div>

            <div class="admin-editor-split">
                <div class="admin-editor-pane">
                    <label class="admin-label">
                        <span>Содержание (markdown)</span>
                        <textarea
                            name="content"
                            id="editor-content"
                            required
                            spellcheck="false"
                        ><?= htmlspecialchars($page->content(), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                </div>
                <div class="admin-editor-pane admin-editor-preview">
                    <div class="admin-label">
                        <span>Предпросмотр</span>
                    </div>
                    <div class="admin-editor-preview-body markdown" id="editor-preview"></div>
                </div>
            </div>

            <div class="admin-editor-actions">
                <button type="submit" class="admin-btn">Сохранить</button>
                <a href="dashboard.php" class="admin-btn admin-btn-secondary">Отмена</a>
            </div>
        </form>
    </div>

    <div class="admin-footer">
        emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>

<script src="../assets/admin-editor.js"></script>

</body>
</html>