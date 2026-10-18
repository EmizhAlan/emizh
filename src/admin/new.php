<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
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
                <h1 class="admin-title">Новая страница</h1>
                <p class="admin-subtitle">Придумайте заголовок — адрес сгенерируется автоматически. При желании его можно изменить.</p>
            </div>
            <div class="admin-card-actions">
                <a href="dashboard.php" class="admin-btn admin-btn-secondary">Отмена</a>
            </div>
        </div>

        <form method="post" action="create.php" class="admin-editor-form" id="editor-form">
            <?= csrfField() ?>

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
                    <span>Адрес (слаг)</span>
                    <input
                        type="text"
                        name="slug"
                        id="input-slug"
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
<script>
// Автогенерация слага из заголовка
(function () {
    var titleInput = document.getElementById('input-title');
    var slugInput = document.getElementById('input-slug');
    var slugManuallyEdited = false;

    if (!titleInput || !slugInput) return;

    // Транслитерация (упрощённая, как в Page::slugify)
    var map = {
        'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh',
        'з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o',
        'п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts',
        'ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya'
    };

    function slugify(text) {
        return text
            .toLowerCase()
            .split('')
            .map(function (ch) { return map[ch] !== undefined ? map[ch] : ch; })
            .join('')
            .replace(/[^a-z0-9\s-]+/g, '')
            .replace(/[\s-]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 64);
    }

    titleInput.addEventListener('input', function () {
        if (!slugManuallyEdited) {
            slugInput.value = slugify(titleInput.value);
        }
    });

    slugInput.addEventListener('input', function () {
        slugManuallyEdited = true;
    });
})();
</script>

</body>
</html>