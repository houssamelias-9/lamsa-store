<?php
require_once __DIR__ . '/config/config.php';
require_login('profile.php');

$me = current_user();
$profileErrors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        if (mb_strlen($name) < 3) {
            $profileErrors['name'] = 'الاسم يجب أن يكون 3 أحرف على الأقل.';
        } elseif (mb_strlen($name) > 100) {
            $profileErrors['name'] = 'الاسم طويل جداً.';
        }

        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
            $profileErrors['phone'] = 'أدخل رقم هاتف صحيحاً.';
        }

        if (mb_strlen($city) > 80) {
            $profileErrors['city'] = 'اسم المدينة طويل جداً.';
        }

        if (mb_strlen($address) > 255) {
            $profileErrors['address'] = 'العنوان طويل جداً.';
        }

        if (!$profileErrors) {
            $pdo->prepare('UPDATE users SET name = ?, phone = ?, city = ?, address = ? WHERE id = ?')
                ->execute([
                    $name,
                    $phone !== '' ? $phone : null,
                    $city !== '' ? $city : null,
                    $address !== '' ? $address : null,
                    (int) $me['id'],
                ]);

            flash('success', 'تم تحديث بيانات حسابك بنجاح.');
            redirect('profile.php');
        }
    }

    if ($action === 'password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (!password_verify($currentPassword, $me['password'])) {
            $passwordErrors['current_password'] = 'كلمة المرور الحالية غير صحيحة.';
        }

        if (strlen($newPassword) < 8) {
            $passwordErrors['new_password'] = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.';
        } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
            $passwordErrors['new_password'] = 'يجب أن تحتوي كلمة المرور على حرف ورقم على الأقل.';
        } elseif ($newPassword === $currentPassword) {
            $passwordErrors['new_password'] = 'اختر كلمة مرور مختلفة عن الحالية.';
        }

        if ($newPassword !== $confirmPassword) {
            $passwordErrors['confirm_password'] = 'كلمتا المرور غير متطابقتين.';
        }

        if (!$passwordErrors) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int) $me['id']]);

            session_regenerate_id(true);
            flash('success', 'تم تغيير كلمة المرور بنجاح.');
            redirect('profile.php');
        }
    }
}

$statsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS orders_count,
            COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END), 0) AS spent
     FROM orders WHERE user_id = ?"
);
$statsStmt->execute([(int) $me['id']]);
$stats = $statsStmt->fetch();

