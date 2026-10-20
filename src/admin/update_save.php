<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;
use Emizh\Classes\Storage;

Auth::requireLogin();

if (!isPost()) {
    adminRedirect('update.php');
}

Csrf::requireValid();

// Проверяем, что файл загружен без ошибок
if (!isset($_FILES['archive']) || $_FILES['archive']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Файл не загружен или произошла ошибка при загрузке.';
    adminRedirect('update.php');
}

$file = $_FILES['archive'];
$tmpPath = $file['tmp_name'];

if ($file['size'] > 20 * 1024 * 1024) {
    $_SESSION['flash_error'] = 'Архив слишком большой (максимум 20 МБ).';
    adminRedirect('update.php');
}

// Проверка zip
$zip = new ZipArchive();
if ($zip->open($tmpPath) !== true) {
    $_SESSION['flash_error'] = 'Не удалось открыть архив. Убедитесь, что это корректный .zip файл.';
    adminRedirect('update.php');
}

// Проверка, что это действительно архив emizh (в корне должны быть нужные файлы)
if (!$zip->locateName('index.php') || !$zip->locateName('bootstrap.php')) {
    $zip->close();
    $_SESSION['flash_error'] = 'Архив не похож на emizh (отсутствует index.php или bootstrap.php).';
    adminRedirect('update.php');
}

$targetDir = dirname(__DIR__); // папка admin/.. = корень сайта
$backupDir = sys_get_temp_dir() . '/emizh_update_' . time();

// Создаём папку для бэкапа
if (!mkdir($backupDir, 0755, true)) {
    $zip->close();
    $_SESSION['flash_error'] = 'Не удалось создать резервную копию. Проверьте права на сервере.';
    adminRedirect('update.php');
}

// Бэкап текущих файлов (только тех, что будут перезаписаны)
$filesToBackup = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    $source = $targetDir . '/' . $name;
    if (is_file($source)) {
        $filesToBackup[] = $name;
        $dest = $backupDir . '/' . $name;
        @mkdir(dirname($dest), 0755, true);
        copy($source, $dest);
    }
}

// Распаковываем поверх
if (!$zip->extractTo($targetDir)) {
    $zip->close();
    // Восстанавливаем из бэкапа
    foreach ($filesToBackup as $name) {
        copy($backupDir . '/' . $name, $targetDir . '/' . $name);
    }
    $_SESSION['flash_error'] = 'Не удалось распаковать архив. Изменения отменены.';
    adminRedirect('update.php');
}

$zip->close();

// Логируем успешное обновление
Storage::log('Обновление через админку выполнено успешно. Резервная копия: ' . $backupDir);

$_SESSION['flash_success'] = 'Система обновлена. Если страница выглядит некорректно — обновите её (Ctrl+F5).';
adminRedirect('dashboard.php');