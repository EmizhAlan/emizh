<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Emizh\Classes\Auth;

Auth::startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./');
    exit;
}

if (!Auth::ownerExists()) {
    $_SESSION['flash_error'] = 'Владелец ещё не создан.';
    header('Location: ./');
    exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['flash_error'] = 'Заполните все поля.';
    header('Location: ./');
    exit;
}

if (!Auth::login($username, $password)) {
    // Небольшая задержка — защита от перебора
    sleep(1);
    $_SESSION['flash_error'] = 'Неверный логин или пароль.';
    header('Location: ./');
    exit;
}

$_SESSION['flash_success'] = 'Вы вошли в админку.';
header('Location: ./');
exit;