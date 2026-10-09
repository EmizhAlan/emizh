<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;

if (!isPost()) {
    adminRedirect('./');
}

Csrf::requireValid();

Auth::logout();
adminRedirect('./');