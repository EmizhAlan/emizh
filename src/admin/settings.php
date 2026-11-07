<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

use Emizh\Classes\Auth;
use Emizh\Classes\Csrf;
use Emizh\Classes\Site;
use Emizh\Classes\Storage;

Auth::requireLogin();
$site = Site::load();

if (isPost()) {
    Csrf::requireValid();
    
    $site->setTitle((string)($_POST['title'] ?? 'Мой сайт'));
    $site->setDescription((string)($_POST['description'] ?? ''));
    
    // Функция для загрузки файлов
    $uploadFile = function($fileKey) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileKey];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp', 'ico'])) {
                $randomName = $fileKey . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $uploadsDir = Storage::dataPath() . DIRECTORY_SEPARATOR . 'uploads';
                if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                if (move_uploaded_file($file['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $randomName)) {
                    return 'uploads/' . $randomName;
                }
            }
        }
        return null;
    };

    // Логотип (шапка)
    if (isset($_POST['remove_logo'])) $site->setLogo('');
    if ($path = $uploadFile('logo')) $site->setLogo($path);

    // Favicon (вкладка)
    if (isset($_POST['remove_favicon'])) $site->setFavicon('');
    if ($path = $uploadFile('favicon')) $site->setFavicon($path);
    
    $site->save();
    $_SESSION['flash_success'] = 'Настройки сохранены.';
    adminRedirect('settings.php');
}

?><!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Настройки сайта — emizh</title>
    <link rel="stylesheet" href="../assets/app.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="admin-body">
<div class="admin-container admin-container-wide">
    <div class="admin-topbar">
        <div class="admin-topbar-title"><a href="dashboard.php" class="admin-back">← Панель управления</a></div>
    </div>

    <?php if ($flashError !== null): ?>
        <div class="admin-alert admin-alert-error"><?= htmlspecialchars((string)$flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($flashSuccess !== null): ?>
        <div class="admin-alert admin-alert-success"><?= htmlspecialchars((string)$flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="admin-card">
        <h1 class="admin-title" style="margin-bottom: 24px;">Настройки сайта</h1>
        
        <form method="post" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="admin-settings-grid">
                
                <!-- ЛЕВАЯ КОЛОНКА -->
                <div>
                    <div class="admin-settings-block">
                        <h3>Основное</h3>
                        <label class="admin-label">
                            <span>Название сайта</span>
                            <input type="text" name="title" value="<?= htmlspecialchars($site->title(), ENT_QUOTES, 'UTF-8') ?>" required>
                        </label>
                        <label class="admin-label" style="margin-top: 12px;">
                            <span>Описание (SEO)</span>
                            <textarea name="description" style="min-height: 60px;"><?= htmlspecialchars($site->description(), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </label>
                    </div>

                    <div class="admin-settings-block" style="margin-top: 20px;">
                        <h3>Логотип в шапке сайта</h3>
                        <p style="font-size: 13px; color: #666; margin-top:0;">Появится слева от названия.</p>
                        
                        <?php if ($site->logo() !== ''): ?>
                            <div style="margin-bottom: 12px; padding: 10px; background: #fff; border: 1px solid #ddd; display: inline-block;">
                                <img src="../media.php?file=<?= urlencode(basename($site->logo())) ?>" style="max-height: 40px; display: block;">
                            </div>
                            <label style="display:flex; align-items:center; gap:6px; margin-bottom:12px; cursor:pointer;">
                                <input type="checkbox" name="remove_logo" value="1"> Удалить логотип
                            </label>
                        <?php endif; ?>
                        
                        <input type="file" name="logo" accept="image/*" style="font-size: 13px;">
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА -->
                <div>
                    <div class="admin-settings-block">
                        <h3>Иконка сайта (Favicon)</h3>
                        <p style="font-size: 13px; color: #666; margin-top:0;">Появится на вкладке браузера.</p>
                        
                        <?php if ($site->favicon() !== ''): ?>
                            <div style="margin-bottom: 12px; padding: 10px; background: #fff; border: 1px solid #ddd; display: inline-block;">
                                <img src="../media.php?file=<?= urlencode(basename($site->favicon())) ?>" style="max-height: 32px; display: block;">
                            </div>
                            <label style="display:flex; align-items:center; gap:6px; margin-bottom:12px; cursor:pointer;">
                                <input type="checkbox" name="remove_favicon" value="1"> Удалить иконку
                            </label>
                        <?php endif; ?>
                        
                        <input type="file" name="favicon" accept="image/*,.ico" style="font-size: 13px;">
                    </div>
                </div>

            </div>

            <div style="margin-top: 24px; border-top: 1px solid #eee; padding-top: 20px; text-align: right;">
                <button type="submit" class="admin-btn">Сохранить настройки</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>