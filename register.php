<?php
require_once __DIR__ . '/config/config.php';

$next = safe_next($_POST['next'] ?? $_GET['next'] ?? '');

if (is_logged_in()) {
    redirect($next);
}

$name = '';
$email = '';
$phone = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (mb_strlen($name) < 3) {
        $errors['name'] = 'الاسم يجب أن يكون 3 أحرف على الأقل.';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'الاسم طويل جداً.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'أدخل بريداً إلكترونياً صحيحاً.';
    } elseif (strlen($email) > 190) {
        $errors['email'] = 'البريد الإلكتروني طويل جداً.';
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $errors['phone'] = 'أدخل رقم هاتف صحيحاً (أرقام فقط).';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'يجب أن تحتوي كلمة المرور على حرف ورقم على الأقل.';
    }

    if ($passwordConfirm !== $password) {
        $errors['password_confirm'] = 'كلمتا المرور غير متطابقتين.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'هذا البريد الإلكتروني مسجّل بالفعل. يمكنك تسجيل الدخول.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $phone !== '' ? $phone : null,
                'customer',
            ]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();

            flash('success', 'تم إنشاء حسابك بنجاح! أهلاً بك في ' . SITE_NAME . '، ' . $name . '.');
            redirect($next);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['email'] = 'هذا البريد الإلكتروني مسجّل بالفعل.';
            } else {
                $errors['general'] = 'حدث خطأ غير متوقع. حاول مرة أخرى.';
            }
        }
    }
}

$pageTitle = 'إنشاء حساب';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-layout">
        <?php require __DIR__ . '/includes/auth-aside.php'; ?>

        <section class="auth-card">
            <div class="auth-head">
                <span class="eyebrow">انضم إلينا</span>
                <h1>إنشاء حساب جديد</h1>
                <p>أقل من دقيقة وتكون جاهزاً للتسوق.</p>
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

            <form method="post" action="<?= url('register.php') ?>" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">

                <div class="field">
                    <label for="name">الاسم الكامل</label>
                    <div class="input-icon">
                        <?= icon('user') ?>
                        <input id="name" type="text" name="name" value="<?= e($name) ?>"
                               placeholder="مثال: سارة أحمد" autocomplete="name" required>
                    </div>
                    <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="email">البريد الإلكتروني</label>
                    <div class="input-icon">
                        <?= icon('mail') ?>
                        <input id="email" type="email" name="email" value="<?= e($email) ?>"
                               placeholder="you@example.com" dir="ltr" autocomplete="email" required>
                    </div>
                    <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="phone">رقم الهاتف <span class="optional">(اختياري)</span></label>
                    <div class="input-icon">
                        <?= icon('phone') ?>
                        <input id="phone" type="tel" name="phone" value="<?= e($phone) ?>"
                               placeholder="07XXXXXXXX" dir="ltr" autocomplete="tel">
                    </div>
                    <?php if (isset($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="password">كلمة المرور</label>
                        <div class="input-icon">
                            <?= icon('shield') ?>
                            <input id="password" type="password" name="password" placeholder="8 أحرف على الأقل"
                                   autocomplete="new-password" required>
                            <button type="button" class="pw-toggle" data-password-toggle="password" aria-label="إظهار كلمة المرور"><?= icon('eye') ?></button>
                        </div>
                        <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="password_confirm">تأكيد كلمة المرور</label>
                        <div class="input-icon">
                            <?= icon('shield') ?>
                            <input id="password_confirm" type="password" name="password_confirm" placeholder="أعد كتابة كلمة المرور"
                                   autocomplete="new-password" required>
                            <button type="button" class="pw-toggle" data-password-toggle="password_confirm" aria-label="إظهار كلمة المرور"><?= icon('eye') ?></button>
                        </div>
                        <?php if (isset($errors['password_confirm'])): ?><span class="field-error"><?= e($errors['password_confirm']) ?></span><?php endif; ?>
                    </div>
                </div>

                <p class="form-hint"><?= icon('shield') ?> بياناتك مشفّرة ومحفوظة بأمان. بإنشاء الحساب أنت توافق على شروط الاستخدام.</p>

                <button type="submit" class="btn btn-dark btn-lg btn-block">إنشاء الحساب</button>
            </form>

            <p class="auth-switch">لديك حساب بالفعل؟ <a href="<?= url('login.php?next=' . urlencode($next)) ?>">سجّل الدخول</a></p>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
