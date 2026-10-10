<?php

declare(strict_types=1);

namespace Emizh\Classes;

/**
 * CSRF-защита форм.
 *
 * Генерирует токен, хранит его в сессии, проверяет на POST-запросах.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const FIELD_NAME = '_csrf';
    private const TOKEN_BYTES = 32;
    private const ROTATE_INTERVAL = 900; // 15 минут

    /**
     * Получить текущий токен. Если его нет — создать.
     */
    public static function token(): string
    {
        Auth::startSession();

        $lastRotate = $_SESSION['_csrf_rotated_at'] ?? 0;

        // Если токена нет или прошло больше ROTATE_INTERVAL — создаём новый
        if (
            empty($_SESSION[self::SESSION_KEY]) ||
            (time() - $lastRotate) > self::ROTATE_INTERVAL
        ) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_BYTES));
            $_SESSION['_csrf_rotated_at'] = time();
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Имя поля формы.
     */
    public static function fieldName(): string
    {
        return self::FIELD_NAME;
    }

    /**
     * Готовый HTML-инпут со скрытым полем.
     * Использовать в формах так: <?= Csrf::field() ?>
     */
    public static function field(): string
    {
        $value = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        $name  = htmlspecialchars(self::FIELD_NAME, ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }

    /**
     * Проверить токен из POST-запроса.
     * Возвращает true, если токен валиден.
     */
    public static function verify(): bool
    {
        Auth::startSession();

        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($stored) || $stored === '') {
            return false;
        }

        $sent = $_POST[self::FIELD_NAME] ?? null;
        if (!is_string($sent) || $sent === '') {
            return false;
        }

        return hash_equals($stored, $sent);
    }

    /**
     * Проверить токен или прервать выполнение.
     * Используется в начале POST-обработчиков.
     */
    public static function requireValid(): void
    {
        if (self::verify()) {
            return;
        }

        http_response_code(419); // "Authentication Timeout" — нестандартный, но понятный код
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'CSRF-токен недействителен. Обновите страницу и попробуйте снова.';
        exit;
    }

    /**
     * Сбросить токен (например, после логина — для безопасности).
     */
    public static function rotate(): void
    {
        Auth::startSession();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_BYTES));
        $_SESSION['_csrf_rotated_at'] = time();
    }
}