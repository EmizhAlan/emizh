<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Emizh\Classes\Version;
use Emizh\Classes\Storage;
use Emizh\Classes\Page;

echo '<pre>';
echo 'Версия: ' . Version::current() . "\n\n";

Storage::ensureData();

// 1. Загрузить все страницы
echo "Все страницы:\n";
$pages = Page::loadAll();
foreach ($pages as $slug => $page) {
    echo "  [$slug] {$page->title()} — обновлена {$page->updated()}\n";
}
echo "\n";

// 2. Загрузить конкретную
$home = Page::load('home');
if ($home !== null) {
    echo "Главная страница:\n";
    echo "  Заголовок: {$home->title()}\n";
    echo "  Слаг: {$home->slug()}\n";
    echo "  Создана: {$home->created()}\n";
    echo "  Обновлена: {$home->updated()}\n";
    echo "  Первые 60 символов содержимого: " . mb_substr($home->content(), 0, 60) . "...\n\n";
}

// 3. Проверка валидации слага
echo "Проверка валидации слагов:\n";
$tests = ['home', 'about', 'my-page', 'Home', 'with space', '../etc', 'a'];
foreach ($tests as $test) {
    $ok = Page::isValidSlug($test) ? '✓' : '✗';
    echo "  $ok $test\n";
}
echo "\n";

// 4. Проверка slugify
echo "Генерация слагов:\n";
$titles = ['Моя первая страница', 'About Me', 'Привет мир', 'Hello World 123'];
foreach ($titles as $t) {
    echo "  \"$t\" → " . Page::slugify($t) . "\n";
}
echo "\n";

// 5. Создать временную страницу, сохранить, удалить
echo "Тест save/delete:\n";
$test = new Page('test-page', 'Тестовая страница', "## Заголовок\n\nТекст.");
$test->save();
echo "  Создана: " . (Page::exists('test-page') ? 'да' : 'нет') . "\n";

$loaded = Page::load('test-page');
echo "  Загружена: " . ($loaded ? $loaded->title() : 'не загружена') . "\n";

$test->delete();
echo "  Удалена: " . (!Page::exists('test-page') ? 'да' : 'нет') . "\n";

echo "</pre>";