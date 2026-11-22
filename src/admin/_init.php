<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;

Auth::startSession();
Auth::touchSession();

// Забираем flash-сообщения и сразу чистим
$GLOBALS['flashError'] = $_SESSION['flash_error'] ?? null;
$GLOBALS['flashSuccess'] = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Удобные обёртки
function adminRedirect(string $path = './'): never
{
    header('Location: ' . $path);
    exit;
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function csrfField(): string
{
    return Csrf::field();
}

/**
 * Единый рендер flash-сообщений (успех/ошибка).
 *
 * Возвращает HTML-блоки, если в сессии есть соответствующие записи.
 * Success-сообщение автоматически помечается на скрытие (JS-класс).
 */
function adminFlash(): string
{
    $out = '';

    if (!empty($GLOBALS['flashError'])) {
        $out .= '<div class="admin-alert admin-alert-error" role="alert">'
            . '<span class="admin-alert-text">'
            . htmlspecialchars((string)$GLOBALS['flashError'], ENT_QUOTES, 'UTF-8')
            . '</span>'
            . '<button type="button" class="admin-alert-close" aria-label="Закрыть">&times;</button>'
            . '</div>';
    }

    if (!empty($GLOBALS['flashSuccess'])) {
        $out .= '<div class="admin-alert admin-alert-success admin-alert-autohide" role="status">'
            . '<span class="admin-alert-text">'
            . htmlspecialchars((string)$GLOBALS['flashSuccess'], ENT_QUOTES, 'UTF-8')
            . '</span>'
            . '<button type="button" class="admin-alert-close" aria-label="Закрыть">&times;</button>'
            . '</div>';
    }

    return $out;
}

/**
 * URL возврата — относительная ссылка на страницу, с которой пришли,
 * или fallback, если реферер внешний/отсутствует/указывает на ту же страницу.
 */
function adminBackUrl(string $fallback = 'dashboard.php'): string
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if ($referer === '' || $host === '') {
        return $fallback;
    }

    $parsed = parse_url($referer);
    if (strcasecmp((string)($parsed['host'] ?? ''), $host) !== 0) {
        return $fallback;
    }

    $path = (string)($parsed['path'] ?? '');
    $adminPos = stripos($path, '/admin/');
    if ($adminPos === false) {
        return $fallback;
    }

    $relative = substr($path, $adminPos + 7);
    if ($relative === '' || $relative === $fallback) {
        return $fallback;
    }

    // Не возвращаемся на саму себя — иначе бесконечный цикл «Отмена → эта же страница»
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($relative === $current) {
        return $fallback;
    }

    return $relative . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
}

/**
 * HTML кнопки «Назад» с учётом фактического источника перехода.
 *
 * Если пользователь пришёл из другой страницы админки того же хоста —
 * возвращаем ссылку на HTTP_REFERER. Иначе — на fallback.
 *
 * @param string $fallback Куда вести, если реферер внешний или отсутствует
 * @param string $labelFallback Текст при отсутствии внутреннего реферера
 */
function adminBack(string $fallback = 'dashboard.php', string $labelFallback = '← Панель управления'): string
{
    $href = adminBackUrl($fallback);
    $label = ($href === $fallback) ? $labelFallback : '← Назад';

    return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="admin-back">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '</a>';
}

/**
 * Форматирует дату из ISO 8601 в читаемый вид.
 */
function self_formatDate(string $iso): string
{
    $timestamp = strtotime($iso);
    if ($timestamp === false) {
        return $iso;
    }

    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) {
        return 'только что';
    }

    if ($diff < 3600) {
        $min = (int)floor($diff / 60);
        return $min . ' мин назад';
    }

    if (date('Y-m-d', $timestamp) === date('Y-m-d')) {
        return 'сегодня, ' . date('H:i', $timestamp);
    }

    if (date('Y-m-d', $timestamp) === date('Y-m-d', $now - 86400)) {
        return 'вчера, ' . date('H:i', $timestamp);
    }

    return date('d.m.Y', $timestamp);
}