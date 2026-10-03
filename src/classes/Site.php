<?php

declare(strict_types=1);

namespace Emizh\Classes;

use InvalidArgumentException;

/**
 * Модель сайта: настройки + список страниц + порядок.
 *
 * Читает и пишет data/site.json.
 */
final class Site
{
    private const THEMES = ['light', 'dark'];

    private string $title;
    private string $accentColor;
    private string $font;
    private string $theme;
    private string $description;
    private string $author;
    private string $homePage;
    /** @var string[] */
    private array $pageOrder;

    public function __construct(
        string $title = 'Мой сайт',
        string $accentColor = '#3b82f6',
        string $font = 'system-ui',
        string $theme = 'light',
        string $description = '',
        string $author = '',
        string $homePage = '',
        array $pageOrder = []
    ) {
        $this->title = $title;
        $this->accentColor = self::normalizeColor($accentColor);
        $this->font = $font;
        $this->theme = self::normalizeTheme($theme);
        $this->description = $description;
        $this->author = $author;
        $this->homePage = $homePage;
        $this->pageOrder = array_values(array_unique($pageOrder));
    }

    /**
     * Загрузить сайт из data/site.json.
     * Если файла нет — создаётся с дефолтными настройками.
     */
    public static function load(): self
    {
        Storage::ensureData();

        if (!Storage::fileExists('site.json')) {
            $site = new self();
            $site->save();
            return $site;
        }

        $data = Storage::readJson('site.json');

        return new self(
            (string) ($data['title'] ?? 'Мой сайт'),
            (string) ($data['accentColor'] ?? '#3b82f6'),
            (string) ($data['font'] ?? 'system-ui'),
            (string) ($data['theme'] ?? 'light'),
            (string) ($data['description'] ?? ''),
            (string) ($data['author'] ?? ''),
            (string) ($data['homePage'] ?? ''),
            is_array($data['pageOrder'] ?? null) ? $data['pageOrder'] : []
        );
    }

    /**
     * Сохранить настройки сайта в data/site.json.
     */
    public function save(): void
    {
        Storage::writeJson('site.json', $this->toArray());
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'accentColor' => $this->accentColor,
            'font' => $this->font,
            'theme' => $this->theme,
            'description' => $this->description,
            'author' => $this->author,
            'homePage' => $this->homePage,
            'pageOrder' => $this->pageOrder,
        ];
    }

    // ---------- Геттеры ----------

    public function title(): string { return $this->title; }
    public function accentColor(): string { return $this->accentColor; }
    public function font(): string { return $this->font; }
    public function theme(): string { return $this->theme; }
    public function description(): string { return $this->description; }
    public function author(): string { return $this->author; }
    public function homePage(): string { return $this->homePage; }

    /** @return string[] */
    public function pageOrder(): array { return $this->pageOrder; }

    // ---------- Сеттеры ----------

    public function setTitle(string $title): void { $this->title = trim($title) ?: 'Мой сайт'; }
    public function setAccentColor(string $color): void { $this->accentColor = self::normalizeColor($color); }
    public function setFont(string $font): void { $this->font = $font !== '' ? $font : 'system-ui'; }
    public function setTheme(string $theme): void { $this->theme = self::normalizeTheme($theme); }
    public function setDescription(string $d): void { $this->description = $d; }
    public function setAuthor(string $a): void { $this->author = $a; }

    // ---------- Работа со страницами ----------

    /**
     * Все страницы сайта в порядке, заданном pageOrder.
     * Если какие-то страницы есть на диске, но их нет в pageOrder — они добавляются в конец.
     * Если какие-то слаги в pageOrder указывают на несуществующие файлы — они игнорируются.
     *
     * @return Page[]
     */
    public function pages(): array
    {
        $all = Page::loadAll(); // ['slug' => Page]

        $ordered = [];
        $known = [];

        foreach ($this->pageOrder as $slug) {
            if (isset($all[$slug])) {
                $ordered[$slug] = $all[$slug];
                $known[$slug] = true;
            }
        }

        // Добавляем те, которых нет в pageOrder
        foreach ($all as $slug => $page) {
            if (!isset($known[$slug])) {
                $ordered[$slug] = $page;
            }
        }

        return $ordered;
    }

    /**
     * Страница, назначенная главной.
     * Если homePage пуст или указывает на несуществующую страницу — берётся первая из списка.
     * Если страниц нет вообще — возвращает null.
     */
    public function home(): ?Page
    {
        $pages = $this->pages();

        if ($this->homePage !== '' && isset($pages[$this->homePage])) {
            return $pages[$this->homePage];
        }

        return $pages === [] ? null : reset($pages);
    }

    /**
     * Найти страницу по слагу. Возвращает null, если её нет.
     */
    public function pageBySlug(string $slug): ?Page
    {
        $pages = $this->pages();
        return $pages[$slug] ?? null;
    }

    /**
     * Установить порядок страниц. Принимает массив слагов.
     * Несуществующие слаги сохраняются, но при отображении игнорируются.
     */
    public function setPageOrder(array $slugs): void
    {
        $this->pageOrder = array_values(array_unique($slugs));
    }

    /**
     * Назначить главную страницу.
     */
    public function setHomePage(string $slug): void
    {
        if ($slug !== '' && !Page::isValidSlug($slug)) {
            throw new InvalidArgumentException('Недопустимый слаг: ' . $slug);
        }
        $this->homePage = $slug;
    }

    /**
     * Пересобрать pageOrder из фактических файлов страниц,
     * сохранив относительный порядок уже известных слагов.
     * Возвращает итоговый порядок.
     *
     * @return string[]
     */
    public function syncPageOrder(): array
    {
        $existing = array_keys(Page::loadAll());

        $ordered = [];
        foreach ($this->pageOrder as $slug) {
            if (in_array($slug, $existing, true)) {
                $ordered[] = $slug;
            }
        }
        foreach ($existing as $slug) {
            if (!in_array($slug, $ordered, true)) {
                $ordered[] = $slug;
            }
        }

        $this->pageOrder = $ordered;
        return $ordered;
    }

    // ---------- Приватные утилиты ----------

    private static function normalizeTheme(string $theme): string
    {
        return in_array($theme, self::THEMES, true) ? $theme : 'light';
    }

    private static function normalizeColor(string $color): string
    {
        if (!preg_match('/^#?[0-9a-fA-F]{3,8}$/', $color)) {
            return '#3b82f6';
        }
        return str_starts_with($color, '#') ? $color : '#' . $color;
    }
}