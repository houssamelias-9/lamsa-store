<?php
require_once __DIR__ . '/config/config.php';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = $productId > 0 ? get_product($productId) : null;

if (!$product || ((int) $product['is_active'] !== 1 && !is_admin())) {
    http_response_code(404);
    $pageTitle = 'المنتج غير موجود';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container">
        <div class="empty-state empty-state--page">
            <span class="empty-icon"><?= icon('box') ?></span>
            <h1>لم نجد هذا المنتج</h1>
            <p>ربما تم حذفه أو تغيّر رابطه. تصفّح باقي المنتجات وستجد ما يعجبك.</p>
            <a class="btn btn-dark" href="<?= url('index.php') ?>">العودة للتسوق</a>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$discount = discount_percent($product['price'], $product['old_price']);
$stock = (int) $product['stock'];

$relatedStmt = $pdo->prepare(
    'SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id <> ? AND p.is_active = 1
     ORDER BY p.is_featured DESC, p.created_at DESC LIMIT 4'
);
$relatedStmt->execute([(int) $product['category_id'], (int) $product['id']]);
$relatedProducts = $relatedStmt->fetchAll();

$activeCategory = $product['category_slug'] ?? '';
$pageTitle = $product['name'];
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb" aria-label="مسار التنقل">
        <a href="<?= url('index.php') ?>">الرئيسية</a>
        <?= icon('left') ?>
        <a href="<?= url('index.php?category=' . urlencode($product['category_slug'] ?? '')) ?>"><?= e($product['category_name'] ?? 'المنتجات') ?></a>
        <?= icon('left') ?>
        <span><?= e($product['name']) ?></span>
    </nav>

    <div class="product-detail">
        <div class="gallery">
            <div class="gallery-main">
                <?php if ($discount > 0): ?>
                    <span class="badge badge-sale gallery-badge">خصم <?= $discount ?>%</span>
                <?php endif; ?>
                <button type="button" class="zoomable" data-full="<?= e(media_url($product['image'])) ?>" aria-label="تكبير الصورة">
                    <img src="<?= e(media_url($product['image'])) ?>" alt="<?= e($product['name']) ?>" width="600" height="750">
                </button>
                <span class="gallery-hint"><?= icon('search') ?> اضغط لتكبير الصورة</span>
            </div>
        </div>

        <div class="detail-info">
            <a class="product-cat" href="<?= url('index.php?category=' . urlencode($product['category_slug'] ?? '')) ?>">
                <?= e($product['category_name'] ?? '') ?>
            </a>
            <h1 class="detail-title"><?= e($product['name']) ?></h1>

            <div class="detail-rating">
                <?= icon('sparkle') ?>
                <span>قطعة مختارة بعناية من تشكيلة <?= e(SITE_NAME) ?></span>
            </div>

            <div class="detail-price">
                <strong><?= money($product['price']) ?></strong>
                <?php if ($discount > 0): ?>
                    <del><?= money($product['old_price']) ?></del>
                    <span class="save-tag">توفير <?= money((float) $product['old_price'] - (float) $product['price']) ?></span>
                <?php endif; ?>
            </div>
            <p class="tax-note">السعر شامل الضرائب — يُحسب الشحن عند إتمام الطلب.</p>

            <p class="detail-desc"><?= nl2br(e($product['description'])) ?></p>

            <div class="stock-line <?= $stock > 0 ? ($stock <= 5 ? 'is-low' : 'is-in') : 'is-out' ?>">
                <span class="stock-dot"></span>
                <?php if ($stock > 5): ?>
                    متوفر في المخزون — جاهز للشحن
                <?php elseif ($stock > 0): ?>
                    كمية محدودة — تبقّى <?= $stock ?> قطع فقط
                <?php else: ?>
                    غير متوفر حالياً
                <?php endif; ?>
            </div>

            <?php if ($stock > 0): ?>
                <form method="post" action="<?= url('cart.php') ?>" class="buy-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="back" value="product.php?id=<?= (int) $product['id'] ?>">

                    <div class="buy-row">
                        <div class="qty-stepper" data-max="<?= $stock ?>">
                            <button type="button" class="qty-btn" data-qty="-1" aria-label="تقليل الكمية"><?= icon('minus') ?></button>
                            <label class="sr-only" for="qty">الكمية</label>
                            <input id="qty" type="number" name="qty" value="1" min="1" max="<?= $stock ?>" inputmode="numeric">
                            <button type="button" class="qty-btn" data-qty="1" aria-label="زيادة الكمية"><?= icon('plus') ?></button>
                        </div>
                        <button type="submit" name="go" value="add" class="btn btn-dark btn-lg buy-btn"><?= icon('cart') ?> أضف للسلة</button>
                    </div>
                    <button type="submit" name="go" value="checkout" class="btn btn-accent btn-lg btn-block">اشترِ الآن</button>
                </form>
            <?php else: ?>
                <button type="button" class="btn btn-dark btn-lg btn-block" disabled>غير متوفر حالياً</button>
                <p class="tax-note">اترك لنا بريدك عبر صفحة حسابك وسنخبرك عند توفره.</p>
            <?php endif; ?>

            <ul class="detail-perks">
                <li><?= icon('truck') ?><div><strong>توصيل سريع</strong><span>1–3 أيام عمل لكل المحافظات</span></div></li>
                <li><?= icon('refresh') ?><div><strong>إرجاع مجاني</strong><span>خلال 14 يوماً من الاستلام</span></div></li>
                <li><?= icon('shield') ?><div><strong>دفع آمن</strong><span>عند الاستلام أو بتحويل بنكي</span></div></li>
            </ul>

            <div class="accordion">
                <details open>
                    <summary>تفاصيل المنتج</summary>
                    <div class="accordion-body">
                        <ul class="detail-list">
                            <li><span>القسم</span><strong><?= e($product['category_name'] ?? '') ?></strong></li>
                            <li><span>رمز المنتج</span><strong dir="ltr">LM-<?= str_pad((string) $product['id'], 5, '0', STR_PAD_LEFT) ?></strong></li>
                            <li><span>التوفر</span><strong><?= $stock > 0 ? 'متوفر' : 'غير متوفر' ?></strong></li>
                            <li><span>الضمان</span><strong>فحص الجودة قبل الشحن</strong></li>
                        </ul>
                    </div>
                </details>
                <details>
                    <summary>الشحن والإرجاع</summary>
                    <div class="accordion-body">
                        <p>نوصل لجميع المحافظات اللبنانية خلال 1–3 أيام عمل. الشحن مجاني للطلبات فوق
                            <?= number_format(FREE_SHIPPING_FROM, 0) ?> <?= e(CURRENCY) ?>، وإلا تُضاف رسوم
                            <?= money(SHIPPING_FEE) ?> فقط.</p>
                        <p>يمكنك إرجاع المنتج خلال 14 يوماً بشرط أن يكون بحالته الأصلية وبكامل تغليفه.</p>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <?php if ($relatedProducts): ?>
        <section class="section">
            <div class="section-head">
                <div>
                    <span class="eyebrow">قد يعجبك أيضاً</span>
                    <h2>منتجات مشابهة</h2>
                </div>
                <a class="link-more" href="<?= url('index.php?category=' . urlencode($product['category_slug'] ?? '')) ?>">
                    كل قسم <?= e($product['category_name'] ?? '') ?> <?= icon('left') ?>
                </a>
            </div>
            <div class="product-grid product-grid--related">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <?php $product = $relatedProduct; ?>
                    <?php require __DIR__ . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
