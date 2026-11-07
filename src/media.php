<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Emizh\Classes\Storage;

$file = $_GET['file'] ?? '';

// Защита от Path Traversal и проверка имени файла
if (!preg_match('/^[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]+$/', $file)) {
    http_response_code(400);
    exit('Недопустимое имя файла');
}

$filePath = Storage::dataPath() . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $file;

if (!is_file($filePath)) {
    http_response_code(404);
    exit('Файл не найден');
}

// Определяем MIME-тип по расширению (в обход finfo, чтобы работало на Windows)
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimes = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
];

$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=31536000');

readfile($filePath);
exit;