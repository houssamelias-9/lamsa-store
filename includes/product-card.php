<?php
/** @var array $product */
$discount = discount_percent($product['price'], $product['old_price']);
$stock = (int) $product['stock'];
$backLink = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
if (!empty($_SERVER['QUERY_STRING'])) {
    $backLink .= '?' . $_SERVER['QUERY_STRING'];
}
?>
<article class="product-card">
    <div class="product-media">
        <a href="<?= url('product.php?id=' . (int) $product['id']) ?>" aria-label="<?= e($product['name']) ?>">
            <img src="<?= e(media_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy" width="600" height="750">
        </a>

        <div class="product-badges">
            <?php if ($discount > 0): ?>
                <span class="badge badge-sale">خصم <?= $discount ?>%</span>
            <?php endif; ?>
            <?php if ($stock < 1): ?>
                <span class="badge badge-out">نفدت الكمية</span>
            <?php elseif ($stock <= 3): ?>
                <span class="badge badge-low">آخر <?= $stock ?> قطع</span>
            <?php endif; ?>
        </div>

        <?php if ($stock > 0): ?>
            <form method="post" action="<?= url('cart.php') ?>" class="quick-add-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="qty" value="1">
                <input type="hidden" name="back" value="<?= e($backLink) ?>">
                <button type="submit" class="quick-add"><?= icon('cart') ?> أضف للسلة</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="product-body">
        <span class="product-cat"><?= e($product['category_name'] ?? '') ?></span>
        <h3 class="product-title">
            <a href="<?= url('product.php?id=' . (int) $product['id']) ?>"><?= e($product['name']) ?></a>
        </h3>
        <div class="product-price">
            <strong><?= money($product['price']) ?></strong>
            <?php if ($discount > 0): ?>
                <del><?= money($product['old_price']) ?></del>
            <?php endif; ?>
        </div>
    </div>
</article>
