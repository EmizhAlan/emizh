<?php

declare(strict_types=1);

namespace Emizh\Classes;

/**
 * Парсер markdown в HTML.
 *
 * Собственная реализация, без внешних библиотек.
 */
final class Markdown
{
    /**
     * Преобразовать markdown в безопасный HTML.
     */
    public function render(string $markdown): string
    {
        $text = $this->normalize($markdown);
        $text = $this->escapeHtml($text);
        $html = $this->parseBlocks($text);

        return $html;
    }

    /**
     * Нормализация переводов строк.
     */
    private function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        return trim($text);
    }

    /**
     * Экранирование HTML. Защита от XSS.
     */
    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

        /**
     * Разбор текста на блоки: заголовки, списки, код, цитаты, абзацы.
     */
    private function parseBlocks(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $lines = explode("\n", $text);
        $result = [];
        $buffer = [];        // текущий абзац
        $listType = null;    // 'ul' | 'ol'
        $listItems = [];     // накопленные <li>
        $inCodeBlock = false;
        $codeLines = [];
        $codeLang = '';
        $quoteLines = [];    // накопленные строки цитаты

        foreach ($lines as $line) {
            // --- Блок кода ---
            if (preg_match('/^```(.*)$/', $line, $m)) {
                if (!$inCodeBlock) {
                    $this->flushParagraph($buffer, $result);
                    $this->flushList($listType, $listItems, $result);
                    $this->flushQuote($quoteLines, $result);
                    $inCodeBlock = true;
                    $codeLang = trim($m[1]);
                    $codeLines = [];
                } else {
                    $code = implode("\n", $codeLines);
                    $langAttr = $codeLang !== ''
                        ? ' class="language-' . $codeLang . '"'
                        : '';
                    $result[] = '<pre><code' . $langAttr . '>'
                        . $code . '</code></pre>';
                    $inCodeBlock = false;
                    $codeLang = '';
                    $codeLines = [];
                }
                continue;
            }

            if ($inCodeBlock) {
                $codeLines[] = $line;
                continue;
            }

            // --- Цитата ---
            if (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $this->flushParagraph($buffer, $result);
                $this->flushList($listType, $listItems, $result);
                $quoteLines[] = $m[1];
                continue;
            }

            // Закрываем цитату, если встретили что-то другое
            if ($quoteLines !== []) {
                $this->flushQuote($quoteLines, $result);
            }

            // --- Заголовок ---
            $heading = $this->parseHeading($line);
            if ($heading !== null) {
                $this->flushParagraph($buffer, $result);
                $this->flushList($listType, $listItems, $result);
                $result[] = $heading;
                continue;
            }

            // --- Пункт списка ---
            $listMatch = $this->parseListItem($line);
            if ($listMatch !== null) {
                if ($listType !== null && $listType !== $listMatch['type']) {
                    $this->flushParagraph($buffer, $result);
                    $this->flushList($listType, $listItems, $result);
                }
                $this->flushParagraph($buffer, $result);
                $listType = $listMatch['type'];
                $listItems[] = $listMatch['content'];
                continue;
            }

            // --- Пустая строка ---
            if (trim($line) === '') {
                $this->flushParagraph($buffer, $result);
                $this->flushList($listType, $listItems, $result);
                continue;
            }

            // --- Обычная строка ---
            if ($listType !== null) {
                $this->flushList($listType, $listItems, $result);
            }
            $buffer[] = $line;
        }

        // Закрываем всё, что осталось открытым
        if ($inCodeBlock && $codeLines !== []) {
            $code = implode("\n", $codeLines);
            $result[] = '<pre><code>' . $code . '</code></pre>';
        }

        $this->flushQuote($quoteLines, $result);
        $this->flushParagraph($buffer, $result);
        $this->flushList($listType, $listItems, $result);

        return implode("\n", $result);
    }

    /**
     * Распознать заголовок в строке.
     * Возвращает HTML или null, если это не заголовок.
     */
    private function parseHeading(string $line): ?string
    {
        if (!preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches)) {
            return null;
        }

        $level = strlen($matches[1]);
        $content = trim($matches[2]);

        if ($content === '') {
            return null;
        }

        $content = $this->parseInline($content);
        return "<h{$level}>{$content}</h{$level}>";
    }

    /**
     * Сохранить накопленный абзац в результат.
     * Если буфер пуст — ничего не делает.
     */
    private function flushParagraph(array &$buffer, array &$result): void
    {
        if ($buffer === []) {
            return;
        }

        $result[] = $this->buildParagraph($buffer);
        $buffer = [];
    }

    /**
     * Собрать абзац из строк буфера с учётом принудительных переносов.
     */
    private function buildParagraph(array $lines): string
    {
        $parts = [];

        foreach ($lines as $line) {
            $line = trim($line);
        
            // Проверяем: заканчивается ли строка двумя и более пробелами
            if (preg_match('/ {2,}$/', $line)) {
                $parts[] = rtrim($line) . '<br>';
            } else {
                $parts[] = $line;
            }
        }

        // Соединяем части и применяем инлайн-разметку
        $joined = '';
        foreach ($parts as $i => $part) {
            if ($i > 0) {
                $prev = $parts[$i - 1];
                if (!str_ends_with($prev, '<br>')) {
                    $joined .= ' ';
                }
            }
            $joined .= $part;
        }
        
        $joined = $this->parseInline($joined);
        
        return '<p>' . $joined . '</p>';
    }

     /**
     * Разбор внутристрочной разметки: ссылки, жирный, курсив, код.
     *
     * Порядок важен: сначала ссылки, потом ** , потом * , потом ` .
     */
    private function parseInline(string $text): string
    {
        // Ссылки: [текст](url)
        $text = $this->parseLinks($text);

        // Жирный: **текст**
        $text = preg_replace(
            '/\*\*(.+?)\*\*/s',
            '<strong>$1</strong>',
            $text
        );

        // Курсив: *текст*
        $text = preg_replace(
            '/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s',
            '<em>$1</em>',
            $text
        );

        // Инлайн-код: `текст`
        $text = preg_replace(
            '/`([^`]+)`/',
            '<code>$1</code>',
            $text
        );

        return $text;
    }

    /**
     * Разбор ссылок [текст](url).
     */
    private function parseLinks(string $text): string
    {
        return preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function (array $m): string {
                $label = $m[1];
                $url = $this->sanitizeUrl($m[2]);

                $external = $this->isExternalUrl($url);
                $attrs = $external
                    ? ' target="_blank" rel="noopener noreferrer"'
                    : '';

                return '<a href="' . $url . '"' . $attrs . '>' . $label . '</a>';
            },
            $text
        );
    }

    /**
     * Проверка и нормализация URL.
     * Разрешаем только безопасные схемы.
     */
    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);

        // Уже экранировано выше, но на всякий случай
        if ($url === '') {
            return '#';
        }

        // Разрешённые схемы и относительные ссылки
        $allowed = [
            '#',
            '/',
            'http://',
            'https://',
            'mailto:',
            'tel:',
        ];

        foreach ($allowed as $prefix) {
            if (str_starts_with($url, $prefix)) {
                return $url;
            }
        }

        // Всё остальное — блокируем
        return '#';
    }

    /**
     * Является ли URL внешним (ведёт на другой сайт).
     */
    private function isExternalUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

        /**
     * Распознать пункт списка в строке.
     * Возвращает ['type' => 'ul'|'ol', 'content' => string] или null.
     */
    private function parseListItem(string $line): ?array
    {
        // Нумерованный: 1. текст
        if (preg_match('/^\s*\d+\.\s+(.+)$/', $line, $m)) {
            return [
                'type' => 'ol',
                'content' => trim($m[1]),
            ];
        }

        // Маркированный: - текст, * текст, + текст
        if (preg_match('/^\s*[-*+]\s+(.+)$/', $line, $m)) {
            return [
                'type' => 'ul',
                'content' => trim($m[1]),
            ];
        }

        return null;
    }

    /**
     * Сохранить накопленный список в результат.
     */
    private function flushList(?string &$listType, array &$items, array &$result): void
    {
        if ($listType === null || $items === []) {
            $listType = null;
            $items = [];
            return;
        }

        $html = "<{$listType}>";
        foreach ($items as $item) {
            $item = $this->parseInline($item);
            $html .= "<li>{$item}</li>";
        }
        $html .= "</{$listType}>";

        $result[] = $html;

        $listType = null;
        $items = [];
    }

        /**
     * Сохранить накопленную цитату в результат.
     */
    private function flushQuote(array &$lines, array &$result): void
    {
        if ($lines === []) {
            return;
        }

        // Разбиваем цитату на абзацы по пустым строкам
        $paragraphs = [];
        $current = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                if ($current !== []) {
                    $paragraphs[] = $current;
                    $current = [];
                }
            } else {
                $current[] = $line;
            }
        }

        if ($current !== []) {
            $paragraphs[] = $current;
        }

        // Если весь блок был пустой (одни `>` подряд) — ничего не выводим
        if ($paragraphs === []) {
            $lines = [];
            return;
        }

        $html = '<blockquote>';
        foreach ($paragraphs as $para) {
            $joined = implode(' ', array_map('trim', $para));
            $joined = $this->parseInline($joined);
            $html .= '<p>' . $joined . '</p>';
        }
        $html .= '</blockquote>';

        $result[] = $html;
        $lines = [];
    }
}