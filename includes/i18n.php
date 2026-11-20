<?php

function lamsa_lang_code(): string
{
    if (isset($_GET['lang'])) {
        $lang = $_GET['lang'] === 'en' ? 'en' : 'ar';
        setcookie('lamsa_lang', $lang, time() + 31536000, '/', '', false, false);
        return $lang;
    }
    return (($_COOKIE['lamsa_lang'] ?? 'ar') === 'en') ? 'en' : 'ar';
}

$LAMSA_LANG = lamsa_lang_code();
$GLOBALS['LAMSA_LANG'] = $LAMSA_LANG;

function lamsa_lang_switch_url(string $lang): string
{
    $params = $_GET;
    $params['lang'] = $lang;
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    return htmlspecialchars($path . '?' . http_build_query($params), ENT_QUOTES, 'UTF-8');
}

function lamsa_translate_output(string $html): string
{
    $lang = $GLOBALS['LAMSA_LANG'] ?? 'ar';
    if ($lang === 'en') {
        static $map = null;
        if ($map === null) {
            $map = require dirname(__DIR__) . '/lang/en.php';
        }
        $html = strtr($html, $map);
        $html = str_replace('<html lang="ar" dir="rtl">', '<html lang="en" dir="ltr">', $html);
    }

    $arUrl = lamsa_lang_switch_url('ar');
    $enUrl = lamsa_lang_switch_url('en');
    $arActive = $lang === 'ar' ? ' is-active' : '';
    $enActive = $lang === 'en' ? ' is-active' : '';

    $css = '<style id="lamsa-lang-css">'
        . '.lamsa-lang{position:fixed;bottom:18px;right:18px;z-index:2147483647;display:flex;align-items:center;gap:2px;padding:4px;background:#fff;border:1px solid #e5e5e5;border-radius:999px;box-shadow:0 6px 24px rgba(0,0,0,.18);font:600 13px/1 system-ui,-apple-system,Segoe UI,sans-serif;direction:ltr}'
        . '.lamsa-lang a{display:block;padding:9px 14px;border-radius:999px;color:#555;text-decoration:none;white-space:nowrap}'
        . '.lamsa-lang a.is-active{background:#111;color:#fff}'
        . '.lamsa-lang a:hover{color:#111}'
        . '.lamsa-lang a.is-active:hover{color:#fff}'
        . '</style>';
    $switcher = '<nav class="lamsa-lang" aria-label="Language">'
        . '<a href="' . $arUrl . '" class="' . trim($arActive) . '">العربية</a>'
        . '<a href="' . $enUrl . '" class="' . trim($enActive) . '">English</a>'
        . '</nav>';

    if (stripos($html, '</head>') !== false) {
        $html = preg_replace('/<\/head>/i', $css . '</head>', $html, 1);
    } else {
        $html = $css . $html;
    }
    return preg_replace_callback('/<body[^>]*>/i', static function ($m) use ($switcher) {
        return $m[0] . $switcher;
    }, $html, 1);
}

ob_start('lamsa_translate_output');
