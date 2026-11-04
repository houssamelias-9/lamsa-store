<?php
require_once __DIR__ . '/config/config.php';

$next = safe_next($_POST['next'] ?? $_GET['next'] ?? '');

if (is_logged_in()) {
    redirect($next);
}

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    /* حماية بسيطة من محاولات الدخول المتكررة */
    $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'last' => 0];
    $lockedFor = 0;
    if ((int) $attempts['count'] >= 5 && (time() - (int) $attempts['last']) < 300) {
        $lockedFor = 300 - (time() - (int) $attempts['last']);
    }

    if ($lockedFor > 0) {
        $errors[] = 'محاولات دخول كثيرة. حاول مرة أخرى بعد ' . (int) ceil($lockedFor / 60) . ' دقيقة.';
    } else {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'أدخل بريداً إلكترونياً صحيحاً.';
        }
        if ($password === '') {
            $errors[] = 'أدخل كلمة المرور.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                unset($_SESSION['login_attempts']);

                flash('success', 'أهلاً بعودتك، ' . $user['name'] . '!');
                redirect($next);
            }

            $attempts['count'] = (int) $attempts['count'] + 1;
            $attempts['last'] = time();
            $_SESSION['login_attempts'] = $attempts;
            $errors[] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        }
    }
}

$pageTitle = 'تسجيل الدخول';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-layout">
        <?php require __DIR__ . '/includes/auth-aside.php'; ?>

        <section class="auth-card">
            <div class="auth-head">
                <span class="eyebrow">مرحباً بعودتك</span>
                <h1>تسجيل الدخول</h1>
                <p>أدخل بياناتك لمتابعة طلباتك وإكمال تسوقك.</p>
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

            <form method="post" action="<?= url('login.php') ?>" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">

                <div class="field">
                    <label for="email">البريد الإلكتروني</label>
                    <div class="input-icon">
                        <?= icon('mail') ?>
                        <input id="email" type="email" name="email" value="<?= e($email) ?>"
                               placeholder="you@example.com" dir="ltr" autocomplete="email" required>
                    </div>
                </div>

                <div class="field">
                    <label for="password">كلمة المرور</label>
                    <div class="input-icon">
                        <?= icon('shield') ?>
                        <input id="password" type="password" name="password" placeholder="••••••••"
                               autocomplete="current-password" required>
                        <button type="button" class="pw-toggle" data-password-toggle="password" aria-label="إظهار كلمة المرور"><?= icon('eye') ?></button>
                    </div>
                </div>

                <button type="submit" class="btn btn-dark btn-lg btn-block">دخول إلى حسابي</button>
            </form>

            <p class="auth-switch">ليس لديك حساب؟ <a href="<?= url('register.php?next=' . urlencode($next)) ?>">أنشئ حساباً جديداً</a></p>
        </section>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
