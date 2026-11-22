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
    private bool $showInNav = true;

    public function __construct(
        string $slug,
        string $title,
        string $content,
        ?string $created = null,
        ?string $updated = null,
        bool $showInNav = true 
    ) {
        self::validateSlug($slug);

        $now = self::now();

        $this->slug = $slug;
        $this->title = $title;
        $this->content = $content;
        $this->created = $created ?? $now;
        $this->updated = $updated ?? $now;
        $this->showInNav = $showInNav;
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
            isset($data['updated']) ? (string) $data['updated'] : null,
            isset($data['showInNav']) ? (bool) $data['showInNav'] : true
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
            'showInNav' => $this->showInNav,
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

            if (!self::isValidSlug($slug)) {
                Storage::log('Страница пропущена: некорректное имя файла "pages/' . $file . '"');
                continue;
            }

            try {
                $page = self::load($slug);
                if ($page !== null) {
                    $pages[$slug] = $page;
                }
            } catch (RuntimeException | InvalidArgumentException $e) {
                // Битый файл не должен ломать весь сайт — пропускаем его с записью в лог
                Storage::log('Страница пропущена: pages/' . $file . ' — ' . $e->getMessage());
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
     *
     * Поддерживает русский, украинский, белорусский, английский и другие.
     * Обрабатывает CamelCase, специальные дефисы, кавычки и прочие символы.
     *
     * Примеры:
     *   "Моя первая страница"   → "moya-pervaya-stranica"
     *   "Проект «Мой сайт»"     → "proekt-moy-sait"
     *   "My Cool Page"          → "my-cool-page"
     *   "Тест — это просто"      → "test-eto-prosto"
     *
     * Если результат пустой или невалидный — генерируется уникальный fallback.
     */
    public static function slugify(string $title, array $existingSlugs = []): string
    {
        $map = [
            // Русский
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            // Украинский
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi',
            // Белорусский
            'ў' => 'u',
            // Специальные символы
            '—' => '-', '–' => '-', '€' => 'eur',
            // Кавычки
            '«' => '', '»' => '', '„' => '', '"' => '',
            '‘' => '', '’' => '',
            // Прочее
            '@' => 'at', '&' => 'and', '№' => 'no', '+' => 'plus',
        ];

        // Транслитерация
        $slug = strtr($title, $map);

        // CamelCase → camel-case (например "MyPage" → "my-page")
        $slug = preg_replace('/([a-z])([A-Z])/', '$1-$2', $slug);

        // Приводим к нижнему регистру
        $slug = mb_strtolower($slug, 'UTF-8');

        // Убираем всё, кроме строчных латинских букв, цифр, пробелов и дефисов
        $slug = preg_replace('/[^a-z0-9\s-]+/u', '', $slug);

        // Дефисы и пробелы → дефисы, убираем дубли
        $slug = preg_replace('/[\s-]+/', '-', $slug);

        // Убираем дефисы с краёв
        $slug = trim($slug, '-');

        // Обрезаем до 64 символов, стараясь не резать слово
        if (mb_strlen($slug) > 64) {
            $cut = mb_substr($slug, 0, 64);
            // Если последний символ не дефис — ищем ближайший дефис назад
            if (!str_ends_with($cut, '-')) {
                $lastDash = strrpos($cut, '-');
                if ($lastDash !== false) {
                    $slug = mb_substr($cut, 0, $lastDash);
                } else {
                    $slug = $cut;
                }
            }
        }

        $slug = trim($slug, '-');

        // Если пусто или невалидно — уникальный fallback
        if ($slug === '' || !self::isValidSlug($slug)) {
            $base = 'page-' . substr(md5($title . microtime(true)), 0, 8);
            return self->uniqueSlug($base, $existingSlugs);
        }

        // Проверяем уникальность
        return self::uniqueSlug($slug, $existingSlugs);
    }

    /**
     * Если слаг уже занят — добавляем -2, -3 и т.д.
     */
    private static function uniqueSlug(string $slug, array $existingSlugs): string
    {
        if (!in_array($slug, $existingSlugs, true)) {
            return $slug;
        }

        $n = 2;
        while (in_array($slug . '-' . $n, $existingSlugs, true)) {
            $n++;
        }

        return $slug . '-' . $n;
    }

    private function touch(): void
    {
        $this->updated = self::now();
    }

    private static function now(): string
    {
        return date('c');
    }

    public function showInNav(): bool { return $this->showInNav; }
    public function setShowInNav(bool $show): void {
        $this->showInNav = $show;
        $this->touch();
    }
}