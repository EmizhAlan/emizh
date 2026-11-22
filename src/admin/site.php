<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;
use Emizh\Classes\Site;

Auth::requireLogin();
$site = Site::load();
$pages = $site->pages();

if (isPost()) {
    Csrf::requireValid();
    $newOrder = $_POST['nav'] ?? [];
    
    // 1. Устанавливаем новый порядок (только те, что отмечены, плюс те, что не отмечены, но должны остаться в конце)
    $allSlugs = array_keys($pages);
    $checkedSlugs = array_keys($newOrder);
    $uncheckedSlugs = array_diff($allSlugs, $checkedSlugs);
    
    // Порядок: сначала отмеченные (в порядке массива nav), затем неотмеченные
    $site->setPageOrder(array_merge($checkedSlugs, $uncheckedSlugs));
    
    // 2. Обновляем флаг showInNav
    foreach ($pages as $slug => $page) {
        $page->setShowInNav(isset($newOrder[$slug]));
        $page->save();
    }
    
    $site->save();
    $_SESSION['flash_success'] = 'Настройки навигации сохранены.';
    adminRedirect('site.php');
}

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Настройки сайта — emizh</title>
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">
<div class="admin-container">
    <div class="admin-topbar">
        <div class="admin-topbar-title"><?= adminBack('dashboard.php') ?></div>
    </div>

    <?= adminFlash() ?>

    <div class="admin-card">
        <h1 class="admin-title">Навигация сайта</h1>
        <p class="admin-subtitle">Выберите страницы, которые будут отображаться в шапке сайта.</p>

        <form method="post" class="admin-form">
            <?= csrfField() ?>
            <?php foreach ($pages as $slug => $page): ?>
                <label class="admin-label" style="flex-direction: row; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 8px;">
                    <input type="checkbox" name="nav[<?= htmlspecialchars($slug) ?>]" value="1" <?= $page->showInNav() ? 'checked' : '' ?>>
                    <span style="font-size: 15px;"><?= htmlspecialchars($page->title()) ?></span>
                </label>
            <?php endforeach; ?>
            <div style="margin-top: 20px;">
                <button type="submit" class="admin-btn">Сохранить настройки</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/admin-ui.js"></script>

</body>
</html>