$pageTitle = 'بيانات حسابي';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-heading">
        <div>
            <span class="eyebrow"><?= icon('user') ?> حسابي</span>
            <h1>بيانات حسابي</h1>
            <p class="page-sub">حدّث معلوماتك الشخصية أو كلمة المرور.</p>
        </div>
    </div>

    <?php require __DIR__ . '/includes/account-nav.php'; ?>

    <div class="profile-layout">
        <div class="profile-main">
            <section class="panel">
                <div class="panel-head">
                    <span class="step"><?= icon('user') ?></span>
                    <div>
                        <h2>المعلومات الشخصية</h2>
                        <p>تظهر هذه البيانات في طلباتك وتُستخدم للتوصيل.</p>
                    </div>
                </div>

                <form method="post" action="<?= url('profile.php') ?>" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="profile">

                    <div class="form-grid">
                        <div class="field">
                            <label for="name">الاسم الكامل</label>
                            <input id="name" type="text" name="name" value="<?= e(old('name', $me['name'])) ?>" required>
                            <?php if (isset($profileErrors['name'])): ?><span class="field-error"><?= e($profileErrors['name']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="email">البريد الإلكتروني</label>
                            <input id="email" type="email" value="<?= e($me['email']) ?>" dir="ltr" disabled>
                            <span class="field-hint">لا يمكن تغيير البريد الإلكتروني.</span>
                        </div>

                        <div class="field">
                            <label for="phone">رقم الهاتف</label>
                            <input id="phone" type="tel" name="phone" value="<?= e(old('phone', $me['phone'] ?? '')) ?>"
                                   placeholder="07XXXXXXXX" dir="ltr">
                            <?php if (isset($profileErrors['phone'])): ?><span class="field-error"><?= e($profileErrors['phone']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="city">المدينة / المحافظة</label>
                            <input id="city" type="text" name="city" value="<?= e(old('city', $me['city'] ?? '')) ?>"
                                   list="profile-cities" placeholder="مثال: بيروت">
                            <datalist id="profile-cities">
                                <?php foreach (cities() as $cityName): ?>
                                    <option value="<?= e($cityName) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <?php if (isset($profileErrors['city'])): ?><span class="field-error"><?= e($profileErrors['city']) ?></span><?php endif; ?>
                        </div>

                        <div class="field field--full">
                            <label for="address">العنوان</label>
                            <input id="address" type="text" name="address" value="<?= e(old('address', $me['address'] ?? '')) ?>"
                                   placeholder="الشارع، رقم البناية، الطابق">
                            <?php if (isset($profileErrors['address'])): ?><span class="field-error"><?= e($profileErrors['address']) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark"><?= icon('check') ?> حفظ التغييرات</button>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <span class="step"><?= icon('shield') ?></span>
                    <div>
                        <h2>تغيير كلمة المرور</h2>
                        <p>استخدم كلمة مرور قوية لا تستخدمها في مواقع أخرى.</p>
                    </div>
                </div>

                <form method="post" action="<?= url('profile.php') ?>" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">

                    <div class="field">
                        <label for="current_password">كلمة المرور الحالية</label>
                        <div class="input-icon">
                            <?= icon('shield') ?>
                            <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
                            <button type="button" class="pw-toggle" data-password-toggle="current_password" aria-label="إظهار كلمة المرور"><?= icon('eye') ?></button>
                        </div>
                        <?php if (isset($passwordErrors['current_password'])): ?><span class="field-error"><?= e($passwordErrors['current_password']) ?></span><?php endif; ?>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label for="new_password">كلمة المرور الجديدة</label>
                            <div class="input-icon">
                                <?= icon('shield') ?>
                                <input id="new_password" type="password" name="new_password" placeholder="8 أحرف على الأقل"
                                       autocomplete="new-password" required>
                                <button type="button" class="pw-toggle" data-password-toggle="new_password" aria-label="إظهار كلمة المرور"><?= icon('eye') ?></button>
                            </div>
                            <?php if (isset($passwordErrors['new_password'])): ?><span class="field-error"><?= e($passwordErrors['new_password']) ?></span><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="confirm_password">تأكيد كلمة المرور</label>
                            <div class="input-icon">
                                <?= icon('shield') ?>
                                <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required>
                                <button type="button" class="pw-toggle" data-password-toggle="confirm_password" aria-label="إظهار كلمة المرور"><?= icon('eye') ?></button>
                            </div>
                            <?php if (isset($passwordErrors['confirm_password'])): ?><span class="field-error"><?= e($passwordErrors['confirm_password']) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark"><?= icon('shield') ?> تحديث كلمة المرور</button>
                </form>
            </section>
        </div>

        <aside class="profile-side">
            <div class="profile-card">
                <span class="profile-avatar"><?= e(mb_substr($me['name'], 0, 1, 'UTF-8')) ?></span>
                <h2><?= e($me['name']) ?></h2>
                <span class="profile-email" dir="ltr"><?= e($me['email']) ?></span>
                <?php if (is_admin()): ?>
                    <span class="role-badge"><?= icon('shield') ?> مدير المتجر</span>
                <?php else: ?>
                    <span class="role-badge role-badge--customer"><?= icon('user') ?> عضو منذ <?= e(format_date($me['created_at'], 'm/Y')) ?></span>
                <?php endif; ?>

                <dl class="profile-stats">
                    <div><dt>الطلبات</dt><dd><?= (int) $stats['orders_count'] ?></dd></div>
                    <div><dt>المشتريات</dt><dd><?= money($stats['spent']) ?></dd></div>
                </dl>

                <a class="btn btn-outline btn-block" href="<?= url('orders.php') ?>"><?= icon('box') ?> عرض طلباتي</a>
            </div>

            <section class="panel panel--soft">
                <h2 class="panel-title"><?= icon('shield') ?> نصائح لحسابك</h2>
                <ul class="tips-list">
                    <li>استخدم كلمة مرور تحتوي حرفاً ورقماً على الأقل.</li>
                    <li>لا تشارك بيانات الدخول مع أي شخص.</li>
                    <li>تأكد من عنوان التوصيل قبل تأكيد الطلب.</li>
                </ul>
            </section>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
