<?php

declare(strict_types=1);

namespace Emizh\Classes;

/**
 * Ограничение частоты попыток.
 *
 * Хранит неудачные попытки в файле, считает по IP-адресу.
 * Используется для защиты формы входа от перебора паролей.
 */
final class RateLimit
{
    private const STORAGE_FILE = 'rate_limit.json';
    private const MAX_ATTEMPTS = 5;      // макс. неудачных попыток
    private const WINDOW_SECONDS = 300;  // за 5 минут

    /**
     * Проверить, разрешена ли попытка.
     * Возвращает true, если можно пытаться, false — если превышен лимит.
     */
    public static function check(string $key): bool
    {
        $data = self::load();
        $now = time();
        $key = self::key($key);

        if (!isset($data[$key])) {
            return true;
        }

        // Фильтруем попытки: убираем старые
        $attempts = array_filter(
            $data[$key]['attempts'] ?? [],
            fn($t) => ($now - $t) < self::WINDOW_SECONDS
        );

        return count($attempts) < self::MAX_ATTEMPTS;
    }

    /**
     * Зафиксировать неудачную попытку.
     */
    public static function hit(string $key): void
    {
        $data = self::load();
        $now = time();
        $key = self::key($key);

        $attempts = $data[$key]['attempts'] ?? [];
        $attempts[] = $now;

        // Оставляем только свежие
        $attempts = array_values(array_filter(
            $attempts,
            fn($t) => ($now - $t) < self::WINDOW_SECONDS
        ));

        $data[$key] = ['attempts' => $attempts];
        self::save($data);
    }

    /**
     * Сбросить попытки (например, после успешного входа).
     */
    public static function clear(string $key): void
    {
        $data = self::load();
        $key = self::key($key);
        unset($data[$key]);
        self::save($data);
    }

    /**
     * Сколько секунд осталось до снятия блокировки.
     * Возвращает 0, если блокировки нет.
     */
    public static function secondsUntilRetry(string $key): int
    {
        $data = self::load();
        $now = time();
        $key = self::key($key);

        if (!isset($data[$key]['attempts'])) {
            return 0;
        }

        $attempts = $data[$key]['attempts'];
        if (count($attempts) < self::MAX_ATTEMPTS) {
            return 0;
        }

        // Самая старая попытка в окне
        sort($attempts);
        $oldest = $attempts[0];
        $until = $oldest + self::WINDOW_SECONDS - $now;

        return max(0, $until);
    }

    // ---------- Приватные методы ----------

    private static function key(string $raw): string
    {
        // Заменяем опасные символы, чтобы ключ был валидным
        return preg_replace('/[^a-zA-Z0-9:._-]/', '_', $raw) ?? $raw;
    }

    private static function storagePath(): string
    {
        return dirname(Storage::dataPath()) . DIRECTORY_SEPARATOR . self::STORAGE_FILE;
    }

    private static function load(): array
    {
        $path = self::storagePath();
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private static function save(array $data): void
    {
        $path = self::storagePath();

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            return;
        }

        $tmp = $path . '.tmp';
        if (file_put_contents($tmp, $json) === false) {
            return;
        }

        @chmod($tmp, 0600);
        @rename($tmp, $path);
    }
}