<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;

if (!isPost()) {
    adminRedirect('./');
}

Csrf::requireValid();

if (Auth::ownerExists()) {
    $_SESSION['flash_error'] = 'Владелец уже создан. Перерегистрация невозможна.';
    adminRedirect('./');
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$passwordRepeat = (string)($_POST['password_repeat'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['flash_error'] = 'Заполните все поля.';
    adminRedirect('./');
}

if ($password !== $passwordRepeat) {
    $_SESSION['flash_error'] = 'Пароли не совпадают.';
    adminRedirect('./');
}

try {
    Auth::createOwner($username, $password);
} catch (\Throwable $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    adminRedirect('./');
}

Auth::login($username, $password);

$_SESSION['flash_success'] = 'Владелец создан. Добро пожаловать!';
adminRedirect('./');