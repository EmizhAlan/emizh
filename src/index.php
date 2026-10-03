<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Emizh\Classes\Version;
use Emizh\Classes\Storage;

echo '<pre>';
echo 'Версия emizh: ' . Version::current() . "\n\n";

echo 'Корень сайта: ' . Storage::siteRoot() . "\n";
echo 'Путь к defaults/: ' . Storage::defaultsPath() . "\n";
echo 'Путь к data/: ' . Storage::dataPath() . "\n\n";

echo 'data/ существует до: ' . (Storage::dataExists() ? 'да' : 'нет') . "\n";

Storage::ensureData();

echo 'data/ существует после: ' . (Storage::dataExists() ? 'да' : 'нет') . "\n\n";

echo "site.json:\n";
print_r(Storage::readJson('site.json'));

echo "\npages/:\n";
print_r(Storage::listFiles('pages'));

echo "</pre>";