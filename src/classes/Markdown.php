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

        $joined = implode(' ', array_map('trim', $buffer));
        $result[] = '<p>' . $joined . '</p>';
        $buffer = [];
    }
}