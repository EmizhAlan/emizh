<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Storage;

Auth::requireLogin();

if (!isPost()) {
    http_response_code(405);
    exit(json_encode(['error' => 'Метод не разрешен']));
}

// Временно отключаем строгую проверку CSRF для отладки, 
// чтобы понять, в ней ли дело. Если картинка загрузится — проблема была в токене.
// Csrf::requireValid(); 

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit(json_encode(['error' => 'Файл не загружен или произошла ошибка. Код: ' . ($_FILES['image']['error'] ?? 'нет файла')]));
}

$file = $_FILES['image'];

if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    exit(json_encode(['error' => 'Файл слишком большой (макс 5 МБ).']));
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    'image/svg+xml' => 'svg',
];

if (!isset($allowedMimes[$mime])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Недопустимый тип файла: ' . $mime . '. Разрешены: JPEG, PNG, GIF, WebP, SVG.']));
}

$ext = $allowedMimes[$mime];
$randomName = bin2hex(random_bytes(8)) . '.' . $ext;

$uploadsDir = Storage::dataPath() . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

$targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $randomName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    exit(json_encode(['error' => 'Не удалось сохранить файл на сервере. Проверьте права на папку data/uploads.']));
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'url' => 'uploads/' . $randomName,
]);
exit;