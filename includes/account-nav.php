<?php
$accountScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="account-nav" aria-label="حسابي">
    <a class="<?= $accountScript === 'profile.php' ? 'is-active' : '' ?>" href="<?= url('profile.php') ?>">
        <?= icon('user') ?> بيانات حسابي
    </a>
    <a class="<?= $accountScript === 'orders.php' || $accountScript === 'order.php' ? 'is-active' : '' ?>" href="<?= url('orders.php') ?>">
        <?= icon('box') ?> طلباتي
    </a>
    <a class="<?= $accountScript === 'cart.php' ? 'is-active' : '' ?>" href="<?= url('cart.php') ?>">
        <?= icon('cart') ?> سلة التسوق
        <?php if (cart_count() > 0): ?><span class="nav-badge"><?= cart_count() ?></span><?php endif; ?>
    </a>
</nav>
