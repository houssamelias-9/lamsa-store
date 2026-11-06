<?php
require_once __DIR__ . '/config/config.php';
require_login('checkout.php');

$me = current_user();
$items = cart_items();

if (!$items) {
    flash('info', 'سلتك فارغة. أضف بعض المنتجات قبل إتمام الطلب.');
    redirect('cart.php');
}

$subtotal = 0.0;
foreach ($items as $item) {
    $subtotal += (float) $item['line_total'];
}
$subtotal = round($subtotal, 2);
$shipping = shipping_cost($subtotal);
$total = round($subtotal + $shipping, 2);

$values = [
    'customer_name'  => old('customer_name', $me['name']),
    'phone'          => old('phone', $me['phone'] ?? ''),
    'city'           => old('city', $me['city'] ?? ''),
    'address'        => old('address', $me['address'] ?? ''),
    'notes'          => old('notes'),
    'payment_method' => old('payment_method', 'cod'),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = $values['customer_name'];
    $phone = $values['phone'];
    $city = $values['city'];
    $address = $values['address'];
    $notes = $values['notes'];
    $payment = $values['payment_method'];

    if (mb_strlen($name) < 3) {
        $errors['customer_name'] = 'أدخل الاسم الكامل (3 أحرف على الأقل).';
    } elseif (mb_strlen($name) > 100) {
        $errors['customer_name'] = 'الاسم طويل جداً.';
    }

    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $errors['phone'] = 'أدخل رقم هاتف صحيحاً.';
    }

    if ($city === '') {
        $errors['city'] = 'اختر المدينة أو المحافظة.';
    } elseif (mb_strlen($city) > 80) {
        $errors['city'] = 'اسم المدينة طويل جداً.';
    }

    if (mb_strlen($address) < 5) {
        $errors['address'] = 'أدخل عنواناً واضحاً (الشارع، رقم البناية، الطابق).';
    } elseif (mb_strlen($address) > 255) {
        $errors['address'] = 'العنوان طويل جداً.';
    }

    if (mb_strlen($notes) > 500) {
        $errors['notes'] = 'الملاحظات يجب ألا تتجاوز 500 حرف.';
    }

    if (!in_array($payment, ['cod', 'transfer'], true)) {
        $errors['payment_method'] = 'اختر طريقة دفع صحيحة.';
    }

    if (!$errors) {
        $orderId = 0;

        try {
            $pdo->beginTransaction();

            $ids = array_map(static fn($item) => (int) $item['product']['id'], $items);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $pdo->prepare(
                "SELECT id, name, price, stock, is_active FROM products WHERE id IN ($placeholders) FOR UPDATE"
            );
            $stmt->execute($ids);

            $locked = [];
            foreach ($stmt->fetchAll() as $row) {
                $locked[(int) $row['id']] = $row;
            }

            $verifiedItems = [];
            $verifiedSubtotal = 0.0;

            foreach ($items as $item) {
                $id = (int) $item['product']['id'];
                $qty = (int) $item['qty'];

                if (!isset($locked[$id]) || (int) $locked[$id]['is_active'] !== 1) {
                    throw new RuntimeException('عذراً، المنتج «' . $item['product']['name'] . '» لم يعد متوفراً. حدّث سلتك وحاول مجدداً.');
                }

                if ((int) $locked[$id]['stock'] < $qty) {
                    throw new RuntimeException(
                        'الكمية المطلوبة من «' . $locked[$id]['name'] . '» غير متوفرة. المتوفر حالياً: '
                        . (int) $locked[$id]['stock'] . ' قطعة.'
                    );
                }

                $lineTotal = round((float) $locked[$id]['price'] * $qty, 2);
                $verifiedSubtotal += $lineTotal;

                $verifiedItems[] = [
                    'id'         => $id,
                    'name'       => $locked[$id]['name'],
                    'unit_price' => (float) $locked[$id]['price'],
                    'qty'        => $qty,
                    'line_total' => $lineTotal,
                ];
            }

            $verifiedSubtotal = round($verifiedSubtotal, 2);
            $shipping = shipping_cost($verifiedSubtotal);
            $total = round($verifiedSubtotal + $shipping, 2);
            $tempNumber = 'TMP-' . bin2hex(random_bytes(7));

            $insertOrder = $pdo->prepare(
                'INSERT INTO orders (user_id, order_number, subtotal, shipping, total, status, payment_method,
                    customer_name, phone, city, address, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insertOrder->execute([
                (int) $me['id'],
                $tempNumber,
                $verifiedSubtotal,
                $shipping,
                $total,
                'pending',
                $payment,
                $name,
                $phone,
                $city,
                $address,
                $notes !== '' ? $notes : null,
            ]);

            $orderId = (int) $pdo->lastInsertId();
            $orderNumber = 'LM-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);

            $pdo->prepare('UPDATE orders SET order_number = ? WHERE id = ?')
                ->execute([$orderNumber, $orderId]);

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $decreaseStock = $pdo->prepare(
                'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
            );

            foreach ($verifiedItems as $verifiedItem) {
                $insertItem->execute([
                    $orderId,
                    $verifiedItem['id'],
                    $verifiedItem['name'],
                    $verifiedItem['unit_price'],
                    $verifiedItem['qty'],
                    $verifiedItem['line_total'],
                ]);

                $decreaseStock->execute([
                    $verifiedItem['qty'],
                    $verifiedItem['id'],
                    $verifiedItem['qty'],
                ]);

                if ($decreaseStock->rowCount() !== 1) {
                    throw new RuntimeException('تعذّر تحديث المخزون. حاول مرة أخرى.');
                }
            }

            $pdo->prepare('UPDATE users SET phone = ?, city = ?, address = ? WHERE id = ?')
                ->execute([$phone, $city, $address, (int) $me['id']]);

            $pdo->commit();

            cart_clear();
            flash('success', 'تم استلام طلبك بنجاح! سنتواصل معك قريباً لتأكيد التوصيل.');
            redirect('order.php?id=' . $orderId . '&success=1');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                flash('error', $e->getMessage());
            } else {
                error_log('Checkout error: ' . $e->getMessage());
                flash('error', 'حدث خطأ غير متوقع أثناء إتمام الطلب. لم يتم خصم أي مبلغ، حاول مرة أخرى.');
            }

            redirect('cart.php');
        }
    }
}

