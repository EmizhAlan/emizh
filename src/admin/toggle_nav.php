<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;
use Emizh\Classes\Page;

Auth::requireLogin();

if (!isPost()) adminRedirect('dashboard.php');
Csrf::requireValid();

$slug = trim((string)($_POST['slug'] ?? ''));
$page = Page::load($slug);

if ($page) {
    // Переключаем туда-сюда
    $page->setShowInNav(!$page->showInNav());
    $page->save();
}

adminRedirect('dashboard.php');