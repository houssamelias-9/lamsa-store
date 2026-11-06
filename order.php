<?php
require_once __DIR__ . '/config/config.php';
require_login('orders.php');

$me = current_user();
$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

$isOwner = $order && (int) $order['user_id'] === (int) $me['id'];

if (!$order || (!$isOwner && !is_admin())) {
    http_response_code(404);
    $pageTitle = 'الطلب غير موجود';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container">
        <div class="empty-state empty-state--page">
            <span class="empty-icon"><?= icon('box') ?></span>
            <h1>لم نجد هذا الطلب</h1>
            <p>ربما تم حذف الطلب أو أن الرابط غير صحيح.</p>
            <a class="btn btn-dark" href="<?= url('orders.php') ?>">العودة لطلباتي</a>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$itemsStmt = $pdo->prepare(
    'SELECT oi.*, p.image, p.is_active, p.stock
     FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC'
);
$itemsStmt->execute([(int) $order['id']]);
$orderItems = $itemsStmt->fetchAll();

/* إعادة الطلب */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reorder') {
    verify_csrf();

    $added = 0;
    foreach ($orderItems as $item) {
        if (!$item['product_id'] || (int) $item['is_active'] !== 1 || (int) $item['stock'] < 1) {
            continue;
        }
        if (cart_add((int) $item['product_id'], (int) $item['quantity'])) {
            $added++;
        }
    }

    if ($added > 0) {
        flash('success', 'تمت إضافة ' . $added . ' منتج إلى سلة التسوق من هذا الطلب.');
        redirect('cart.php');
    }

    flash('error', 'عذراً، منتجات هذا الطلب غير متوفرة حالياً.');
    redirect('order.php?id=' . (int) $order['id']);
}

$statusSteps = [
    'pending'    => ['label' => 'تم استلام الطلب', 'desc' => 'بانتظار تأكيد المتجر'],
    'processing' => ['label' => 'قيد التجهيز', 'desc' => 'نحضّر منتجاتك بعناية'],
    'shipped'    => ['label' => 'تم الشحن', 'desc' => 'طلبك في الطريق إليك'],
    'delivered'  => ['label' => 'تم التسليم', 'desc' => 'نتمنى أن يعجبك طلبك'],
];
$stepKeys = array_keys($statusSteps);
$currentIndex = array_search($order['status'], $stepKeys, true);
$isCancelled = $order['status'] === 'cancelled';
$paymentLabel = $order['payment_method'] === 'transfer' ? 'تحويل بنكي' : 'الدفع عند الاستلام';
$showSuccess = isset($_GET['success']) && $_GET['success'] === '1';

