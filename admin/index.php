<?php
require_once dirname(__DIR__) . '/config/config.php';
require_admin();

$stats = [
    'revenue' => (float) $pdo->query(
        "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'cancelled'"
    )->fetchColumn(),
    'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'products' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'customers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
];

$recentOrders = $pdo->query(
    'SELECT o.*, u.name AS user_name
     FROM orders o LEFT JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC, o.id DESC LIMIT 6'
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.stock <= 5
     ORDER BY p.stock ASC, p.id ASC LIMIT 5'
)->fetchAll();

$topProducts = $pdo->query(
    "SELECT oi.product_name, SUM(oi.quantity) AS sold, SUM(oi.line_total) AS sales
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status <> 'cancelled'
     GROUP BY oi.product_name
     ORDER BY sold DESC LIMIT 5"
)->fetchAll();

$maxSold = 0;
foreach ($topProducts as $topProduct) {
    $maxSold = max($maxSold, (int) $topProduct['sold']);
}

$adminTitle = 'لوحة التحكم';
$adminSubtitle = 'نظرة سريعة على أداء متجرك اليوم';
$adminActive = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="stats-grid">
    <article class="stat-card">
        <span class="stat-icon stat-icon--rose"><?= icon('chart') ?></span>
        <div>
            <span class="stat-label">إجمالي المبيعات</span>
            <strong class="stat-value"><?= money($stats['revenue']) ?></strong>
            <span class="stat-hint">بدون الطلبات الملغاة</span>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon stat-icon--blue"><?= icon('box') ?></span>
        <div>
            <span class="stat-label">الطلبات</span>
            <strong class="stat-value"><?= $stats['orders'] ?></strong>
            <span class="stat-hint">إجمالي الطلبات المسجلة</span>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon stat-icon--amber"><?= icon('clock') ?></span>
        <div>
            <span class="stat-label">بانتظار المراجعة</span>
            <strong class="stat-value"><?= $stats['pending'] ?></strong>
            <span class="stat-hint">طلبات تحتاج تأكيدك</span>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon stat-icon--green"><?= icon('tag') ?></span>
        <div>
            <span class="stat-label">المنتجات النشطة</span>
            <strong class="stat-value"><?= $stats['products'] ?></strong>
            <span class="stat-hint">معروضة في المتجر</span>
        </div>
    </article>

    <article class="stat-card">
        <span class="stat-icon stat-icon--violet"><?= icon('users') ?></span>
        <div>
            <span class="stat-label">العملاء</span>
            <strong class="stat-value"><?= $stats['customers'] ?></strong>
            <span class="stat-hint">حسابات مسجّلة</span>
        </div>
    </article>
</section>

<div class="admin-grid">
    <section class="admin-card admin-card--wide">
        <div class="admin-card-head">
            <div>
                <h2>أحدث الطلبات</h2>
                <p>آخر الطلبات الواردة إلى المتجر</p>
            </div>
            <a class="link-more" href="<?= url('admin/orders.php') ?>">كل الطلبات <?= icon('left') ?></a>
        </div>

        <?php if (!$recentOrders): ?>
            <div class="admin-empty">لا توجد طلبات بعد.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>العميل</th>
                            <th>التاريخ</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong dir="ltr"><?= e($order['order_number']) ?></strong></td>
                                <td>
                                    <?= e($order['customer_name']) ?>
                                    <small class="cell-sub"><?= e($order['city']) ?></small>
                                </td>
                                <td><?= e(format_date($order['created_at'], 'd/m/Y')) ?></td>
                                <td><?= money($order['total']) ?></td>
                                <td><span class="status-badge <?= e(order_status_class($order['status'])) ?>"><?= e(order_status_label($order['status'])) ?></span></td>
                                <td><a class="table-link" href="<?= url('admin/order.php?id=' . (int) $order['id']) ?>">عرض</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <div>
                <h2>مخزون منخفض</h2>
                <p>منتجات تحتاج إعادة تعبئة</p>
            </div>
        </div>

        <?php if (!$lowStock): ?>
            <div class="admin-empty">كل المنتجات بمخزون جيد 👌</div>
        <?php else: ?>
            <ul class="admin-list">
                <?php foreach ($lowStock as $item): ?>
                    <li>
                        <img src="<?= e(media_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" loading="lazy">
                        <div class="admin-list-info">
                            <a href="<?= url('admin/product-form.php?id=' . (int) $item['id']) ?>"><?= e($item['name']) ?></a>
                            <span><?= e($item['category_name']) ?></span>
                        </div>
                        <span class="stock-badge <?= (int) $item['stock'] === 0 ? 'stock-badge--out' : 'stock-badge--low' ?>">
                            <?= (int) $item['stock'] ?> قطعة
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-card-head">
            <div>
                <h2>الأكثر مبيعاً</h2>
                <p>حسب عدد القطع المبيعة</p>
            </div>
        </div>

        <?php if (!$topProducts): ?>
            <div class="admin-empty">لا توجد مبيعات بعد.</div>
        <?php else: ?>
            <ul class="top-products">
                <?php foreach ($topProducts as $index => $topProduct): ?>
                    <li>
                        <span class="top-rank"><?= $index + 1 ?></span>
                        <div class="top-info">
                            <strong><?= e($topProduct['product_name']) ?></strong>
                            <div class="progress progress--slim">
                                <span style="width: <?= $maxSold > 0 ? max(8, (int) round(((int) $topProduct['sold'] / $maxSold) * 100)) : 0 ?>%"></span>
                            </div>
                        </div>
                        <span class="top-sold"><?= (int) $topProduct['sold'] ?> قطعة</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="admin-card admin-card--soft">
        <div class="admin-card-head">
            <div>
                <h2>إجراءات سريعة</h2>
                <p>اختصر وقتك</p>
            </div>
        </div>
        <div class="quick-actions">
            <a class="quick-action" href="<?= url('admin/product-form.php') ?>">
                <span><?= icon('plus') ?></span>
                <strong>إضافة منتج</strong>
                <small>منتج جديد للمتجر</small>
            </a>
            <a class="quick-action" href="<?= url('admin/orders.php?status=pending') ?>">
                <span><?= icon('clock') ?></span>
                <strong>مراجعة الطلبات</strong>
                <small><?= $stats['pending'] ?> طلب بانتظارك</small>
            </a>
            <a class="quick-action" href="<?= url('admin/products.php') ?>">
                <span><?= icon('box') ?></span>
                <strong>إدارة المخزون</strong>
                <small><?= count($lowStock) ?> منتج بمخزون منخفض</small>
            </a>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
