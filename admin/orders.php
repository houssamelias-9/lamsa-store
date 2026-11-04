<?php
require_once dirname(__DIR__) . '/config/config.php';
require_admin();

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$allowedStatuses = array_keys(order_statuses());
if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$statusCounts = $pdo->query('SELECT status, COUNT(*) AS total FROM orders GROUP BY status')
    ->fetchAll(PDO::FETCH_KEY_PAIR);
$totalAll = array_sum(array_map('intval', $statusCounts));

$where = ['1 = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.phone LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '') {
    $where[] = 'o.status = ?';
    $params[] = $status;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT o.*, u.email AS user_email,
        (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE $whereSql
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$queryParams = [];
if ($q !== '') {
    $queryParams['q'] = $q;
}
if ($status !== '') {
    $queryParams['status'] = $status;
}

$chipUrl = static function (string $chipStatus) use ($q): string {
    $params = [];
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($chipStatus !== '') {
        $params['status'] = $chipStatus;
    }
    return url('admin/orders.php' . ($params ? '?' . http_build_query($params) : ''));
};

$adminTitle = 'الطلبات';
$adminSubtitle = 'متابعة الطلبات وتحديث حالات الشحن';
$adminActive = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-head">
    <span class="result-count"><?= $total ?> طلب</span>
    <form method="get" action="<?= url('admin/orders.php') ?>" class="filter-bar filter-bar--compact">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <div class="filter-search">
            <?= icon('search') ?>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="رقم الطلب، الاسم أو الهاتف...">
        </div>
        <button type="submit" class="btn btn-dark">بحث</button>
        <?php if ($q !== ''): ?>
            <a class="btn btn-ghost" href="<?= e($chipUrl($status)) ?>"><?= icon('close') ?> مسح</a>
        <?php endif; ?>
    </form>
</div>

<nav class="filter-chips" aria-label="تصفية حسب الحالة">
    <a class="filter-chip<?= $status === '' ? ' is-active' : '' ?>" href="<?= e($chipUrl('')) ?>">
        الكل <span><?= $totalAll ?></span>
    </a>
    <?php foreach (order_statuses() as $key => $label): ?>
        <a class="filter-chip<?= $status === $key ? ' is-active' : '' ?>" href="<?= e($chipUrl($key)) ?>">
            <?= e($label) ?> <span><?= (int) ($statusCounts[$key] ?? 0) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<section class="admin-card">
    <?php if (!$orders): ?>
        <div class="admin-empty">
            <p>لا توجد طلبات مطابقة لهذا البحث.</p>
            <a class="btn btn-outline btn-sm" href="<?= url('admin/orders.php') ?>">عرض كل الطلبات</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>التاريخ</th>
                        <th>القطع</th>
                        <th>الإجمالي</th>
                        <th>الدفع</th>
                        <th>الحالة</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a class="table-order-number" href="<?= url('admin/order.php?id=' . (int) $order['id']) ?>" dir="ltr">
                                    <?= e($order['order_number']) ?>
                                </a>
                            </td>
                            <td>
                                <?= e($order['customer_name']) ?>
                                <small class="cell-sub" dir="ltr"><?= e($order['phone']) ?></small>
                            </td>
                            <td><?= e(format_date($order['created_at'], 'd/m/Y')) ?></td>
                            <td><?= (int) $order['items_count'] ?></td>
                            <td><strong><?= money($order['total']) ?></strong></td>
                            <td><?= $order['payment_method'] === 'transfer' ? 'تحويل بنكي' : 'عند الاستلام' ?></td>
                            <td><span class="status-badge <?= e(order_status_class($order['status'])) ?>"><?= e(order_status_label($order['status'])) ?></span></td>
                            <td><a class="table-link" href="<?= url('admin/order.php?id=' . (int) $order['id']) ?>">إدارة</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($pages, $start + 4);
            $start = max(1, $end - 4);
            ?>
            <nav class="pagination" aria-label="صفحات الطلبات">
                <?php if ($page > 1): ?>
                    <a class="page-btn" href="<?= url('admin/orders.php?' . http_build_query($queryParams + ['page' => $page - 1])) ?>"><?= icon('right') ?></a>
                <?php endif; ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a class="page-btn<?= $i === $page ? ' is-active' : '' ?>" href="<?= url('admin/orders.php?' . http_build_query($queryParams + ['page' => $i])) ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $pages): ?>
                    <a class="page-btn" href="<?= url('admin/orders.php?' . http_build_query($queryParams + ['page' => $page + 1])) ?>"><?= icon('left') ?></a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
