<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;
use Emizh\Classes\Page;
use Emizh\Classes\Site;
use Emizh\Classes\Version;

Auth::requireLogin();

$username = Auth::currentUser();
$slug = trim((string)($_GET['slug'] ?? $_POST['slug'] ?? ''));

// ---- Валидация слага ----
if ($slug === '' || !Page::isValidSlug($slug)) {
    $_SESSION['flash_error'] = 'Некорректный адрес страницы.';
    adminRedirect('dashboard.php');
}

// ---- Загружаем страницу ----
$page = Page::load($slug);
if ($page === null) {
    $_SESSION['flash_error'] = 'Страница не найдена.';
    adminRedirect('dashboard.php');
}

$site = Site::load();

// ---- Обработка POST (подтверждение удаления) ----
if (isPost()) {
    Csrf::requireValid();

    // Удаляем файл страницы
    $page->delete();

    // Обновляем site.json
    $order = $site->pageOrder();
    $order = array_values(array_filter($order, fn($s) => $s !== $slug));
    $site->setPageOrder($order);

    // Если удалили главную — назначаем новую
    if ($site->homePage() === $slug) {
        $remaining = $site->pages(); // загружаем заново — файл уже удалён
        if ($remaining !== []) {
            $firstSlug = array_key_first($remaining);
            $site->setHomePage($firstSlug);
        } else {
            $site->setHomePage('');
        }
    }

    $site->save();

    $_SESSION['flash_success'] = 'Страница «' . $page->title() . '» удалена.';
    adminRedirect('dashboard.php');
}

// ---- GET: показываем страницу подтверждения ----
$isHome = ($slug === $site->homePage());

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удаление страницы — emizh</title>
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">

<div class="admin-container">

    <div class="admin-card">
        <h1 class="admin-title">Удалить страницу?</h1>
        <p class="admin-subtitle">
            Страница <strong>«<?= htmlspecialchars($page->title(), ENT_QUOTES, 'UTF-8') ?>»</strong>
            (адрес <code>/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></code>)
            будет удалена навсегда.
        </p>

        <?php if ($isHome): ?>
            <div class="admin-alert admin-alert-error">
                <strong>Внимание:</strong> это главная страница сайта.
                После удаления главной станет другая страница (если они есть).
            </div>
        <?php endif; ?>

        <p>Действие нельзя отменить. Если вы не уверены — нажмите «Отмена».</p>

        <form method="post" action="delete.php" class="admin-form-inline">
            <?= csrfField() ?>
            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">

            <a href="dashboard.php" class="admin-btn admin-btn-secondary">Отмена</a>
            <button type="submit" class="admin-btn admin-btn-danger">Удалить навсегда</button>
        </form>
    </div>

    <div class="admin-footer">
        emizh <?= htmlspecialchars(Version::current(), ENT_QUOTES, 'UTF-8') ?>
    </div>

</div>

</body>
</html>