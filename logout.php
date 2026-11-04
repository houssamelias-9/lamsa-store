<?php
require_once __DIR__ . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    unset($_SESSION['user_id'], $_SESSION['login_attempts']);
    session_regenerate_id(true);
    flash('info', 'تم تسجيل الخروج بنجاح. نراك قريباً!');
}

redirect('index.php');
