<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;

Auth::startSession();
Auth::touchSession();

// Забираем flash-сообщения и сразу чистим
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
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