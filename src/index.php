<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Emizh\Classes\Version;
use Emizh\Classes\Site;
use Emizh\Classes\Page;
use Emizh\Classes\Markdown;
use Emizh\Classes\Auth;

/**
 * Экранирование для безопасного вывода в HTML.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---- Загружаем сайт ----
$site = Site::load();

// ---- Определяем, какую страницу показывать ----
$requestedSlug = isset($_GET['page']) ? (string) $_GET['page'] : '';
$markdown = new Markdown();
$page = null;
$notFound = false;

if ($requestedSlug === '') {
    // Главная
    $page = $site->home();
} elseif (Page::isValidSlug($requestedSlug)) {
    // Конкретная страница
    $page = $site->pageBySlug($requestedSlug);
    if ($page === null) {
        $notFound = true;
    }
} else {
    // Некорректный слаг
    $notFound = true;
}

// ---- Мета для <head> ----
$siteTitle   = $site->title();
$pageTitle   = $page ? $page->title() : ($notFound ? 'Страница не найдена' : $siteTitle);
$fullTitle   = $page && $requestedSlug !== '' ? $pageTitle . ' — ' . $siteTitle : $siteTitle;
$description = $site->description();
$theme       = $site->theme();
$accentColor = $site->accentColor();
$font        = $site->font();

?><!DOCTYPE html>
<html lang="ru" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullTitle) ?></title>
    <?php if ($description !== ''): ?>
        <meta name="description" content="<?= e($description) ?>">
    <?php endif; ?>
    <?php if ($site->author() !== ''): ?>
        <meta name="author" content="<?= e($site->author()) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/app.css">
    <style>
        :root {
            --accent: <?= e($accentColor) ?>;
            --font: <?= e($font) ?>, system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container">
        <a href="./" class="site-title"><?= e($siteTitle) ?></a>

        <nav class="site-nav">
            <?php foreach ($site->pages() as $slug => $navPage): ?>
                <?php
                    $isActive = $page !== null && $page->slug() === $slug;
                    $href = './?page=' . urlencode($slug);
                ?>
                <a href="<?= e($href) ?>" class="nav-link<?= $isActive ? ' is-active' : '' ?>">
                    <?= e($navPage->title()) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">

        <?php if ($notFound): ?>

            <article class="page">
                <h1 class="page-title">Страница не найдена</h1>
                <p>Возможно, она была удалена или вы перешли по неверной ссылке.</p>
                <p><a href="./">← Вернуться на главную</a></p>
            </article>

        <?php elseif ($page === null): ?>

            <article class="page">
                <h1 class="page-title">Сайт пока пуст</h1>
                <p>Здесь пока нет ни одной страницы.</p>
            </article>

        <?php else: ?>

            <article class="page">
                <h1 class="page-title"><?= e($page->title()) ?></h1>
                <div class="markdown">
                    <?= $markdown->render($page->content()) ?>
                </div>
            </article>

        <?php endif; ?>

    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <span><?= e($siteTitle) ?></span>
        <?php if ($site->author() !== ''): ?>
            <span> · <?= e($site->author()) ?></span>
        <?php endif; ?>
        <span class="version">emizh <?= e(Version::current()) ?></span>
    </div>
</footer>

</body>
</html>

<?php
// ВРЕМЕННЫЙ БЛОК — удалить после проверки
if (isset($_GET['test_auth'])) {
    echo '<pre style="background:#f4f4f4;padding:20px;margin:20px;">';
    echo "ownerExists: " . (Auth::ownerExists() ? 'да' : 'нет') . "\n";
    echo "ownerFilePath: " . Auth::ownerFilePath() . "\n\n";

    if (!Auth::ownerExists()) {
        echo "Создаём тестового владельца...\n";
        Auth::createOwner('admin', 'secret123');
        echo "Создан.\n\n";
    }

    echo "Проверка логина/пароля:\n";
    echo "  admin/secret123: " . (Auth::verify('admin', 'secret123') ? '✓' : '✗') . "\n";
    echo "  admin/wrong:     " . (Auth::verify('admin', 'wrong') ? '✓' : '✗') . "\n";
    echo "  baduser/secret:  " . (Auth::verify('baduser', 'secret123') ? '✓' : '✗') . "\n\n";

    echo "username: " . (Auth::username() ?? '(нет)') . "\n";
    echo "secret: " . substr(Auth::secret() ?? '', 0, 16) . "...\n\n";

    echo "Проверка валидации логинов:\n";
    foreach (['admin', 'ab', 'user_name', 'user name', 'кириллица', str_repeat('a', 40)] as $u) {
        echo "  " . (Auth::isValidUsername($u) ? '✓' : '✗') . " " . $u . "\n";
    }

    echo '</pre>';
    exit;
}