$pageTitle = 'تفاصيل الطلب ' . $order['order_number'];
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb" aria-label="مسار التنقل">
        <a href="<?= url('index.php') ?>">الرئيسية</a>
        <?= icon('left') ?>
        <a href="<?= url('orders.php') ?>">طلباتي</a>
        <?= icon('left') ?>
        <span dir="ltr"><?= e($order['order_number']) ?></span>
    </nav>

    <?php if ($showSuccess): ?>
        <div class="success-banner">
            <span class="success-icon"><?= icon('check') ?></span>
            <div>
                <h2>شكراً لك، <?= e($order['customer_name']) ?>! تم استلام طلبك.</h2>
                <p>رقم طلبك <strong dir="ltr"><?= e($order['order_number']) ?></strong>. سنتواصل معك على
                    <span dir="ltr"><?= e($order['phone']) ?></span> لتأكيد التوصيل.</p>
            </div>
            <a class="btn btn-outline" href="<?= url('index.php') ?>">متابعة التسوق</a>
        </div>
    <?php endif; ?>

    <div class="page-heading">
        <div>
            <span class="eyebrow"><?= icon('box') ?> تفاصيل الطلب</span>
            <h1 dir="ltr" class="order-number-title"><?= e($order['order_number']) ?></h1>
            <p class="page-sub">تم الطلب في <?= e(format_date($order['created_at'], 'd/m/Y H:i')) ?></p>
        </div>
        <div class="heading-actions">
            <span class="status-badge status-badge--lg <?= e(order_status_class($order['status'])) ?>">
                <?= e(order_status_label($order['status'])) ?>
            </span>
            <?php if (is_admin()): ?>
                <a class="btn btn-ghost btn-sm" href="<?= url('admin/order.php?id=' . (int) $order['id']) ?>">
                    <?= icon('edit') ?> إدارة الطلب
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isCancelled): ?>
        <div class="notice notice--error">
            <?= icon('close') ?>
            <div>
                <strong>تم إلغاء هذا الطلب</strong>
                <p>إذا كان لديك استفسار، تواصل معنا على <span dir="ltr">+961 70 000 000</span>.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="timeline">
            <?php foreach ($statusSteps as $key => $step): ?>
                <?php
                $stepIndex = array_search($key, $stepKeys, true);
                $stateClass = '';
                if ($currentIndex !== false && $stepIndex < $currentIndex) {
                    $stateClass = 'is-done';
                } elseif ($currentIndex !== false && $stepIndex === $currentIndex) {
                    $stateClass = 'is-current';
                }
                ?>
                <div class="timeline-step <?= $stateClass ?>">
                    <span class="timeline-dot"><?= $stepIndex < $currentIndex ? icon('check') : '' ?></span>
                    <div>
                        <strong><?= e($step['label']) ?></strong>
                        <span><?= e($step['desc']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="order-layout">
        <div class="order-main">
            <section class="panel">
                <div class="panel-head panel-head--row">
                    <div>
                        <h2>منتجات الطلب</h2>
                        <p><?= count($orderItems) ?> منتج</p>
                    </div>
                    <span class="result-count"><?= (int) array_sum(array_column($orderItems, 'quantity')) ?> قطعة</span>
                </div>

                <div class="order-items">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item">
                            <?php if ($item['product_id']): ?>
                                <a class="order-item-thumb" href="<?= url('product.php?id=' . (int) $item['product_id']) ?>">
                                    <img src="<?= e(media_url($item['image'] ?? '')) ?>" alt="<?= e($item['product_name']) ?>" loading="lazy">
                                </a>
                            <?php else: ?>
                                <span class="order-item-thumb">
                                    <img src="<?= e(media_url(null)) ?>" alt="<?= e($item['product_name']) ?>" loading="lazy">
                                </span>
                            <?php endif; ?>

                            <div class="order-item-info">
                                <?php if ($item['product_id']): ?>
                                    <a href="<?= url('product.php?id=' . (int) $item['product_id']) ?>"><?= e($item['product_name']) ?></a>
                                <?php else: ?>
                                    <strong><?= e($item['product_name']) ?></strong>
                                <?php endif; ?>
                                <span><?= money($item['unit_price']) ?> × <?= (int) $item['quantity'] ?></span>
                            </div>

                            <div class="order-item-total"><?= money($item['line_total']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <dl class="summary-list summary-list--panel">
                    <div><dt>المجموع الفرعي</dt><dd><?= money($order['subtotal']) ?></dd></div>
                    <div>
                        <dt>الشحن</dt>
                        <dd><?= (float) $order['shipping'] > 0 ? money($order['shipping']) : '<span class="free-tag">مجاني</span>' ?></dd>
                    </div>
                    <div class="summary-total"><dt>الإجمالي</dt><dd><?= money($order['total']) ?></dd></div>
                </dl>
            </section>
        </div>

        <aside class="order-side">
            <section class="panel">
                <h2 class="panel-title">ملخص الطلب</h2>
                <ul class="info-list">
                    <li><span>رقم الطلب</span><strong dir="ltr"><?= e($order['order_number']) ?></strong></li>
                    <li><span>التاريخ</span><strong><?= e(format_date($order['created_at'], 'd/m/Y')) ?></strong></li>
                    <li><span>طريقة الدفع</span><strong><?= e($paymentLabel) ?></strong></li>
                    <li><span>عدد القطع</span><strong><?= (int) array_sum(array_column($orderItems, 'quantity')) ?></strong></li>
                </ul>

                <form method="post" action="<?= url('order.php?id=' . (int) $order['id']) ?>" class="reorder-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reorder">
                    <button type="submit" class="btn btn-dark btn-block"><?= icon('refresh') ?> إعادة الطلب</button>
                </form>
            </section>

            <section class="panel">
                <h2 class="panel-title"><?= icon('pin') ?> عنوان التوصيل</h2>
                <div class="address-block">
                    <strong><?= e($order['customer_name']) ?></strong>
                    <span><?= e($order['address']) ?></span>
                    <span><?= e($order['city']) ?></span>
                    <span dir="ltr"><?= e($order['phone']) ?></span>
                </div>
                <?php if (!empty($order['notes'])): ?>
                    <div class="order-notes">
                        <span>ملاحظات:</span>
                        <p><?= nl2br(e($order['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel panel--soft">
                <h2 class="panel-title"><?= icon('phone') ?> تحتاج مساعدة؟</h2>
                <p class="panel-text">فريقنا جاهز لمساعدتك بخصوص طلبك.</p>
                <a class="contact-line" href="tel:+96170000000" dir="ltr">+961 70 000 000</a>
                <a class="contact-line" href="mailto:hello@lamsa.com" dir="ltr">hello@lamsa.com</a>
            </section>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
