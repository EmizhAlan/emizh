<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Emizh\Classes\Auth;

Auth::startSession();

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit;
}

// Регистрация доступна только если владельца ещё нет
if (Auth::ownerExists()) {
    $_SESSION['flash_error'] = 'Владелец уже создан. Перерегистрация невозможна.';
    header('Location: ./');
    exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$passwordRepeat = (string)($_POST['password_repeat'] ?? '');

// Базовая валидация
if ($username === '' || $password === '') {
    $_SESSION['flash_error'] = 'Заполните все поля.';
    header('Location: ./');
    exit;
}

if ($password !== $passwordRepeat) {
    $_SESSION['flash_error'] = 'Пароли не совпадают.';
    header('Location: ./');
    exit;
}

try {
    Auth::createOwner($username, $password);
} catch (\Throwable $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ./');
    exit;
}

// Сразу логиним
Auth::login($username, $password);

$_SESSION['flash_success'] = 'Владелец создан. Добро пожаловать!';
header('Location: ./');
exit;