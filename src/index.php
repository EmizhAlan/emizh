<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Emizh\Classes\Version;
use Emizh\Classes\Storage;
use Emizh\Classes\Site;

echo '<pre>';
echo 'Версия: ' . Version::current() . "\n\n";

$site = Site::load();

echo "Настройки сайта:\n";
echo "  Название: {$site->title()}\n";
echo "  Акцент: {$site->accentColor()}\n";
echo "  Шрифт: {$site->font()}\n";
echo "  Тема: {$site->theme()}\n";
echo "  Описание: {$site->description()}\n";
echo "  Автор: {$site->author()}\n";
echo "  Главная: {$site->homePage()}\n";
echo "  Порядок: " . implode(', ', $site->pageOrder()) . "\n\n";

echo "Страницы в порядке:\n";
foreach ($site->pages() as $slug => $page) {
    echo "  [$slug] {$page->title()}\n";
}
echo "\n";

$home = $site->home();
echo "Главная страница: " . ($home ? $home->title() : '(нет)') . "\n\n";

echo "Изменяем название и тему...\n";
$site->setTitle('Emizh Demo');
$site->setTheme('dark');
$site->setAccentColor('#10b981');
$site->save();

echo "Перечитываем из файла:\n";
$reloaded = Site::load();
echo "  Название: {$reloaded->title()}\n";
echo "  Тема: {$reloaded->theme()}\n";
echo "  Акцент: {$reloaded->accentColor()}\n\n";

echo "Возвращаем как было...\n";
$reloaded->setTitle('Мой сайт');
$reloaded->setTheme('light');
$reloaded->setAccentColor('#3b82f6');
$reloaded->save();
echo "Готово.\n";

echo "</pre>";