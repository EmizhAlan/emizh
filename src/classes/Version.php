<?php

declare(strict_types=1);

namespace Emizh\Classes;

/**
 * Версия приложения emizh.
 *
 * Читает номер версии из файла VERSION в корне проекта.
 * Если файл недоступен — возвращает заглушку.
 */
final class Version
{
    private const FALLBACK = '0.0.0';

    private static ?string $cached = null;

    /**
     * Возвращает текущую версию приложения в виде строки.
     */
    public static function current(): string
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $path = self::versionFilePath();

        if ($path !== null && is_readable($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $trimmed = trim($content);
                if ($trimmed !== '') {
                    self::$cached = $trimmed;
                    return self::$cached;
                }
            }
        }

        self::$cached = self::FALLBACK;
        return self::$cached;
    }

    /**
     * Ищет файл VERSION, поднимаясь от текущего файла вверх.
     */
    private static function versionFilePath(): ?string
    {
        $dir = __DIR__;
        for ($i = 0; $i < 5; $i++) {
            $candidate = $dir . DIRECTORY_SEPARATOR . 'VERSION';
            if (is_file($candidate)) {
                return $candidate;
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }
        return null;
    }
}