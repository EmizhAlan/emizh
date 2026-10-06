<?php

declare(strict_types=1);

namespace Emizh\Classes;

/**
 * Парсер markdown в HTML.
 *
 * Собственная реализация, без внешних библиотек.
 * На этом этапе — каркас: нормализация, экранирование HTML,
 * обёртка в абзац. Реальные правила разбора добавляются в следующих версиях.
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
        $text = $this->wrapInParagraph($text);

        return $text;
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
     * Обернуть текст в абзац.
     *
     * Временная реализация — весь текст в один <p>.
     * Заменится на полноценный разбор по строкам в следующих версиях.
     */
    private function wrapInParagraph(string $text): string
    {
        if ($text === '') {
            return '';
        }

        return '<p>' . $text . '</p>';
    }
}