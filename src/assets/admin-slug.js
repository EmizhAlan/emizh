/**
 * Автогенерация слага из заголовка.
 *
 * Алгоритм полностью совпадает с Page::slugify() (PHP).
 * Используется на страницах new.php (автогенерация при вводе) и
 * edit.php (кнопка regenerate).
 */
var EmizhSlug = (function () {
    "use strict";

    // Карта транслитерации — должна совпадать с Page::slugify()
    var map = {
        // Русский
        'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
        'е': 'e', 'ё': 'yo', 'ж': 'zh', 'з': 'z', 'и': 'i',
        'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
        'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't',
        'у': 'u', 'ф': 'f', 'х': 'kh', 'ц': 'ts', 'ч': 'ch',
        'ш': 'sh', 'щ': 'shch', 'ъ': '', 'ы': 'y', 'ь': '',
        'э': 'e', 'ю': 'yu', 'я': 'ya',
        // Украинский
        'є': 'ye', 'і': 'i', 'ї': 'yi',
        // Белорусский
        'ў': 'u',
        // Специальные символы
        '—': '-', '–': '-', '€': 'eur',
        // Кавычки
        '«': '', '»': '', '„': '', '"': '',
        '‘': '', '’': '',
        // Прочее
        '@': 'at', '&': 'and', '№': 'no', '+': 'plus'
    };

    /**
     * Преобразовать заголовок в слаг.
     */
    function slugify(text) {
        var slug = String(text || '')
            .split('')
            .map(function (ch) { return map[ch] !== undefined ? map[ch] : ch; })
            .join('');

        // CamelCase → camel-case
        slug = slug.replace(/([a-z])([A-Z])/g, '$1-$2');

        // Нижний регистр
        slug = slug.toLowerCase();

        // Убираем всё, кроме латиницы, цифр, пробелов и дефисов
        slug = slug.replace(/[^a-z0-9\s-]+/g, '');

        // Пробелы и группы дефисов → один дефис
        slug = slug.replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '');

        // Обрезаем до 64 символов по границе слова
        if (slug.length > 64) {
            var cut = slug.substring(0, 64);
            var lastDash = cut.lastIndexOf('-');
            slug = lastDash > 20 ? cut.substring(0, lastDash) : cut;
            slug = slug.replace(/^-+|-+$/g, '');
        }

        return slug;
    }

    /**
     * Сделать слаг уникальным относительно существующих.
     */
    function unique(base, existing) {
        var list = existing || [];
        if (base === '' || list.indexOf(base) === -1) {
            return base;
        }

        var n = 2;
        while (list.indexOf(base + '-' + n) !== -1) {
            n++;
        }
        return base + '-' + n;
    }

    /**
     * Полный цикл: заголовок → валидный уникальный слаг.
     */
    function fromTitle(title, existing) {
        var slug = unique(slugify(title), existing);

        if (slug === '' || !/^[a-z0-9-]{1,64}$/.test(slug)) {
            // Fallback — детерминированный по заголовку
            var hash = 0;
            var s = String(title || '');
            for (var i = 0; i < s.length; i++) {
                hash = (hash * 31 + s.charCodeAt(i)) >>> 0;
            }
            slug = unique('page-' + hash.toString(36).substring(0, 8), existing);
        }

        return slug;
    }

    /**
     * Привязать автогенерацию к полям формы.
     *
     * На странице создания: слаг обновляется при вводе заголовка,
     * пока пользователь не отредактировал его вручную.
     *
     * @param {HTMLInputElement} titleInput
     * @param {HTMLInputElement} slugInput
     * @param {string[]} existingSlugs
     */
    function bind(titleInput, slugInput, existingSlugs) {
        if (!titleInput || !slugInput) {
            return;
        }

        var manuallyEdited = false;

        titleInput.addEventListener('input', function () {
            if (!manuallyEdited) {
                slugInput.value = fromTitle(titleInput.value, existingSlugs);
            }
        });

        slugInput.addEventListener('input', function () {
            manuallyEdited = true;
        });
    }

    return {
        slugify: slugify,
        unique: unique,
        fromTitle: fromTitle,
        bind: bind
    };
})();

