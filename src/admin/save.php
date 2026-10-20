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

// ---- Читаем входные данные ----
$originalSlug = trim((string)($_POST['original_slug'] ?? ''));
$title        = trim((string)($_POST['title'] ?? ''));
$newSlug      = trim((string)($_POST['slug'] ?? ''));
$content      = (string)($_POST['content'] ?? '');
$showInNav = isset($_POST['show_in_nav']);

// ---- Валидация ----

// Оригинальный слаг должен быть валиден
if (!Page::isValidSlug($originalSlug)) {
    $_SESSION['flash_error'] = 'Некорректный исходный адрес страницы.';
    adminRedirect('dashboard.php');
}

// Загружаем существующую страницу
$page = Page::load($originalSlug);
if ($page === null) {
    $_SESSION['flash_error'] = 'Страница не найдена.';
    adminRedirect('dashboard.php');
}

// Заголовок не может быть пустым
if ($title === '') {
    $_SESSION['flash_error'] = 'Заголовок не может быть пустым.';
    adminRedirect('edit.php?slug=' . urlencode($originalSlug));
}

// Новый слаг должен быть валидным
if (!Page::isValidSlug($newSlug)) {
    $_SESSION['flash_error'] = 'Адрес страницы содержит недопустимые символы. Разрешены строчные латинские буквы, цифры и дефис.';
    adminRedirect('edit.php?slug=' . urlencode($originalSlug));
}

$slugChanged = ($newSlug !== $originalSlug);

// Если слаг меняется — проверяем, что новый слаг не занят
if ($slugChanged && Page::exists($newSlug)) {
    $_SESSION['flash_error'] = 'Страница с адресом «' . $newSlug . '» уже существует.';
    adminRedirect('edit.php?slug=' . urlencode($originalSlug));
}

// ---- Обновляем объект ----
$page->setTitle($title);
$page->setContent($content);
$page->setShowInNav($showInNav);

// ---- Сохраняем ----

if ($slugChanged) {
    // Меняем слаг: создаём новый файл, удаляем старый, обновляем pageOrder
    $page->setSlug($newSlug);
    $page->save();                       // сохраняем по новому слагу
    Page::load($originalSlug)?->delete(); // удаляем старый файл

    // Обновляем site.json — порядок страниц и главную
    $site = Site::load();
    $order = $site->pageOrder();
    $order = array_map(
        fn($s) => $s === $originalSlug ? $newSlug : $s,
        $order
    );

    // Если переименованная страница была главной — обновляем homePage
    $homePage = $site->homePage();
    if ($homePage === $originalSlug) {
        $site->setHomePage($newSlug);
    }

    $site->setPageOrder(array_values(array_unique($order)));
    $site->save();

    $_SESSION['flash_success'] = 'Страница сохранена. Адрес изменён на «' . $newSlug . '».';
} else {
    // Просто сохраняем
    $page->save();
    $_SESSION['flash_success'] = 'Страница сохранена.';
}

adminRedirect('dashboard.php');