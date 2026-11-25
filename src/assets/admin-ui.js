/**
 * UI-улучшения админки: авто-скрытие success-уведомлений,
 * ручное закрытие alert-блоков, плавное появление.
 */
(function () {
    "use strict";

    var AUTOHIDE_MS = 4000;
    var FADE_MS = 220;

    function fadeOutAndRemove(el) {
        if (!el || el.dataset.closing === "1") return;
        el.dataset.closing = "1";

        el.classList.add("admin-alert-closing");

        // Подожём завершения CSS-анимации, затем удалим узел
        var removed = false;
        var done = function () {
            if (removed) return;
            removed = true;
            if (el.parentNode) el.parentNode.removeChild(el);
        };

        el.addEventListener("animationend", done);
        setTimeout(done, FADE_MS + 60); // страховка на случай, если анимация не проигралась
    }

    function closeAll() {
        var alerts = document.querySelectorAll(".admin-alert");
        alerts.forEach(function (el) {
            fadeOutAndRemove(el);
        });
    }

    function bindCloseButtons() {
        var buttons = document.querySelectorAll(".admin-alert-close");
        buttons.forEach(function (btn) {
            btn.addEventListener("click", function () {
                var alert = btn.closest(".admin-alert");
                fadeOutAndRemove(alert);
            });
        });
    }

    function scheduleAutoHide() {
        var auto = document.querySelectorAll(".admin-alert-autohide");
        auto.forEach(function (el) {
            setTimeout(function () {
                fadeOutAndRemove(el);
            }, AUTOHIDE_MS);
        });
    }

    function init() {
        bindCloseButtons();
        scheduleAutoHide();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    // Публичный API на случай, если понадобится программно закрыть все
    window.EmizhUi = {
        closeAllAlerts: closeAll,
        fadeOutAndRemove: fadeOutAndRemove
    };
})();

/**
 * Обобщённая модалка подтверждения.
 *
 * Режим 1 (ссылка-триггер, delete):
 *   <a data-confirm="#modal" data-confirm-title="…" data-confirm-message="…"
 *      data-confirm-warning="…" data-confirm-submit="…">Удалить</a>
 *   Query-параметры href переносятся в поля формы модалки.
 *
 * Режим 2 (форма-триггер, update):
 *   <form data-confirm data-confirm-title="…" data-confirm-message="…"
 *         data-confirm-warning="…" data-confirm-submit="…">
 *   Submit формы предотвращается → модалка → подтверждение отправляет форму.
 *
 * Без JS: ссылки и формы работают как раньше.
 */
(function () {
    "use strict";

    var active = { modal: null, onConfirm: null, lastFocused: null };

    // ---- Утилиты ----

    function focusableSelector() {
        return 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),'
             + 'textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';
    }

    function focusTrap(e) {
        if (e.key !== 'Tab' || !active.modal) return;

        var dialog = active.modal.querySelector('.admin-modal-dialog');
        var focusable = dialog.querySelectorAll(focusableSelector());
        if (focusable.length === 0) return;

        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (e.shiftKey) {
            if (document.activeElement === first || !dialog.contains(document.activeElement)) {
                e.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last || !dialog.contains(document.activeElement)) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    function escHandler(e) {
        if (e.key === 'Escape' && active.modal) {
            close();
        }
    }

    function bodyScrollLock() {
        if (!active.modal) {
            document.body.style.overflow = '';
            return;
        }
        document.body.style.overflow = 'hidden';
    }

    function close() {
        var modal = active.modal;
        if (!modal || modal.dataset.closing === "1") return;

        var lastFocused = active.lastFocused;

        // Сбрасываем состояние сразу — иначе bodyScrollLock() не снимет блокировку
        active.modal = null;
        active.onConfirm = null;
        active.lastFocused = null;

        modal.dataset.closing = "1";
        modal.classList.add("admin-modal-closing");

        var removed = false;
        var done = function () {
            if (removed) return;
            removed = true;
            modal.setAttribute("hidden", "");
            modal.classList.remove("admin-modal-closing");
            delete modal.dataset.closing;
            modal.removeEventListener("animationend", done);
        };
        modal.addEventListener("animationend", done);
        setTimeout(done, 200);

        bodyScrollLock(); // active.modal === null → снимает overflow

        if (lastFocused && lastFocused.focus) {
            lastFocused.focus();
        }
    }

    // ---- Публичное API ----

    /**
     * @param {HTMLElement} modalEl — .admin-modal
     * @param {object} opts
     * @param {string} opts.title
     * @param {string} opts.message
     * @param {string} [opts.warning] — если пустой, блок скрыт
     * @param {string} [opts.submitText] — текст кнопки подтверждения
     * @param {string} [opts.submitClass] — класс кнопки (по умолчанию admin-btn-danger)
     * @param {Function} [opts.onConfirm] — вызывается при подтверждении
     * @param {HTMLElement} [opts.trigger] — элемент, вернувший фокус при закрытии
     */
    function open(modalEl, opts) {
        if (!modalEl) return;

        var titleEl = modalEl.querySelector('[data-confirm-title]');
        var messageEl = modalEl.querySelector('[data-confirm-message]');
        var warningEl = modalEl.querySelector('[data-confirm-warning]');
        var submitBtn = modalEl.querySelector('[data-confirm-submit-btn]');

        if (titleEl) titleEl.textContent = opts.title || 'Подтвердите действие';
        if (messageEl) messageEl.textContent = opts.message || '';

        if (opts.warning) {
            if (warningEl) {
                warningEl.textContent = opts.warning;
                warningEl.removeAttribute("hidden");
            }
        } else if (warningEl) {
            warningEl.setAttribute("hidden", "");
            warningEl.textContent = '';
        }

        var btnText = opts.submitText || 'Подтвердить';
        var btnClass = opts.submitClass || 'admin-btn admin-btn-danger';
        if (submitBtn) {
            submitBtn.textContent = btnText;
            submitBtn.className = btnClass;
        }

        active.modal = modalEl;
        active.onConfirm = opts.onConfirm || function () { close(); };
        active.lastFocused = opts.trigger || document.activeElement;

        modalEl.removeAttribute("hidden");
        bodyScrollLock();

        // Фокус на первую кнопку (Отмена)
        var cancelBtn = modalEl.querySelector('[data-modal-close]');
        if (cancelBtn) cancelBtn.focus();
    }

    function confirm() {
        if (active.onConfirm) {
            active.onConfirm();
            close();
        } else {
            close();
        }
    }

    // ---- Делегирование кликов (ссылка-триггер) ----

    document.addEventListener("click", function (e) {
        var trigger = e.target.closest("[data-confirm]");
        if (!trigger) return;

        var href = trigger.getAttribute("href");
        if (!href) return; // без href — не мешаем

        e.preventDefault();

        var modalId = trigger.getAttribute("data-confirm");
        var modal = document.querySelector(modalId);
        if (!modal) return; // модалка не найдена — пропускаем (graceful degradation)

        // Заполняем тексты
        var opts = {
            title: trigger.getAttribute("data-confirm-title") || "Подтвердите действие",
            message: trigger.getAttribute("data-confirm-message") || "",
            warning: trigger.getAttribute("data-confirm-warning") || "",
            submitText: trigger.getAttribute("data-confirm-submit") || "Подтвердить",
            trigger: trigger
        };

        // Перенос query-параметров в поля формы модалки
        try {
            var url = new URL(href, location.href);
            var form = modal.querySelector("form");
            if (form) {
                form.action = url.pathname + url.search; // без hash
                url.searchParams.forEach(function (val, key) {
                    var input = form.elements[key];
                    if (input) {
                        input.value = val;
                    }
                });
            }
        } catch (_) { /* не мешаем */ }

        opts.onConfirm = function () {
            var form = modal.querySelector("form");
            if (form) form.submit(); // нативный submit — в обход preventDefault
        };

        open(modal, opts);
    });

    // ---- Делегирование submit (форма-триггер) ----

    document.addEventListener("submit", function (e) {
        var form = e.target;
        if (!form.hasAttribute("data-confirm")) return;

        e.preventDefault();

        var modalId = form.getAttribute("data-confirm");
        var modal = document.querySelector(modalId);
        if (!modal) return;

        // Скопируем action и enctype формы-триггера в форму модалки
        var modalForm = modal.querySelector("form");
        if (modalForm) {
            modalForm.action = form.action;
            modalForm.enctype = form.enctype;
        }

        var opts = {
            title: form.getAttribute("data-confirm-title") || "Подтвердите действие",
            message: form.getAttribute("data-confirm-message") || "",
            warning: form.getAttribute("data-confirm-warning") || "",
            submitText: form.getAttribute("data-confirm-submit") || "Подтвердить",
            trigger: form
        };

        opts.onConfirm = function () {
            form.submit(); // нативный — в обход preventDefault
        };

        open(modal, opts);
    });

    // ---- Backdrop click ----

    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("admin-modal-backdrop")) {
            close();
        }
        if (e.target.hasAttribute("data-modal-close")) {
            close();
        }
    });

    // ---- Кнопка подтверждения (внутри модалки) ----

    document.addEventListener("click", function (e) {
        var btn = e.target.closest("[data-confirm-submit-btn]");
        if (!btn) return;

        e.preventDefault();
        confirm();
    });

    // ---- Keyboard ----

    document.addEventListener("keydown", focusTrap);
    document.addEventListener("keydown", escHandler);

    // ---- Публичный API ----

    window.EmizhConfirm = {
        open: open,
        close: close,
        confirm: confirm
    };
})();
