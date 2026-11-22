<?php

declare(strict_types=1);

/**
 * Инициализация приложения: автозагрузка классов и обработка ошибок.
 *
 * Ошибки PHP посетителям не показываются — они записываются в лог-файл
 * рядом с папкой data/ (ТЗ, раздел 11, пункт 9). Вместо текста ошибки
 * браузер получает нейтральную страницу.
 */

// ---- Режим ошибок: собираем всё, но не выводим посетителям ----
// log_errors оставляем включённым — это страховка для ошибок,
// возникающих до загрузки bootstrap.php (например, синтаксических).

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// ---- Автозагрузчик классов ----

spl_autoload_register(function (string $class): void {
    $prefix = 'Emizh\\Classes\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/classes/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

// ---- Служебные функции обработки ошибок ----

/**
 * Путь к лог-файлу. Лежит на уровень выше корня сайта, рядом с data/.
 */
function emizh_log_path(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'emizh.log';
}

/**
 * Записать строку в лог приложения.
 * Использует Storage::log(), а если класс недоступен — пишет напрямую.
 */
function emizh_log(string $message): void
{
    if (class_exists(\Emizh\Classes\Storage::class)) {
        try {
            \Emizh\Classes\Storage::log($message);
            return;
        } catch (\Throwable) {
            // data/ недоступна — переходим к прямой записи
        }
    }

    @file_put_contents(
        emizh_log_path(),
        '[' . date('c') . '] ' . $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

/**
 * Нейтральная страница вместо текста с путями и стеком вызовов.
 */
function emizh_show_error_page(): void
{
    if (headers_sent()) {
        return;
    }

    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');

    echo '<!DOCTYPE html>'
        . '<html lang="ru"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>Неполадка на сайте</title><style>'
        . 'body{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;'
        . 'margin:80px auto;max-width:560px;padding:0 20px;text-align:center;color:#222}'
        . 'h1{font-size:22px}p{color:#666;line-height:1.6}'
        . '</style></head><body>'
        . '<h1>Временная неполадка</h1>'
        . '<p>Не удалось отобразить страницу. Попробуйте обновить страницу позже.</p>'
        . '</body></html>';
}

// ---- Обработчики ----

// Необработанное исключение: пишем в лог, показываем заглушку.
set_exception_handler(function (\Throwable $e): void {
    emizh_log(sprintf(
        'НЕПЕРЕХВАЧЕНОЕ ИСКЛЮЧЕНИЕ %s: %s в %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    emizh_log('Стек: ' . str_replace("\n", ' | ', $e->getTraceAsString()));

    emizh_show_error_page();
    exit;
});

// Фатальные ошибки (E_ERROR, E_PARSE) исключениями не являются — ловим при выключении.
register_shutdown_function(function (): void {
    $last = error_get_last();
    if ($last === null) {
        return;
    }

    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($last['type'], $fatal, true)) {
        return;
    }

    emizh_log(sprintf(
        'ФАТАЛЬНАЯ ОШИБКА [%d]: %s в %s:%d',
        $last['type'],
        $last['message'],
        $last['file'],
        $last['line']
    ));

    emizh_show_error_page();
});

// Предупреждения и notice тоже отправляем в лог, не прерывая выполнение.
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    // Ошибки, подавленные оператором @, не логируем
    if (!(error_reporting() & $severity)) {
        return false;
    }

    emizh_log(sprintf('PHP [%d]: %s в %s:%d', $severity, $message, $file, $line));

    // Возвращаем false — дальше стандартная обработка PHP
    return false;
});