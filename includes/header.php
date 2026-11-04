<?php
$navCategories = $pdo->query('SELECT * FROM categories ORDER BY id ASC')->fetchAll();
$flashes = take_flashes();
$me = current_user();
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$searchValue = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$cartCount = cart_count();
$pageTitle = $pageTitle ?? SITE_NAME;
$activeCategory = $activeCategory ?? '';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e(SITE_NAME) ?> — <?= e(SITE_TAGLINE) ?>. تسوّق أزياء وإلكترونيات ومستلزمات المنزل مع توصيل لكل المحافظات.">
    <title><?= e($pageTitle) ?> · <?= e(SITE_NAME) ?></title>
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<a class="skip-link" href="#main">تخطَّ إلى المحتوى</a>

<div class="topbar">
    <div class="container topbar-inner">
        <span class="topbar-item"><?= icon('truck') ?> توصيل لجميع المحافظات خلال 1–3 أيام</span>
        <span class="topbar-item topbar-item--accent"><?= icon('sparkle') ?> شحن مجاني للطلبات فوق <?= (int) FREE_SHIPPING_FROM ?> <?= e(CURRENCY) ?></span>
    </div>
</div>

<header class="site-header">
    <div class="container header-main">
        <a class="brand" href="<?= url('index.php') ?>" aria-label="<?= e(SITE_NAME) ?> — الصفحة الرئيسية">
            <span class="brand-mark">ل</span>
            <span class="brand-copy">
                <span class="brand-name"><?= e(SITE_NAME) ?><span class="brand-dot">.</span></span>
                <span class="brand-tagline"><?= e(SITE_TAGLINE) ?></span>
            </span>
        </a>

        <form class="search" role="search" action="<?= url('index.php') ?>" method="get">
            <label class="sr-only" for="site-search">البحث في المنتجات</label>
            <input id="site-search" type="search" name="q" value="<?= e($searchValue) ?>"
                   placeholder="ابحث عن فستان، حقيبة، سماعات..." autocomplete="off">
            <button class="search-btn" type="submit" aria-label="بحث"><?= icon('search') ?></button>
        </form>

        <div class="header-actions">
            <?php if ($me): ?>
                <details class="account-menu">
                    <summary class="icon-btn icon-btn--stacked">
                        <?= icon('user') ?>
                        <span class="icon-btn-label">حسابي</span>
                    </summary>
                    <div class="menu-panel">
                        <div class="menu-user">
                            <span class="menu-avatar"><?= e(mb_substr($me['name'], 0, 1, 'UTF-8')) ?></span>
                            <div>
                                <strong><?= e($me['name']) ?></strong>
                                <small><?= e($me['email']) ?></small>
                            </div>
                        </div>
                        <a href="<?= url('profile.php') ?>"><?= icon('user') ?> بيانات حسابي</a>
                        <a href="<?= url('orders.php') ?>"><?= icon('box') ?> طلباتي</a>
                        <?php if (is_admin()): ?>
                            <a href="<?= url('admin/index.php') ?>"><?= icon('dashboard') ?> لوحة الإدارة</a>
                        <?php endif; ?>
                        <form method="post" action="<?= url('logout.php') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="menu-logout"><?= icon('logout') ?> تسجيل الخروج</button>
                        </form>
                    </div>
                </details>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm header-auth" href="<?= url('login.php') ?>">دخول</a>
                <a class="btn btn-dark btn-sm header-auth" href="<?= url('register.php') ?>">إنشاء حساب</a>
            <?php endif; ?>

            <a class="icon-btn icon-btn--stacked cart-btn<?= $currentScript === 'cart.php' ? ' is-active' : '' ?>"
               href="<?= url('cart.php') ?>" aria-label="سلة التسوق">
                <?= icon('cart') ?>
                <span class="cart-count<?= $cartCount > 0 ? '' : ' is-empty' ?>"><?= $cartCount > 99 ? '99+' : $cartCount ?></span>
                <span class="icon-btn-label">السلة</span>
            </a>
        </div>
    </div>

    <nav class="cat-nav" aria-label="أقسام المتجر">
        <div class="container cat-nav-inner">
            <a class="cat-link<?= $currentScript === 'index.php' && $activeCategory === '' && $searchValue === '' ? ' is-active' : '' ?>"
               href="<?= url('index.php') ?>"><?= icon('sparkle') ?> كل المنتجات</a>
            <?php foreach ($navCategories as $cat): ?>
                <a class="cat-link<?= $activeCategory === $cat['slug'] ? ' is-active' : '' ?>"
                   href="<?= url('index.php?category=' . urlencode($cat['slug'])) ?>">
                    <span class="cat-emoji" aria-hidden="true"><?= e($cat['icon']) ?></span><?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
            <a class="cat-link cat-link--sale" href="<?= url('index.php?sort=discount') ?>">
                <?= icon('tag') ?> عروض
            </a>
        </div>
    </nav>
</header>

<main id="main">
    <?php if ($flashes): ?>
        <div class="container flash-wrap">
            <?php foreach ($flashes as $flash): ?>
                <div class="flash flash-<?= e($flash['type']) ?>" role="status">
                    <span class="flash-icon">
                        <?= $flash['type'] === 'success' ? icon('check') : ($flash['type'] === 'error' ? icon('close') : icon('clock')) ?>
                    </span>
                    <span class="flash-text"><?= e($flash['message']) ?></span>
                    <button type="button" class="flash-close" aria-label="إغلاق"><?= icon('close') ?></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
