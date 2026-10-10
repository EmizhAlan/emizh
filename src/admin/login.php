<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;

if (!isPost()) {
    adminRedirect('./');
}

Csrf::requireValid();

if (!Auth::ownerExists()) {
    $_SESSION['flash_error'] = 'Владелец ещё не создан.';
    adminRedirect('./');
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['flash_error'] = 'Заполните все поля.';
    adminRedirect('./');
}

if (!Auth::login($username, $password)) {
    sleep(1);
    $_SESSION['flash_error'] = 'Неверный логин или пароль.';
    adminRedirect('./');
}

RateLimit::clear($rateKey);

$_SESSION['flash_success'] = 'Вы вошли в админку.';
adminRedirect('./');