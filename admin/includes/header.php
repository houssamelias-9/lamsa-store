<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_admin();

$adminUser = current_user();
$adminFlashes = take_flashes();
$adminScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$adminTitle = $adminTitle ?? 'لوحة الإدارة';
$adminSubtitle = $adminSubtitle ?? '';
$adminActive = $adminActive ?? '';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?> · إدارة <?= e(SITE_NAME) ?></title>
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?= url('admin/index.php') ?>">
            <span class="brand-mark">ل</span>
            <span>
                <strong><?= e(SITE_NAME) ?></strong>
                <small>لوحة الإدارة</small>
            </span>
        </a>

        <nav class="admin-nav" aria-label="قائمة الإدارة">
            <span class="admin-nav-label">نظرة عامة</span>
            <a class="<?= $adminActive === 'dashboard' ? 'is-active' : '' ?>" href="<?= url('admin/index.php') ?>">
                <?= icon('dashboard') ?> لوحة التحكم
            </a>

            <span class="admin-nav-label">الكتالوج</span>
            <a class="<?= $adminActive === 'products' ? 'is-active' : '' ?>" href="<?= url('admin/products.php') ?>">
                <?= icon('box') ?> المنتجات
            </a>
            <a class="<?= $adminActive === 'product-new' ? 'is-active' : '' ?>" href="<?= url('admin/product-form.php') ?>">
                <?= icon('plus') ?> إضافة منتج
            </a>

            <span class="admin-nav-label">المبيعات</span>
            <a class="<?= $adminActive === 'orders' ? 'is-active' : '' ?>" href="<?= url('admin/orders.php') ?>">
                <?= icon('list') ?> الطلبات
            </a>

            <span class="admin-nav-label">المتجر</span>
            <a href="<?= url('index.php') ?>" target="_blank" rel="noopener">
                <?= icon('eye') ?> عرض المتجر
            </a>
        </nav>

        <div class="admin-sidebar-foot">
            <div class="admin-user">
                <span class="admin-avatar"><?= e(mb_substr($adminUser['name'], 0, 1, 'UTF-8')) ?></span>
                <div>
                    <strong><?= e($adminUser['name']) ?></strong>
                    <small dir="ltr"><?= e($adminUser['email']) ?></small>
                </div>
            </div>
            <form method="post" action="<?= url('logout.php') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="admin-logout"><?= icon('logout') ?> تسجيل الخروج</button>
            </form>
        </div>
    </aside>

    <div class="admin-backdrop" data-admin-close></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-menu-btn" data-admin-toggle aria-label="فتح القائمة"><?= icon('menu') ?></button>
            <div class="admin-heading">
                <h1><?= e($adminTitle) ?></h1>
                <?php if ($adminSubtitle !== ''): ?><p><?= e($adminSubtitle) ?></p><?php endif; ?>
            </div>
            <div class="admin-top-actions">
                <a class="btn btn-outline btn-sm" href="<?= url('index.php') ?>" target="_blank" rel="noopener">
                    <?= icon('eye') ?> <span class="hide-sm">عرض المتجر</span>
                </a>
                <span class="admin-top-avatar" title="<?= e($adminUser['name']) ?>">
                    <?= e(mb_substr($adminUser['name'], 0, 1, 'UTF-8')) ?>
                </span>
            </div>
        </header>

        <div class="admin-content">
            <?php if ($adminFlashes): ?>
                <div class="flash-wrap flash-wrap--admin">
                    <?php foreach ($adminFlashes as $flash): ?>
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
