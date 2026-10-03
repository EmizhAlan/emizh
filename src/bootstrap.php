<?php

declare(strict_types=1);

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