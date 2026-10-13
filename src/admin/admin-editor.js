/**
 * Примитивный предпросмотр markdown в редакторе.
 *
 * Временное решение для v0.3.1. В v0.3.2 превью будет рендериться
 * на сервере через настоящий класс Markdown, чтобы совпадало с итогом.
 */
(function () {
    "use strict";

    const textarea = document.getElementById("editor-content");
    const preview = document.getElementById("editor-preview");

    if (!textarea || !preview) {
        return;
    }

    /**
     * Простейшее экранирование HTML.
     */
    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /**
     * Очень простой рендер markdown для предпросмотра.
     * Поддерживает: заголовки, жирный, курсив, инлайн-код, ссылки, списки, абзацы.
     */
    function renderMarkdown(text) {
        let html = escapeHtml(text);

        // Заголовки
        html = html.replace(/^(#{1,6})\s+(.+)$/gm, function (_, hashes, content) {
            const level = hashes.length;
            return `<h${level}>${content}</h${level}>`;
        });

        // Блоки кода (тройные бэктики)
        html = html.replace(/```([\s\S]*?)```/g, function (_, code) {
            return `<pre><code>${code.trim()}</code></pre>`;
        });

        // Жирный
        html = html.replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>");

        // Курсив
        html = html.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, "<em>$1</em>");

        // Инлайн-код
        html = html.replace(/`([^`]+)`/g, "<code>$1</code>");

        // Ссылки
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');

        // Маркированные списки
        html = html.replace(/^(?:[-*+]\s+(.+)(?:\n|$))+/gm, function (block) {
            const items = block
                .trim()
                .split("\n")
                .map((line) => line.replace(/^[-*+]\s+/, ""))
                .map((line) => `<li>${line}</li>`)
                .join("");
            return `<ul>${items}</ul>`;
        });

        // Нумерованные списки
        html = html.replace(/^(?:\d+\.\s+(.+)(?:\n|$))+/gm, function (block) {
            const items = block
                .trim()
                .split("\n")
                .map((line) => line.replace(/^\d+\.\s+/, ""))
                .map((line) => `<li>${line}</li>`)
                .join("");
            return `<ol>${items}</ol>`;
        });

        // Абзацы — всё, что не является блоком
        html = html
            .split("\n\n")
            .map(function (block) {
                const trimmed = block.trim();
                if (
                    trimmed.startsWith("<h") ||
                    trimmed.startsWith("<ul") ||
                    trimmed.startsWith("<ol") ||
                    trimmed.startsWith("<pre") ||
                    trimmed === ""
                ) {
                    return trimmed;
                }
                return `<p>${trimmed.replace(/\n/g, " ")}</p>`;
            })
            .join("\n");

        return html;
    }

    function updatePreview() {
        preview.innerHTML = renderMarkdown(textarea.value);
    }

    // Обновляем при вводе + сразу при загрузке
    textarea.addEventListener("input", updatePreview);
    updatePreview();
})();