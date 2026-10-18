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

// ---- Читаем данные ----
$title   = trim((string)($_POST['title'] ?? ''));
$slug    = trim((string)($_POST['slug'] ?? ''));
$content = (string)($_POST['content'] ?? '');

// ---- Валидация ----

if ($title === '') {
    $_SESSION['flash_error'] = 'Заголовок не может быть пустым.';
    adminRedirect('new.php');
}

if (!Page::isValidSlug($slug)) {
    $_SESSION['flash_error'] = 'Адрес страницы содержит недопустимые символы. Разрешены строчные латинские буквы, цифры и дефис.';
    adminRedirect('new.php');
}

if (Page::exists($slug)) {
    $_SESSION['flash_error'] = 'Страница с адресом «' . $slug . '» уже существует.';
    adminRedirect('new.php');
}

if (trim($content) === '') {
    $_SESSION['flash_error'] = 'Содержание не может быть пустым.';
    adminRedirect('new.php');
}

// ---- Создаём страницу ----
try {
    $page = new Page($slug, $title, $content);
    $page->save();
} catch (\Throwable $e) {
    $_SESSION['flash_error'] = 'Не удалось создать страницу: ' . $e->getMessage();
    adminRedirect('new.php');
}

// ---- Обновляем site.json ----
$site = Site::load();
$order = $site->pageOrder();
$order[] = $slug;
$site->setPageOrder(array_values(array_unique($order)));

// Если это первая страница — делаем её главной
if ($site->homePage() === '' || !Page::exists($site->homePage())) {
    $site->setHomePage($slug);
}

$site->save();

$_SESSION['flash_success'] = 'Страница «' . $title . '» создана.';
adminRedirect('dashboard.php');