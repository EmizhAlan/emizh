/**
 * Логика редактора страниц (предпросмотр и загрузка изображений).
 * Файл должен находиться в src/assets/admin-editor.js
 */
(function () {
    "use strict";

    // === 1. Инициализация элементов ===
    const textarea = document.getElementById("editor-content");
    const preview = document.getElementById("editor-preview");
    const form = document.getElementById("editor-form");

    // Если это не страница редактора (например, дашборд), выходим
    if (!textarea || !preview) {
        return;
    }

    // Получаем CSRF-токен из формы (он нужен и для предпросмотра, и для загрузки)
    let csrfToken = "";
    if (form) {
        const csrfInput = form.querySelector('input[name="_csrf"]');
        if (csrfInput) {
            csrfToken = csrfInput.value;
        }
    }

    // === 2. Логика предпросмотра markdown ===
    let timeoutId = null;
    let currentAbort = null;

    function updatePreview() {
        // Отменяем предыдущий запрос, если он ещё не завершился
        if (currentAbort) {
            currentAbort.abort();
        }

        const controller = new AbortController();
        currentAbort = controller;

        const body = new URLSearchParams();
        body.append("content", textarea.value);
        if (csrfToken) {
            body.append("_csrf", csrfToken);
        }

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
                    return; // Игнорируем отменённые запросы
                }
                preview.innerHTML = '<p style="color:#999">Не удалось обновить предпросмотр</p>';
                console.error("Preview error:", error);
            })
            .finally(function () {
                if (currentAbort === controller) {
                    currentAbort = null;
                }
            });
    }

    function schedulePreviewUpdate() {
        if (timeoutId !== null) {
            clearTimeout(timeoutId);
        }
        // Ждём 300 мс после последнего нажатия клавиши
        timeoutId = setTimeout(function () {
            timeoutId = null;
            updatePreview();
        }, 300);
    }

    // Подписываемся на ввод текста
    textarea.addEventListener("input", schedulePreviewUpdate);

    // Первичный рендер при загрузке страницы
    if (textarea.value.trim() !== "") {
        updatePreview();
    }


    // === 3. Логика загрузки изображений ===
    const uploadInput = document.getElementById("image-upload");
    const uploadStatus = document.getElementById("upload-status");

    if (uploadInput) {
        uploadInput.addEventListener("change", function () {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append("image", file);
            
            // Передаём CSRF-токен для защиты
            if (csrfToken) {
                formData.append("_csrf", csrfToken);
            } else {
                console.warn("CSRF-токен не найден! Загрузка может быть отклонена.");
            }

            if (uploadStatus) {
                uploadStatus.textContent = "Загрузка...";
                uploadStatus.style.color = "#666";
            }

            fetch("upload.php", {
                method: "POST",
                body: formData
            })
            .then(async function (res) {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Ответ сервера (не JSON):", text);
                    throw new Error("Сервер вернул некорректный ответ. Проверьте консоль.");
                }
            })
            .then(function (data) {
                if (data.error) {
                    alert("Ошибка: " + data.error);
                    if (uploadStatus) uploadStatus.textContent = "";
                    return;
                }

                // Вставляем markdown-код в позицию курсора
                const markdown = `![${file.name}](${data.url})`;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;

                textarea.value = text.substring(0, start) + markdown + text.substring(end);
                
                // Ставим курсор после вставленного текста
                textarea.selectionStart = textarea.selectionEnd = start + markdown.length;
                textarea.focus();

                // Триггерим событие input, чтобы предпросмотр обновился
                textarea.dispatchEvent(new Event("input"));

                if (uploadStatus) {
                    uploadStatus.textContent = "Вставлено!";
                    uploadStatus.style.color = "#16a34a"; // зелёный
                    setTimeout(function () {
                        uploadStatus.textContent = "";
                    }, 2500);
                }
            })
            .catch(function (err) {
                console.error("Upload error:", err);
                alert("Ошибка загрузки: " + err.message);
                if (uploadStatus) {
                    uploadStatus.textContent = "Ошибка";
                    uploadStatus.style.color = "#dc2626"; // красный
                }
            })
            .finally(function () {
                uploadInput.value = ""; // Сбрасываем input, чтобы можно было загрузить тот же файл ещё раз
            });
        });
    }
})();