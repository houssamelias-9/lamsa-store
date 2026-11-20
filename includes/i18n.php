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
    $switcher = '<nav class="lamsa-lang" aria-label="Language">'
        . '<a href="' . $arUrl . '" class="' . $arActive . '">العربية</a>'
        . '<a href="' . $enUrl . '" class="' . $enActive . '">English</a>'
        . '</nav>';
    return preg_replace_callback('/<body[^>]*>/i', static function ($m) use ($switcher) {
        return $m[0] . $switcher;
    }, $html, 1);
}

ob_start('lamsa_translate_output');
