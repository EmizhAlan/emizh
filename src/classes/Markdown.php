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
     * Разбор текста на блоки: заголовки, абзацы.
     */
    private function parseBlocks(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $lines = explode("\n", $text);
        $result = [];
        $paragraphBuffer = [];

        foreach ($lines as $line) {
            $heading = $this->parseHeading($line);

            if ($heading !== null) {
                // Прежде чем начать заголовок, выгружаем накопленный абзац
                $this->flushParagraph($paragraphBuffer, $result);
                $result[] = $heading;
                continue;
            }

            // Пустая строка — тоже выгружаем абзац
            if (trim($line) === '') {
                $this->flushParagraph($paragraphBuffer, $result);
                continue;
            }

            $paragraphBuffer[] = $line;
        }

        // Не забываем выгрузить остаток
        $this->flushParagraph($paragraphBuffer, $result);

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
     * Разбор внутристрочной разметки: жирный, курсив, код.
     *
     * Порядок важен: сначала обрабатываем **, потом *, потом `.
     */
    private function parseInline(string $text): string
    {
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
}