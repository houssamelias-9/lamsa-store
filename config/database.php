<?php
/* الاتصال بقاعدة البيانات عبر PDO */

$lamsaDsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

try {
    $pdo = new PDO($lamsaDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    $errorDetails = APP_DEBUG
        ? '<code style="display:block;margin-top:18px;padding:12px;background:#fff;border-radius:8px;color:#b91c1c;direction:ltr;text-align:left;font-size:13px;overflow:auto">'
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</code>'
        : '';
    echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>تعذّر الاتصال بقاعدة البيانات</title></head>'
        . '<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f5f2;font-family:Tahoma,Arial,sans-serif;color:#14151a">'
        . '<div style="max-width:620px;margin:24px;background:#fff;border:1px solid #e8e3dc;border-radius:16px;padding:32px;box-shadow:0 20px 50px rgba(20,21,26,.08)">'
        . '<div style="font-size:42px;line-height:1">🛠️</div>'
        . '<h1 style="margin:12px 0 8px;font-size:22px">تعذّر الاتصال بقاعدة البيانات</h1>'
        . '<p style="margin:0 0 18px;color:#6b6b73;line-height:1.9">تأكد من الخطوات التالية ثم حدّث الصفحة:</p>'
        . '<ol style="margin:0;padding-inline-start:20px;line-height:2;color:#3f3f46">'
        . '<li>شغّل <strong>MySQL</strong> من لوحة تحكم XAMPP.</li>'
        . '<li>افتح <strong>phpMyAdmin</strong> واستورد ملف <strong>database.sql</strong>.</li>'
        . '<li>تأكد أن اسم القاعدة <strong>' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</strong> مطابق لملف <code>config/config.php</code>.</li>'
        . '</ol>'
        . $errorDetails
        . '</div></body></html>';
    exit;
}
