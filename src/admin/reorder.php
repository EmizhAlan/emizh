<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;
use Emizh\Classes\Page;
use Emizh\Classes\Site;

Auth::requireLogin();

if (!isPost()) {
    adminRedirect('dashboard.php');
}

Csrf::requireValid();

$slug = trim((string)($_POST['slug'] ?? ''));
$action = trim((string)($_POST['action'] ?? ''));

if (!Page::isValidSlug($slug) || !Page::exists($slug)) {
    $_SESSION['flash_error'] = 'Страница не найдена.';
    adminRedirect('dashboard.php');
}

$site = Site::load();
$order = $site->pageOrder();

// Синхронизируем порядок на случай, если в site.json чего-то не хватает
$site->syncPageOrder();
$order = $site->pageOrder();

$index = array_search($slug, $order, true);
if ($index === false) {
    $_SESSION['flash_error'] = 'Ошибка порядка страниц.';
    adminRedirect('dashboard.php');
}

if ($action === 'up') {
    if ($index > 0) {
        // Меняем местами с предыдущей
        $temp = $order[$index - 1];
        $order[$index - 1] = $order[$index];
        $order[$index] = $temp;
        $site->setPageOrder($order);
        $site->save();
    }
} elseif ($action === 'down') {
    if ($index < count($order) - 1) {
        // Меняем местами со следующей
        $temp = $order[$index + 1];
        $order[$index + 1] = $order[$index];
        $order[$index] = $temp;
        $site->setPageOrder($order);
        $site->save();
    }
} elseif ($action === 'make_home') {
    $site->setHomePage($slug);
    $site->save();
    $_SESSION['flash_success'] = 'Страница назначена главной.';
}

adminRedirect('dashboard.php');