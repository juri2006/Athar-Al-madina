<?php
/*
 * نظام اللغات في أثر المدينة
* اللغات المدعومة: العربية (ar) - الإنجليزية (en) - الإندونيسية (id) - الأردية (ur)
 *
 * طريقة الاستخدام في أي صفحة (في أول الصفحة):
 *     require_once __DIR__ . "/lang.php";      // أو "../lang.php" داخل مجلد places
 * ثم في الـ HTML:
 *     <html lang="<?= $LANG ?>" dir="<?= $LANG_DIR ?>">
 *     <?= t('nav_home') ?>
 *     <?= lang_switcher() ?>
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$LANGS = [
    "ar" => ["name" => "العربية",          "dir" => "rtl"],
    "en" => ["name" => "English",          "dir" => "ltr"],
    "id" => ["name" => "Bahasa Indonesia", "dir" => "ltr"],
    "ur" => ["name" => "اردو",             "dir" => "rtl"],
];

// تغيير اللغة عند الضغط على أحد أزرار اللغات (?lang=en)
if (isset($_GET["lang"]) && is_string($_GET["lang"]) && isset($LANGS[$_GET["lang"]])) {
    $_SESSION["lang"] = $_GET["lang"];
    if (!headers_sent()) {
        setcookie("lang", $_GET["lang"], time() + 365 * 24 * 3600, "/");
    }
}

$LANG = $_SESSION["lang"] ?? ($_COOKIE["lang"] ?? "ar");
if (!is_string($LANG) || !isset($LANGS[$LANG])) {
    $LANG = "ar";
}

$LANG_DIR   = $LANGS[$LANG]["dir"];
$LANG_ARROW = ($LANG_DIR === "rtl") ? "←" : "→";

// الترجمة الحالية + العربية كاحتياط لأي مفتاح ناقص
$__LANG_AR  = require __DIR__ . "/lang/ar.php";
$__LANG_CUR = ($LANG === "ar") ? $__LANG_AR : require __DIR__ . "/lang/" . $LANG . ".php";

/** إرجاع نص مترجم. مثال: t('pl_selected', 3) */
function t(string $key, ...$args): string
{
    global $__LANG_CUR, $__LANG_AR;
    $text = $__LANG_CUR[$key] ?? $__LANG_AR[$key] ?? $key;
    return $args ? vsprintf($text, $args) : $text;
}

/** ترجمة اسم التصنيف المخزّن في قاعدة البيانات (يبقى عربيًا في القاعدة) */
function cat_label($raw): string
{
    $key = preg_replace('/\s+/u', '', trim((string)$raw));
    $map = [
       "معالمدينية"    => "cat_religious",
     "معالمتاريخية"  => "cat_historical",
      "معالمتارخية"   => "cat_historical",   // الخطأ الإملائي الموجود في بيانات القاعدة
       "متاحف"         => "cat_museums",
     "متاحفوثقافة"   => "cat_museums_culture",
      "أماكنتراثية"   => "cat_heritage",
     "أماكنزراعية"   => "cat_agri",
     "أماكنطبيعية"   => "cat_nature",
     "معالمأخرى"     => "cat_other",
    ];
    return isset($map[$key]) ? t($map[$key]) : trim((string)$raw);
}

/** أنماط أزرار تبديل اللغة */
function lang_switcher_css(): string
{
    return '<style>
.lang-switcher{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.lang-switcher a{font-size:12px;padding:4px 10px;border-radius:14px;border:1px solid rgba(255,255,255,.45);
color:#fff;text-decoration:none;background:rgba(255,255,255,.08);line-height:1.6}
.lang-switcher a:hover{background:rgba(255,255,255,.22)}
.lang-switcher a.active{background:#fff;color:#073d35;font-weight:700}
.lang-switcher.on-light a{color:#174c4c;border-color:#cfdcd8;background:#fff}
.lang-switcher.on-light a.active{background:#174c4c;color:#fff}
</style>';
}

/** تم حذف أزرار اللغات البيضاوية - الاعتماد الآن على القائمة المنسدلة (Google Translate) فقط */
function lang_switcher(string $extraClass = ""): string
{
    return "";
}