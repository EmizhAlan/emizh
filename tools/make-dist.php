<?php

declare(strict_types=1);

/**
 * Скрипт сборки папки поставки.
 *
 * Копирует содержимое src/ в dist/.
 * Запускается из корня проекта командой:
 *
 *     php tools/make-dist.php
 */

// ---- Константы путей ----

$root       = dirname(__DIR__);
$srcDir     = $root . DIRECTORY_SEPARATOR . 'src';
$distDir    = $root . DIRECTORY_SEPARATOR . 'dist';

// ---- Простая проверка окружения ----

if (!is_dir($srcDir)) {
    fwrite(STDERR, "Ошибка: не найдена папка src/ в {$root}\n");
    fwrite(STDERR, "Запускайте скрипт из корня проекта: php tools/make-dist.php\n");
    exit(1);
}

if (!is_file($srcDir . DIRECTORY_SEPARATOR . 'index.php')) {
    fwrite(STDERR, "Ошибка: в src/ нет index.php. Похоже, структура проекта повреждена.\n");
    exit(1);
}

// ---- Приветствие ----

echo "=== emizh: сборка dist/ ===\n";
echo "Корень проекта: {$root}\n";
echo "Источник:       {$srcDir}\n";
echo "Назначение:     {$distDir}\n\n";

// ---- Удаляем старую dist/ ----

if (is_dir($distDir)) {
    echo "Удаляю старую dist/...\n";
    if (!removeDirectory($distDir)) {
        fwrite(STDERR, "Ошибка: не удалось удалить старую dist/. Возможно, файлы заняты.\n");
        exit(1);
    }
    echo "  готова\n\n";
}

// ---- Копируем src/ → dist/ ----

echo "Копирую src/ → dist/...\n";
if (!copyDirectory($srcDir, $distDir)) {
    fwrite(STDERR, "Ошибка: не удалось скопировать файлы.\n");
    exit(1);
}

// ---- Считаем статистику ----

$fileCount = countFiles($distDir);
$totalSize = directorySize($distDir);

echo "  готово: {$fileCount} файлов, " . formatSize($totalSize) . "\n\n";

// ---- Финальное сообщение ----

echo "=== Сборка завершена ===\n";
echo "Содержимое dist/ готово к заливке на хостинг.\n";
echo "Скопируйте ВСЁ содержимое dist/ в public_html/ на сервере.\n";

// =========================================================
// Вспомогательные функции
// =========================================================

function copyDirectory(string $src, string $dst): bool
{
    if (!is_dir($src)) {
        return false;
    }

    if (!is_dir($dst)) {
        if (!mkdir($dst, 0755, true) && !is_dir($dst)) {
            return false;
        }
    }

    $items = scandir($src);
    if ($items === false) {
        return false;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $srcPath = $src . DIRECTORY_SEPARATOR . $item;
        $dstPath = $dst . DIRECTORY_SEPARATOR . $item;

        if (is_dir($srcPath)) {
            if (!copyDirectory($srcPath, $dstPath)) {
                return false;
            }
        } elseif (is_file($srcPath)) {
            if (!copy($srcPath, $dstPath)) {
                return false;
            }
            // Сохраняем права на исполнение для скриптов
            chmod($dstPath, fileperms($srcPath) & 0777);
        }
    }

    return true;
}

function removeDirectory(string $dir): bool
{
    if (!is_dir($dir)) {
        return true;
    }

    $items = scandir($dir);
    if ($items === false) {
        return false;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            if (!removeDirectory($path)) {
                return false;
            }
        } elseif (is_file($path)) {
            if (!unlink($path)) {
                return false;
            }
        }
    }

    return rmdir($dir);
}

function countFiles(string $dir): int
{
    if (!is_dir($dir)) {
        return 0;
    }

    $count = 0;
    $items = scandir($dir);
    if ($items === false) {
        return 0;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $count += countFiles($path);
        } elseif (is_file($path)) {
            $count++;
        }
    }

    return $count;
}

function directorySize(string $dir): int
{
    if (!is_dir($dir)) {
        return 0;
    }

    $size = 0;
    $items = scandir($dir);
    if ($items === false) {
        return 0;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $size += directorySize($path);
        } elseif (is_file($path)) {
            $size += filesize($path) ?: 0;
        }
    }

    return $size;
}

function formatSize(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' Б';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' КБ';
    }
    return round($bytes / (1024 * 1024), 2) . ' МБ';
}