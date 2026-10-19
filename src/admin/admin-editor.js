/**
 * Живой предпросмотр markdown в редакторе.
 *
 * Отправляет содержимое на сервер (preview.php) и вставляет
 * полученный HTML. Это гарантирует, что предпросмотр совпадает
 * с тем, что будет на сайте.
 */
(function () {
    "use strict";

    const textarea = document.getElementById("editor-content");
    const preview = document.getElementById("editor-preview");
    const form = document.getElementById("editor-form");

    if (!textarea || !preview) {
        return;
    }

    // CSRF-токен берём из скрытого поля формы
    let csrfToken = "";
    if (form) {
        const csrfInput = form.querySelector('input[name="_csrf"]');
        if (csrfInput) {
            csrfToken = csrfInput.value;
        }
    }

    let timeoutId = null;
    let currentAbort = null;

    /**
     * Отправить содержимое на сервер и получить HTML.
     */
    function updatePreview() {
        // Отменяем предыдущий запрос, если он ещё идёт
        if (currentAbort) {
            currentAbort.abort();
        }

        const controller = new AbortController();
        currentAbort = controller;

        const body = new URLSearchParams();
        body.append("content", textarea.value);
        body.append("_csrf", csrfToken);

        fetch("preview.php", {
            method: "POST",
            body: body,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            signal: controller.signal,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("HTTP " + response.status);
                }
                return response.text();
            })
            .then(function (html) {
                preview.innerHTML = html;
            })
            .catch(function (error) {
                if (error.name === "AbortError") {
                    return; // отменённый запрос — это нормально
                }
                preview.innerHTML = '<p style="color:#999">Не удалось обновить предпросмотр</p>';
                console.error(error);
            })
            .finally(function () {
                if (currentAbort === controller) {
                    currentAbort = null;
                }
            });
    }

    /**
     * Debounce — ждём 300 мс после последнего ввода,
     * чтобы не отправлять запрос на каждое нажатие клавиши.
     */
    function scheduleUpdate() {
        if (timeoutId !== null) {
            clearTimeout(timeoutId);
        }
        timeoutId = setTimeout(function () {
            timeoutId = null;
            updatePreview();
        }, 300);
    }

    // Подписываемся на ввод
    textarea.addEventListener("input", scheduleUpdate);

    // Первичный рендер при загрузке (когда поле уже заполнено)
    if (textarea.value.trim() !== "") {
        updatePreview();
    }
})();