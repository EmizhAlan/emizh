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
