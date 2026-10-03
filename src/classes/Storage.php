<?php

declare(strict_types=1);

namespace Emizh\Classes;

use RuntimeException;

/**
 * Работа с папкой data/ и эталоном defaults/.
 *
 * Отвечает за чтение, запись, создание, удаление файлов данных.
 * Если папки data/ нет — автоматически воссоздаёт её из defaults/.
 */
final class Storage
{
    private const DATA_DIR_NAME = 'data';
    private const DEFAULTS_DIR_NAME = 'defaults';

    /**
     * Абсолютный путь к папке data/.
     * Папка лежит на уровень выше корня сайта (public_html/).
     */
    public static function dataPath(): string
    {
        return dirname(self::siteRoot()) . DIRECTORY_SEPARATOR . self::DATA_DIR_NAME;
    }

    /**
     * Абсолютный путь к папке defaults/ внутри сайта.
     */
    public static function defaultsPath(): string
    {
        return self::siteRoot() . DIRECTORY_SEPARATOR . self::DEFAULTS_DIR_NAME;
    }

    /**
     * Корень сайта — папка, в которой лежит src/ или dist/.
     */
    public static function siteRoot(): string
    {
        // src/classes/Storage.php → src/classes → src → корень
        return dirname(__DIR__, 1);
    }

    /**
     * Существует ли папка data/.
     */
    public static function dataExists(): bool
    {
        return is_dir(self::dataPath());
    }

    /**
     * Убедиться, что data/ существует. Если нет — создать из defaults/.
     */
    public static function ensureData(): void
    {
        if (self::dataExists()) {
            return;
        }

        self::restoreFromDefaults();
    }

    /**
     * Полностью удалить data/ и воссоздать из defaults/.
     * Используется кнопкой «Очистить сайт».
     */
    public static function resetData(): void
    {
        self::removeDirectory(self::dataPath());
        self::restoreFromDefaults();
    }

    /**
     * Скопировать defaults/ → data/.
     */
    public static function restoreFromDefaults(): void
    {
        $defaults = self::defaultsPath();
        $data = self::dataPath();

        if (!is_dir($defaults)) {
            throw new RuntimeException('Папка defaults/ не найдена: ' . $defaults);
        }

        self::copyDirectory($defaults, $data);

        // Дополнительно убеждаемся, что есть uploads/
        $uploads = $data . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($uploads)) {
            mkdir($uploads, 0755, true);
        }
    }

    /**
     * Читает JSON-файл и возвращает массив.
     * Относительный путь от data/, например: "site.json" или "pages/home.json".
     */
    public static function readJson(string $relativePath): array
    {
        $full = self::resolveDataPath($relativePath);

        if (!is_file($full)) {
            return [];
        }

        $content = file_get_contents($full);
        if ($content === false) {
            throw new RuntimeException('Не удалось прочитать файл: ' . $full);
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Некорректный JSON в файле: ' . $full);
        }

        return $decoded;
    }

    /**
     * Записывает массив как JSON-файл.
     */
    public static function writeJson(string $relativePath, array $data): void
    {
        $full = self::resolveDataPath($relativePath);
        $dir = dirname($full);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new RuntimeException('Не удалось закодировать JSON: ' . json_last_error_msg());
        }

        // Пишем во временный файл, потом переименовываем — атомарная запись
        $tmp = $full . '.tmp';
        if (file_put_contents($tmp, $json) === false) {
            throw new RuntimeException('Не удалось записать файл: ' . $tmp);
        }

        if (!rename($tmp, $full)) {
            @unlink($tmp);
            throw new RuntimeException('Не удалось переименовать файл: ' . $full);
        }
    }

    /**
     * Удаляет один файл внутри data/.
     */
    public static function deleteFile(string $relativePath): bool
    {
        $full = self::resolveDataPath($relativePath);
        if (is_file($full)) {
            return unlink($full);
        }
        return false;
    }

    /**
     * Список файлов в подпапке data/. Возвращает массив имён файлов без пути.
     */
    public static function listFiles(string $subdir): array
    {
        $dir = self::resolveDataPath($subdir);
        if (!is_dir($dir)) {
            return [];
        }

        $files = scandir($dir);
        if ($files === false) {
            return [];
        }

        $result = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $full = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($full)) {
                $result[] = $file;
            }
        }
        return $result;
    }

    /**
     * Существует ли файл внутри data/.
     */
    public static function fileExists(string $relativePath): bool
    {
        return is_file(self::resolveDataPath($relativePath));
    }

    // ---------- Приватные методы ----------

    private static function resolveDataPath(string $relativePath): string
    {
        $clean = ltrim($relativePath, '/\\');
        if (str_contains($clean, '..')) {
            throw new RuntimeException('Недопустимый путь: ' . $relativePath);
        }
        return self::dataPath() . DIRECTORY_SEPARATOR . $clean;
    }

    private static function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            throw new RuntimeException('Источник не найден: ' . $src);
        }

        if (!is_dir($dst)) {
            if (!mkdir($dst, 0755, true) && !is_dir($dst)) {
                throw new RuntimeException('Не удалось создать папку: ' . $dst);
            }
        }

        $items = scandir($src);
        if ($items === false) {
            throw new RuntimeException('Не удалось прочитать папку: ' . $src);
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $srcPath = $src . DIRECTORY_SEPARATOR . $item;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $item;

            if (is_dir($srcPath)) {
                self::copyDirectory($srcPath, $dstPath);
            } elseif (is_file($srcPath)) {
                if (!copy($srcPath, $dstPath)) {
                    throw new RuntimeException('Не удалось скопировать: ' . $srcPath);
                }
            }
        }
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDirectory($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}