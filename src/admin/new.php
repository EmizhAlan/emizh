<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Page;
use Emizh\Classes\Site;
use Emizh\Classes\Version;

Auth::requireLogin();

$username = Auth::currentUser();
$site = Site::load();

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новая страница — emizh</title>
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">

<div class="admin-container admin-container-wide">

    <div class="admin-topbar">
        <div class="admin-topbar-title">
            <?= adminBack('dashboard.php') ?>
        </div>
        <div class="admin-topbar-user">
            <?= htmlspecialchars((string)$username, ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>

    <?= adminFlash() ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h1 class="admin-title">Новая страница</h1>
                <p class="admin-subtitle">Придумайте заголовок — адрес сгенерируется автоматически. При желании его можно изменить.</p>
            </div>
            <div class="admin-card-actions">
                <a href="dashboard.php" class="admin-btn admin-btn-secondary">Отмена</a>
            </div>
        </div>

        <form method="post" action="create.php" class="admin-editor-form" id="editor-form">
            <?= csrfField() ?>
            <input type="hidden" name="show_in_nav" value="1">

            <div class="admin-editor-meta">
                <label class="admin-label">
                    <span>Заголовок</span>
                    <input
                        type="text"
                        name="title"
                        id="input-title"
                        required
                        maxlength="200"
                        autocomplete="off"
                        autofocus
                    >
                </label>

                <label class="admin-label">
                    <span>Адрес (слаг) <small style="color:#888;font-weight:normal;">— заполнится автоматически</small></span>
                    <input
                        type="text"
                        name="slug"
                        id="input-slug"
                        maxlength="64"
                        pattern="[a-z0-9-]{0,64}"
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
                            placeholder="# Заголовок&#10;&#10;Текст страницы..."
                        ></textarea>
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
                <button type="submit" class="admin-btn">Создать страницу</button>
                <a href="dashboard.php" class="admin-btn admin-btn-secondary">Отмена</a>
            </div>
        </form>
    </div>

    <div class="admin-footer">
        emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>

<script src="../assets/admin-editor.js"></script>
<script src="../assets/admin-slug.js"></script>
<script src="../assets/admin-ui.js"></script>
<script>
// Автогенерация слага из заголовка (логика — в assets/admin-slug.js)
EmizhSlug.bind(
    document.getElementById('input-title'),
    document.getElementById('input-slug'),
    <?= json_encode(array_keys(Page::loadAll()), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>
);
</script>

</body>
</html>