<?php
require_once __DIR__ . '/config/config.php';

/* ---------- معالجة عمليات السلة ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    $productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    $back = isset($_POST['back']) && is_string($_POST['back']) ? safe_next($_POST['back']) : 'cart.php';

    if ($action === 'add' && $productId > 0) {
        $product = get_product($productId);
        $requested = max(1, (int) ($_POST['qty'] ?? 1));

        if (!$product || (int) $product['is_active'] !== 1 || (int) $product['stock'] < 1) {
            flash('error', 'عذراً، هذا المنتج غير متوفر حالياً.');
            redirect($back);
        }

        $currentQty = (int) (cart()[$productId] ?? 0);
        $available = (int) $product['stock'];

        if ($currentQty >= $available) {
            flash('info', 'لديك بالفعل أقصى كمية متوفرة من هذا المنتج في السلة.');
        } elseif (cart_add($productId, $requested)) {
            $newQty = (int) (cart()[$productId] ?? 0);
            if ($currentQty + $requested > $available) {
                flash('info', 'تمت إضافة الكمية المتوفرة فقط (' . $newQty . ' قطع) من «' . $product['name'] . '».');
            } else {
                flash('success', 'تمت إضافة «' . $product['name'] . '» إلى السلة.');
            }
        } else {
            flash('error', 'تعذّرت إضافة المنتج إلى السلة.');
        }

        $go = isset($_POST['go']) && $_POST['go'] === 'checkout' ? 'checkout.php' : $back;
        redirect($go);
    }

    if ($action === 'update') {
        $quantities = isset($_POST['quantities']) && is_array($_POST['quantities']) ? $_POST['quantities'] : [];
        $clamped = false;

        foreach (cart_items() as $item) {
            $id = (int) $item['product']['id'];
            if (!array_key_exists($id, $quantities)) {
                continue;
            }
            $qty = max(0, (int) $quantities[$id]);
            $available = (int) $item['product']['stock'];

            if ($qty > $available) {
                $qty = $available;
                $clamped = true;
            }
            cart_set_quantity($id, $qty);
        }

        if ($clamped) {
            flash('info', 'تم تعديل بعض الكميات حسب المتوفر في المخزون.');
        } else {
            flash('success', 'تم تحديث سلة التسوق.');
        }
        redirect('cart.php');
    }

    if ($action === 'remove' || isset($_POST['remove_me'])) {
        cart_remove($productId);
        flash('success', 'تم حذف المنتج من السلة.');
        redirect('cart.php');
    }

    if ($action === 'clear') {
        cart_clear();
        flash('info', 'تم تفريغ سلة التسوق.');
        redirect('cart.php');
    }

    redirect('cart.php');
}

/* ---------- عرض السلة ---------- */
$items = cart_items();
$subtotal = 0.0;
foreach ($items as $item) {
    $subtotal += (float) $item['line_total'];
}
$subtotal = round($subtotal, 2);
$shipping = shipping_cost($subtotal);
$total = round($subtotal + $shipping, 2);
$remainingForFree = max(0, (float) FREE_SHIPPING_FROM - $subtotal);
$progress = FREE_SHIPPING_FROM > 0 ? min(100, (int) round(($subtotal / FREE_SHIPPING_FROM) * 100)) : 100;