$pageTitle = 'إتمام الطلب';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav class="breadcrumb" aria-label="مسار التنقل">
        <a href="<?= url('index.php') ?>">الرئيسية</a>
        <?= icon('left') ?>
        <a href="<?= url('cart.php') ?>">السلة</a>
        <?= icon('left') ?>
        <span>إتمام الطلب</span>
    </nav>

    <div class="page-heading">
        <div>
            <span class="eyebrow"><?= icon('shield') ?> خطوات قليلة ويصلك طلبك</span>
            <h1>إتمام الطلب</h1>
        </div>
        <div class="checkout-steps">
            <span class="is-done">السلة</span>
            <span class="is-active">البيانات</span>
            <span>التأكيد</span>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="form-errors" role="alert">
            <?= icon('close') ?>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="checkout-layout">
        <form method="post" action="<?= url('checkout.php') ?>" class="checkout-form" novalidate>
            <?= csrf_field() ?>

            <section class="panel">
                <div class="panel-head">
                    <span class="step">1</span>
                    <div>
                        <h2>بيانات التوصيل</h2>
                        <p>سنستخدم هذه البيانات لشحن طلبك.</p>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="customer_name">الاسم الكامل</label>
                        <input id="customer_name" type="text" name="customer_name" value="<?= e($values['customer_name']) ?>"
                               autocomplete="name" required>
                        <?php if (isset($errors['customer_name'])): ?><span class="field-error"><?= e($errors['customer_name']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="phone">رقم الهاتف</label>
                        <input id="phone" type="tel" name="phone" value="<?= e($values['phone']) ?>"
                               placeholder="07XXXXXXXX" dir="ltr" autocomplete="tel" required>
                        <?php if (isset($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="city">المدينة / المحافظة</label>
                        <input id="city" type="text" name="city" value="<?= e($values['city']) ?>"
                               list="cities-list" placeholder="مثال: بيروت" autocomplete="address-level2" required>
                        <datalist id="cities-list">
                            <?php foreach (cities() as $cityName): ?>
                                <option value="<?= e($cityName) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <?php if (isset($errors['city'])): ?><span class="field-error"><?= e($errors['city']) ?></span><?php endif; ?>
                    </div>

                    <div class="field field--full">
                        <label for="address">العنوان التفصيلي</label>
                        <input id="address" type="text" name="address" value="<?= e($values['address']) ?>"
                               placeholder="الشارع، رقم البناية، الطابق" autocomplete="street-address" required>
                        <?php if (isset($errors['address'])): ?><span class="field-error"><?= e($errors['address']) ?></span><?php endif; ?>
                    </div>

                    <div class="field field--full">
                        <label for="notes">ملاحظات التوصيل <span class="optional">(اختياري)</span></label>
                        <textarea id="notes" name="notes" rows="3" maxlength="500"
                                  placeholder="مثال: الرجاء الاتصال قبل الوصول"><?= e($values['notes']) ?></textarea>
                        <?php if (isset($errors['notes'])): ?><span class="field-error"><?= e($errors['notes']) ?></span><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <span class="step">2</span>
                    <div>
                        <h2>طريقة الدفع</h2>
                        <p>اختر الطريقة الأنسب لك.</p>
                    </div>
                </div>

                <div class="pay-options">
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="cod"
                               <?= $values['payment_method'] === 'cod' ? 'checked' : '' ?>>
                        <span class="pay-body">
                            <span class="pay-icon"><?= icon('truck') ?></span>
                            <span class="pay-copy">
                                <strong>الدفع عند الاستلام</strong>
                                <small>ادفع نقداً عند وصول الطلب إلى بابك.</small>
                            </span>
                            <span class="pay-check"><?= icon('check') ?></span>
                        </span>
                    </label>

                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="transfer"
                               <?= $values['payment_method'] === 'transfer' ? 'checked' : '' ?>>
                        <span class="pay-body">
                            <span class="pay-icon"><?= icon('shield') ?></span>
                            <span class="pay-copy">
                                <strong>تحويل بنكي</strong>
                                <small>سنرسل لك تفاصيل الحساب بعد تأكيد الطلب.</small>
                            </span>
                            <span class="pay-check"><?= icon('check') ?></span>
                        </span>
                    </label>
                </div>
                <?php if (isset($errors['payment_method'])): ?><span class="field-error"><?= e($errors['payment_method']) ?></span><?php endif; ?>
            </section>

            <button type="submit" class="btn btn-accent btn-lg btn-block checkout-submit">
                <?= icon('shield') ?> تأكيد الطلب — <?= money($total) ?>
            </button>
            <p class="checkout-terms">بالضغط على «تأكيد الطلب» أنت توافق على شروط الاستخدام وسياسة الإرجاع.</p>
        </form>

        <aside class="checkout-summary">
            <div class="summary-head">
                <h2>طلبك</h2>
                <a href="<?= url('cart.php') ?>">تعديل السلة</a>
            </div>

            <ul class="summary-items">
                <?php foreach ($items as $item): ?>
                    <?php $p = $item['product']; ?>
                    <li>
                        <div class="summary-thumb">
                            <img src="<?= e(media_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                            <span class="summary-qty"><?= (int) $item['qty'] ?></span>
                        </div>
                        <div class="summary-item-info">
                            <strong><?= e($p['name']) ?></strong>
                            <span><?= money($p['price']) ?> × <?= (int) $item['qty'] ?></span>
                        </div>
                        <span class="summary-line"><?= money($item['line_total']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <dl class="summary-list">
                <div><dt>المجموع الفرعي</dt><dd><?= money($subtotal) ?></dd></div>
                <div>
                    <dt>الشحن</dt>
                    <dd><?= $shipping > 0 ? money($shipping) : '<span class="free-tag">مجاني</span>' ?></dd>
                </div>
                <div class="summary-total"><dt>الإجمالي</dt><dd><?= money($total) ?></dd></div>
            </dl>

            <?php if ($shipping > 0): ?>
                <p class="summary-note summary-note--soft">
                    <?= icon('truck') ?> أضف <?= money(max(0, FREE_SHIPPING_FROM - $subtotal)) ?> للحصول على شحن مجاني.
                </p>
            <?php else: ?>
                <p class="summary-note summary-note--soft"><?= icon('check') ?> طلبك مؤهّل للشحن المجاني.</p>
            <?php endif; ?>

            <ul class="summary-perks">
                <li><?= icon('shield') ?> دفع آمن ومشفّر</li>
                <li><?= icon('refresh') ?> إرجاع مجاني خلال 14 يوماً</li>
                <li><?= icon('phone') ?> دعم على الرقم <span dir="ltr">+961 70 000 000</span></li>
            </ul>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
