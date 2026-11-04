<?php
/* ============================================================
   إعدادات المتجر — عدّل من هنا فقط
   ============================================================ */

define('SITE_NAME', 'لمسة');
define('SITE_TAGLINE', 'أناقة يومية بأسعار تناسبك');
define('CURRENCY', 'د.أ');

/* رسوم التوصيل وحد الشحن المجاني */
define('SHIPPING_FEE', 2.00);
define('FREE_SHIPPING_FROM', 50.00);

/* بيانات قاعدة البيانات الافتراضية في XAMPP */
define('DB_HOST', getenv('LAMSA_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('LAMSA_DB_PORT') ?: '3306');
define('DB_NAME', getenv('LAMSA_DB_NAME') ?: 'lamsa_store');
define('DB_USER', getenv('LAMSA_DB_USER') ?: 'root');
$lamsaDbPass = getenv('LAMSA_DB_PASS');
define('DB_PASS', $lamsaDbPass === false ? '' : $lamsaDbPass);

/* ============================================================
   لا تحتاج لتعديل ما بعد هذا السطر
   ============================================================ */

date_default_timezone_set('Asia/Amman');

/* إظهار الأخطاء محلياً فقط (مفيد أثناء التطوير على XAMPP) */
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$isLocalHost = in_array($serverName, ['localhost', '127.0.0.1', '::1'], true);
define('APP_DEBUG', $isLocalHost || getenv('LAMSA_DEBUG') === '1');

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

/* اكتشاف مسار المشروع تلقائياً (يعمل داخل أي مجلد في htdocs) */
$lamsaAppRoot = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
$lamsaDocRoot = isset($_SERVER['DOCUMENT_ROOT'])
    ? str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT']))
    : '';
$lamsaBasePath = '';
if ($lamsaDocRoot !== '' && $lamsaAppRoot !== ''
    && str_starts_with(strtolower($lamsaAppRoot), strtolower($lamsaDocRoot))) {
    $lamsaBasePath = substr($lamsaAppRoot, strlen($lamsaDocRoot));
}
define('BASE_URL', rtrim($lamsaBasePath, '/') . '/');

/* الجلسة */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('LAMSA_SESSION');
    session_start();
}

require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
