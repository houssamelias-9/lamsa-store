<?php
require_once dirname(__DIR__) . '/config/config.php';
require_admin();

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare(
    'SELECT o.*, u.email AS user_email FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     WHERE o.id = ? LIMIT 1'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'الطلب غير موجود.');
    redirect('admin/orders.php');
}

$itemsStmt = $pdo->prepare(
    'SELECT oi.*, p.image FROM order_items oi
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ? ORDER BY oi.id ASC'
);
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

/* ---------- تحديث الحالة ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $newStatus = isset($_POST['status']) && is_string($_POST['status']) ? $_POST['status'] : '';
    $currentStatus = $order['status'];

    if (!array_key_exists($newStatus, order_statuses())) {
        flash('error', 'حالة الطلب غير صحيحة.');
    } elseif ($newStatus === $currentStatus) {
        flash('info', 'حالة الطلب لم تتغير.');
    } elseif ($currentStatus === 'cancelled') {
        flash('error', 'لا يمكن تعديل حالة طلب ملغي.');
    } elseif ($newStatus === 'cancelled' && !in_array($currentStatus, ['pending', 'processing'], true)) {
        flash('error', 'لا يمكن إلغاء الطلب بعد شحنه. تواصل مع العميل لإتمام الإرجاع.');
    } else {
        try {
            $pdo->beginTransaction();

            if ($newStatus === 'cancelled') {
                $restoreStock = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                foreach ($orderItems as $item) {
                    if (!empty($item['product_id'])) {
                        $restoreStock->execute([(int) $item['quantity'], (int) $item['product_id']]);
                    }
                }
            }

            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $orderId]);
            $pdo->commit();

            flash('success', $newStatus === 'cancelled'
                ? 'تم إلغاء الطلب وإعادة الكميات إلى المخزون.'
                : 'تم تحديث حالة الطلب إلى «' . order_status_label($newStatus) . '».');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Order status update error: ' . $e->getMessage());
            flash('error', 'تعذّر تحديث حالة الطلب. حاول مرة أخرى.');
        }
    }

    redirect('admin/order.php?id=' . $orderId);
}

$canCancel = in_array($order['status'], ['pending', 'processing'], true);
$paymentLabel = $order['payment_method'] === 'transfer' ? 'تحويل بنكي' : 'الدفع عند الاستلام';
$itemsCount = (int) array_sum(array_column($orderItems, 'quantity'));

$adminTitle = 'الطلب ' . $order['order_number'];
$adminSubtitle = 'تفاصيل الطلب وتحديث حالته';
$adminActive = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-page-head">
    <a class="link-more" href="<?= url('admin/orders.php') ?>"><?= icon('right') ?> العودة إلى الطلبات</a>
    <a class="btn btn-outline btn-sm" href="<?= url('order.php?id=' . $orderId) ?>" target="_blank" rel="noopener">
        <?= icon('eye') ?> عرض كالعميل
    </a>
</div>

<div class="admin-order-head">
    <div>
        <span class="eyebrow">رقم الطلب</span>
        <h2 class="admin-order-number" dir="ltr"><?= e($order['order_number']) ?></h2>
        <span class="page-sub"><?= e(format_date($order['created_at'], 'd/m/Y H:i')) ?></span>
    </div>
    <span class="status-badge status-badge--lg <?= e(order_status_class($order['status'])) ?>">
        <?= e(order_status_label($order['status'])) ?>
    </span>
</div>

<div class="admin-grid">
    <section class="admin-card admin-card--wide">
        <div class="admin-card-head">
            <div>
                <h2>منتجات الطلب</h2>
                <p><?= count($orderItems) ?> منتج · <?= $itemsCount ?> قطعة</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>سعر الوحدة</th>
                        <th>الكمية</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <div class="cell-product">
                                    <img src="<?= e(media_url($item['image'] ?? '')) ?>" alt="" loading="lazy">
                                    <div>
                                        <?php if ($item['product_id']): ?>
                                            <a href="<?= url('admin/product-form.php?id=' . (int) $item['product_id']) ?>"><?= e($item['product_name']) ?></a>
                                        <?php else: ?>
                                            <span><?= e($item['product_name']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!$item['product_id']): ?><small class="cell-sub">منتج محذوف</small><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= money($item['unit_price']) ?></td>
                            <td><?= (int) $item['quantity'] ?></td>
                            <td><strong><?= money($item['line_total']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

    <div class="admin-order-side">
        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>تحديث الحالة</h2>
                    <p>سيطّلع العميل على الحالة الجديدة</p>
                </div>
            </div>

            <?php if ($order['status'] === 'cancelled'): ?>
                <div class="notice notice--error notice--compact">
                    <?= icon('close') ?>
                    <span>هذا الطلب ملغي ولا يمكن تعديل حالته.</span>
                </div>
            <?php else: ?>
                <form method="post" action="<?= url('admin/order.php?id=' . $orderId) ?>" class="form">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="status">حالة الطلب</label>
                        <select id="status" name="status" required>
                            <?php foreach (order_statuses() as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $order['status'] === $key ? 'selected' : '' ?>
                                    <?= $key === 'cancelled' && !$canCancel ? 'disabled' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$canCancel): ?>
                            <span class="field-hint">لا يمكن إلغاء الطلب بعد شحنه.</span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-accent btn-block"><?= icon('check') ?> حفظ الحالة</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>بيانات العميل</h2>
                </div>
            </div>
            <ul class="info-list">
                <li><span>الاسم</span><strong><?= e($order['customer_name']) ?></strong></li>
                <li><span>الهاتف</span><strong dir="ltr"><?= e($order['phone']) ?></strong></li>
                <li><span>البريد</span><strong dir="ltr"><?= e($order['user_email'] ?? '—') ?></strong></li>
                <li><span>المدينة</span><strong><?= e($order['city']) ?></strong></li>
                <li><span>العنوان</span><strong><?= e($order['address']) ?></strong></li>
            </ul>

            <?php if (!empty($order['notes'])): ?>
                <div class="order-notes">
                    <span>ملاحظات العميل:</span>
                    <p><?= nl2br(e($order['notes'])) ?></p>
                </div>
            <?php endif; ?>
        </section>

        <section class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>الدفع</h2>
                </div>
            </div>
            <ul class="info-list">
                <li><span>طريقة الدفع</span><strong><?= e($paymentLabel) ?></strong></li>
                <li><span>حالة الدفع</span><strong>غير مدفوع</strong></li>
                <li><span>الإجمالي</span><strong><?= money($order['total']) ?></strong></li>
            </ul>
            <p class="panel-text">تُحصّل قيمة الطلب عند التسليم أو عبر التحويل البنكي حسب اختيار العميل.</p>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
