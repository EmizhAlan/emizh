<?php

declare(strict_types=1);

namespace Emizh\Classes;

use InvalidArgumentException;
use RuntimeException;

/**
 * Аутентификация владельца.
 *
 * Отвечает за файл owner.json, который лежит рядом с папкой data/,
 * вне корневой директории сайта. Здесь хранится логин, хэш пароля
 * и секретный ключ для подписи сессий.
 */
final class Auth
{
    private const FILE_NAME = 'owner.json';
    private const USERNAME_MIN = 3;
    private const USERNAME_MAX = 32;
    private const PASSWORD_MIN = 6;

    /**
     * Абсолютный путь к файлу owner.json.
     * Лежит рядом с data/, то есть на уровень выше корня сайта.
     */
    public static function ownerFilePath(): string
    {
        return dirname(Storage::dataPath()) . DIRECTORY_SEPARATOR . self::FILE_NAME;
    }

    /**
     * Существует ли владелец.
     */
    public static function ownerExists(): bool
    {
        return is_file(self::ownerFilePath());
    }

    /**
     * Прочитать owner.json и вернуть массив.
     * Если файла нет — вернуть null.
     */
    public static function read(): ?array
    {
        $path = self::ownerFilePath();

        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Не удалось прочитать owner.json');
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new RuntimeException('owner.json повреждён или содержит некорректный JSON');
        }

        if (!isset($data['username'], $data['passwordHash'], $data['secret'])) {
            throw new RuntimeException('owner.json не содержит обязательных полей');
        }

        return $data;
    }

    /**
     * Создать владельца.
     *
     * Только для первого запуска. Если владелец уже существует —
     * выбрасывает исключение.
     */
    public static function createOwner(string $username, string $password): void
    {
        if (self::ownerExists()) {
            throw new RuntimeException('Владелец уже создан. Перерегистрация запрещена.');
        }

        self::validateUsername($username);
        self::validatePassword($password);

        $data = [
            'username' => $username,
            'passwordHash' => self::hashPassword($password),
            'secret' => self::generateSecret(),
            'created' => date('c'),
        ];

        self::writeFile($data);
    }

    /**
     * Проверить логин и пароль.
     * Возвращает true при совпадении, иначе false.
     */
    public static function verify(string $username, string $password): bool
    {
        $owner = self::read();
        if ($owner === null) {
            return false;
        }

        if (!hash_equals($owner['username'], $username)) {
            return false;
        }

        return password_verify($password, $owner['passwordHash']);
    }

    /**
     * Получить секретный ключ для подписи сессий.
     * Используется в v0.2.2.
     */
    public static function secret(): ?string
    {
        $owner = self::read();
        return $owner['secret'] ?? null;
    }

    /**
     * Получить имя владельца (для отображения в админке).
     */
    public static function username(): ?string
    {
        $owner = self::read();
        return $owner['username'] ?? null;
    }

    /**
     * Изменить пароль владельца.
     * Используется в будущих версиях — пока не подключено к UI.
     */
    public static function changePassword(string $currentPassword, string $newPassword): bool
    {
        $owner = self::read();
        if ($owner === null) {
            return false;
        }

        if (!password_verify($currentPassword, $owner['passwordHash'])) {
            return false;
        }

        self::validatePassword($newPassword);

        $owner['passwordHash'] = self::hashPassword($newPassword);
        $owner['updated'] = date('c');

        self::writeFile($owner);
        return true;
    }

    // ---------- Валидация ----------

    /**
     * Проверка логина.
     */
    public static function validateUsername(string $username): void
    {
        $len = mb_strlen($username, 'UTF-8');

        if ($len < self::USERNAME_MIN) {
            throw new InvalidArgumentException(
                'Логин слишком короткий: минимум ' . self::USERNAME_MIN . ' символа'
            );
        }

        if ($len > self::USERNAME_MAX) {
            throw new InvalidArgumentException(
                'Логин слишком длинный: максимум ' . self::USERNAME_MAX . ' символов'
            );
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            throw new InvalidArgumentException(
                'Логин может содержать только латинские буквы, цифры, точку, дефис и подчёркивание'
            );
        }
    }

    /**
     * Проверка пароля.
     */
    public static function validatePassword(string $password): void
    {
        if (mb_strlen($password, 'UTF-8') < self::PASSWORD_MIN) {
            throw new InvalidArgumentException(
                'Пароль слишком короткий: минимум ' . self::PASSWORD_MIN . ' символов'
            );
        }
    }

    /**
     * Проверка логина без исключения.
     */
    public static function isValidUsername(string $username): bool
    {
        try {
            self::validateUsername($username);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    // ---------- Приватные утилиты ----------

    private static function hashPassword(string $password): string
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException('Не удалось захэшировать пароль');
        }
        return $hash;
    }

    private static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    private static function writeFile(array $data): void
    {
        $path = self::ownerFilePath();
        $dir = dirname($path);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('Не удалось создать директорию для owner.json');
            }
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new RuntimeException('Не удалось закодировать owner.json');
        }

        // Атомарная запись: временный файл → rename
        $tmp = $path . '.tmp';
        if (file_put_contents($tmp, $json) === false) {
            throw new RuntimeException('Не удалось записать временный файл owner.json');
        }

        // Права 600 — только владелец может читать и писать
        @chmod($tmp, 0600);

        if (!rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException('Не удалось переименовать owner.json');
        }
    }
}