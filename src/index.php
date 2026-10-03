<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Emizh\Classes\Version;

echo 'Версия emizh: ' . Version::current();