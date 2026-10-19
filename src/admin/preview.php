<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;
use Emizh\Classes\Markdown;

Auth::requireLogin();

if (!isPost()) {
    http_response_code(405);
    exit;
}

Csrf::requireValid();

$content = (string)($_POST['content'] ?? '');

$markdown = new Markdown();
$html = $markdown->render($content);

header('Content-Type: text/html; charset=UTF-8');
echo $html;
exit;