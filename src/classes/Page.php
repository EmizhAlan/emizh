<?php

declare(strict_types=1);

namespace Emizh\Classes;

use InvalidArgumentException;
use RuntimeException;

/**
 * Модель одной страницы сайта.
 *
 * Отвечает за представление страницы, её валидацию,
 * загрузку и сохранение в data/pages/<slug>.json.
 */
final class Page
{
    public const SLUG_PATTERN = '/^[a-z0-9-]{1,64}$/';

    private string $slug;
    private string $title;
    private string $content;
    private string $created;
    private string $updated;

    public function __construct(
        string $slug,
        string $title,
        string $content,
        ?string $created = null,
        ?string $updated = null
    ) {
        self::validateSlug($slug);

        $now = self::now();

        $this->slug = $slug;
        $this->title = $title;
        $this->content = $content;
        $this->created = $created ?? $now;
        $this->updated = $updated ?? $now;
    }

    /**
     * Создать страницу из массива (например, из JSON-файла).
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['slug'], $data['title'])) {
            throw new InvalidArgumentException('Не хватает обязательных полей: slug, title');
        }

        return new self(
            (string) $data['slug'],
            (string) $data['title'],
            (string) ($data['content'] ?? ''),
            isset($data['created']) ? (string) $data['created'] : null,
            isset($data['updated']) ? (string) $data['updated'] : null
        );
    }

    /**
     * Превратить страницу в массив для записи в JSON.
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content,
            'created' => $this->created,
            'updated' => $this->updated,
        ];
    }

    // ---------- Геттеры ----------

    public function slug(): string
    {
        return $this->slug;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function created(): string
    {
        return $this->created;
    }

    public function updated(): string
    {
        return $this->updated;
    }

    // ---------- Сеттеры ----------

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->touch();
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->touch();
    }

    /**
     * Изменить слаг страницы. Файл нужно сохранить заново и удалить старый.
     */
    public function setSlug(string $slug): void
    {
        self::validateSlug($slug);
        $this->slug = $slug;
        $this->touch();
    }

    // ---------- Работа с файлами ----------

    /**
     * Путь к файлу страницы относительно data/.
     */
    public function relativePath(): string
    {
        return 'pages/' . $this->slug . '.json';
    }

    /**
     * Сохранить страницу в data/pages/<slug>.json.
     */
    public function save(): void
    {
        Storage::writeJson($this->relativePath(), $this->toArray());
    }

    /**
     * Удалить файл страницы из data/pages/.
     */
    public function delete(): bool
    {
        return Storage::deleteFile($this->relativePath());
    }

    /**
     * Загрузить страницу по слагу. Возвращает null, если файла нет.
     */
    public static function load(string $slug): ?self
    {
        self::validateSlug($slug);

        $path = 'pages/' . $slug . '.json';
        if (!Storage::fileExists($path)) {
            return null;
        }

        return self::fromArray(Storage::readJson($path));
    }

    /**
     * Загрузить все страницы из data/pages/.
     * Возвращает массив объектов Page, ключи — слаги.
     */
    public static function loadAll(): array
    {
        $files = Storage::listFiles('pages');
        $pages = [];

        foreach ($files as $file) {
            if (!str_ends_with($file, '.json')) {
                continue;
            }

            $slug = substr($file, 0, -5); // убираем .json

            try {
                $page = self::load($slug);
                if ($page !== null) {
                    $pages[$slug] = $page;
                }
            } catch (RuntimeException | InvalidArgumentException) {
                // Пропускаем битый файл, продолжаем
                continue;
            }
        }

        return $pages;
    }

    /**
     * Существует ли страница с таким слагом.
     */
    public static function exists(string $slug): bool
    {
        try {
            self::validateSlug($slug);
        } catch (InvalidArgumentException) {
            return false;
        }

        return Storage::fileExists('pages/' . $slug . '.json');
    }

    // ---------- Валидация и утилиты ----------

    /**
     * Проверить слаг на соответствие шаблону.
     * Бросает исключение, если слаг невалиден.
     */
    public static function validateSlug(string $slug): void
    {
        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            throw new InvalidArgumentException(
                'Недопустимый слаг: "' . $slug . '". Разрешены строчные латинские буквы, цифры и дефис, от 1 до 64 символов.'
            );
        }
    }

    /**
     * Проверить, валиден ли слаг, без исключения.
     */
    public static function isValidSlug(string $slug): bool
    {
        return (bool) preg_match(self::SLUG_PATTERN, $slug);
    }

    /**
     * Сгенерировать слаг из произвольного заголовка.
     * Например: "Моя первая страница" → "moya-pervaya-stranica".
     */
    public static function slugify(string $title): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $lower = mb_strtolower($title, 'UTF-8');
        $replaced = strtr($lower, $map);

        // Убираем всё, кроме латиницы, цифр и пробелов
        $clean = preg_replace('/[^a-z0-9\s-]+/u', '', $replaced);
        $clean = preg_replace('/[\s-]+/', '-', $clean);
        $clean = trim($clean, '-');

        if ($clean === '' || !self::isValidSlug($clean)) {
            $clean = 'page-' . substr(md5($title . microtime()), 0, 8);
        }

        return $clean;
    }

    private function touch(): void
    {
        $this->updated = self::now();
    }

    private static function now(): string
    {
        return date('c');
    }
}