$pageTitle = 'سلة التسوق';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb" aria-label="مسار التنقل">
        <a href="<?= url('index.php') ?>">الرئيسية</a>
        <?= icon('left') ?>
        <span>سلة التسوق</span>
    </nav>

    <div class="page-heading">
        <div>
            <span class="eyebrow"><?= icon('cart') ?> خطوة واحدة وتصل</span>
            <h1>سلة التسوق</h1>
        </div>
        <?php if ($items): ?>
            <span class="result-count"><?= cart_count() ?> قطعة</span>
        <?php endif; ?>
    </div>

    <?php if (!$items): ?>
        <div class="empty-state empty-state--page">
            <span class="empty-icon"><?= icon('cart') ?></span>
            <h2>سلتك فارغة</h2>
            <p>لم تُضف أي منتج بعد. تصفّح تشكيلتنا واختر ما يناسب إطلالتك.</p>
            <a class="btn btn-dark btn-lg" href="<?= url('index.php') ?>">ابدأ التسوق <?= icon('left') ?></a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-main">
                <?php if ($remainingForFree > 0): ?>
                    <div class="free-shipping">
                        <div class="free-shipping-text">
                            <?= icon('truck') ?>
                            <span>أضف <strong><?= money($remainingForFree) ?></strong> لتحصل على شحن مجاني!</span>
                        </div>
                        <div class="progress"><span style="width: <?= $progress ?>%"></span></div>
                    </div>
                <?php else: ?>
                    <div class="free-shipping free-shipping--done">
                        <?= icon('check') ?>
                        <span>مبروك! طلبك مؤهّل للشحن المجاني.</span>
                    </div>
                <?php endif; ?>

                <div class="cart-list">
                    <?php foreach ($items as $item): ?>
                        <?php $p = $item['product']; ?>
                        <form method="post" action="<?= url('cart.php') ?>" class="cart-row">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">

                            <a class="cart-thumb" href="<?= url('product.php?id=' . (int) $p['id']) ?>">
                                <img src="<?= e(media_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                            </a>

                            <div class="cart-info">
                                <span class="product-cat"><?= e($p['category_name'] ?? '') ?></span>
                                <a class="cart-name" href="<?= url('product.php?id=' . (int) $p['id']) ?>"><?= e($p['name']) ?></a>
                                <span class="cart-unit"><?= money($p['price']) ?> للقطعة</span>
                                <?php if ((int) $p['stock'] <= 5): ?>
                                    <span class="cart-low">متبقٍ <?= (int) $p['stock'] ?> قطع</span>
                                <?php endif; ?>
                                <button type="submit" name="remove_me" value="1" class="cart-remove" formnovalidate>
                                    <?= icon('trash') ?> إزالة
                                </button>
                            </div>

                            <div class="qty-stepper qty-stepper--cart" data-max="<?= (int) $p['stock'] ?>">
                                <button type="button" class="qty-btn" data-qty="-1" aria-label="تقليل الكمية"><?= icon('minus') ?></button>
                                <label class="sr-only" for="qty-<?= (int) $p['id'] ?>">الكمية</label>
                                <input id="qty-<?= (int) $p['id'] ?>" type="number" name="quantities[<?= (int) $p['id'] ?>]"
                                       value="<?= (int) $item['qty'] ?>" min="1" max="<?= (int) $p['stock'] ?>" inputmode="numeric">
                                <button type="button" class="qty-btn" data-qty="1" aria-label="زيادة الكمية"><?= icon('plus') ?></button>
                            </div>

                            <div class="cart-line-total">
                                <span>الإجمالي</span>
                                <strong><?= money($item['line_total']) ?></strong>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>

                <div class="cart-toolbar">
                    <a class="btn btn-outline" href="<?= url('index.php') ?>"><?= icon('right') ?> متابعة التسوق</a>
                    <form method="post" action="<?= url('cart.php') ?>" data-confirm="هل تريد تفريغ السلة بالكامل؟">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-ghost"><?= icon('trash') ?> تفريغ السلة</button>
                    </form>
                </div>
            </div>

            <aside class="cart-summary">
                <h2>ملخّص الطلب</h2>
                <dl class="summary-list">
                    <div><dt>المجموع الفرعي</dt><dd><?= money($subtotal) ?></dd></div>
                    <div>
                        <dt>الشحن</dt>
                        <dd><?= $shipping > 0 ? money($shipping) : '<span class="free-tag">مجاني</span>' ?></dd>
                    </div>
                    <div class="summary-total"><dt>الإجمالي</dt><dd><?= money($total) ?></dd></div>
                </dl>

                <a class="btn btn-accent btn-lg btn-block" href="<?= url('checkout.php') ?>">
                    إتمام الطلب <?= icon('left') ?>
                </a>
                <p class="summary-note"><?= icon('shield') ?> دفع آمن — الدفع عند الاستلام متاح.</p>

                <ul class="summary-perks">
                    <li><?= icon('truck') ?> توصيل خلال 1–3 أيام عمل</li>
                    <li><?= icon('refresh') ?> إرجاع مجاني خلال 14 يوماً</li>
                </ul>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
