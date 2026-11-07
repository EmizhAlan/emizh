<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;

Auth::requireLogin();

if (!isPost()) {
    adminRedirect('password.php');
}

Csrf::requireValid();

$current = (string)($_POST['current_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$repeat = (string)($_POST['new_password_repeat'] ?? '');

if ($current === '' || $new === '' || $repeat === '') {
    $_SESSION['flash_error'] = 'Заполните все поля.';
    adminRedirect('password.php');
}

if ($new !== $repeat) {
    $_SESSION['flash_error'] = 'Новые пароли не совпадают.';
    adminRedirect('password.php');
}

if ($new === $current) {
    $_SESSION['flash_error'] = 'Новый пароль должен отличаться от текущего.';
    adminRedirect('password.php');
}

if (!Auth::changePassword($current, $new)) {
    $_SESSION['flash_error'] = 'Текущий пароль указан неверно.';
    adminRedirect('password.php');
}

Auth::login((string)Auth::username(), $new);

$_SESSION['flash_success'] = 'Пароль изменён.';
adminRedirect('password.php');