<?php
require_once __DIR__ . '/config/config.php';
require_login('orders.php');

$me = current_user();

$stmt = $pdo->prepare(
    'SELECT o.*, COALESCE(SUM(oi.quantity), 0) AS items_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC, o.id DESC'
);
$stmt->execute([(int) $me['id']]);
$orders = $stmt->fetchAll();

$statsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS orders_count,
            COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END), 0) AS spent
     FROM orders WHERE user_id = ?"
);
$statsStmt->execute([(int) $me['id']]);
$stats = $statsStmt->fetch();

$previews = [];
if ($orders) {
    $orderIds = array_map(static fn($order) => (int) $order['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemStmt = $pdo->prepare(
        "SELECT oi.order_id, oi.product_name, p.image
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id IN ($placeholders)
         ORDER BY oi.id ASC"
    );
    $itemStmt->execute($orderIds);
    foreach ($itemStmt->fetchAll() as $row) {
        $previews[(int) $row['order_id']][] = $row;
    }
}

$pageTitle = 'طلباتي';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-heading">
        <div>
            <span class="eyebrow"><?= icon('user') ?> حسابي</span>
            <h1>طلباتي</h1>
            <p class="page-sub">تابع حالة طلباتك من هنا.</p>
        </div>
        <div class="account-hero-stats">
            <div><strong><?= (int) $stats['orders_count'] ?></strong><span>طلب</span></div>
            <div><strong><?= money($stats['spent']) ?></strong><span>إجمالي المشتريات</span></div>
        </div>
    </div>

    <?php require __DIR__ . '/includes/account-nav.php'; ?>

    <?php if (!$orders): ?>
        <div class="empty-state empty-state--page">
            <span class="empty-icon"><?= icon('box') ?></span>
            <h2>لا توجد طلبات بعد</h2>
            <p>عند إتمام أول طلب، ستجده هنا مع تفاصيله وحالة الشحن.</p>
            <a class="btn btn-dark btn-lg" href="<?= url('index.php') ?>">ابدأ التسوق <?= icon('left') ?></a>
        </div>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): ?>
                <?php $orderPreview = $previews[(int) $order['id']] ?? []; ?>
                <article class="order-card">
                    <div class="order-card-head">
                        <div class="order-cell">
                            <span>رقم الطلب</span>
                            <strong dir="ltr"><?= e($order['order_number']) ?></strong>
                        </div>
                        <div class="order-cell">
                            <span>التاريخ</span>
                            <strong><?= e(format_date($order['created_at'], 'd/m/Y')) ?></strong>
                        </div>
                        <div class="order-cell">
                            <span>عدد القطع</span>
                            <strong><?= (int) $order['items_count'] ?></strong>
                        </div>
                        <div class="order-cell">
                            <span>الإجمالي</span>
                            <strong><?= money($order['total']) ?></strong>
                        </div>
                        <span class="status-badge <?= e(order_status_class($order['status'])) ?>">
                            <?= e(order_status_label($order['status'])) ?>
                        </span>
                    </div>

                    <div class="order-card-body">
                        <div class="order-preview">
                            <?php foreach (array_slice($orderPreview, 0, 4) as $preview): ?>
                                <img src="<?= e(media_url($preview['image'] ?? '')) ?>" alt="<?= e($preview['product_name']) ?>" loading="lazy">
                            <?php endforeach; ?>
                            <?php if (count($orderPreview) > 4): ?>
                                <span class="order-preview-more">+<?= count($orderPreview) - 4 ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="order-card-actions">
                            <span class="order-payment"><?= $order['payment_method'] === 'transfer' ? 'تحويل بنكي' : 'دفع عند الاستلام' ?></span>
                            <a class="btn btn-outline btn-sm" href="<?= url('order.php?id=' . (int) $order['id']) ?>">
                                تفاصيل الطلب <?= icon('left') ?>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
