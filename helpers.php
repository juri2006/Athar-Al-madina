<?php
/*
 * دوال مساعدة لعرض صور المعالم.
 * المشكلة: أسماء الصور المخزنة في قاعدة البيانات لا تطابق دائمًا أسماء الملفات
 * الفعلية داخل مجلد images (مثل: image/ بدل images/ ، أو "أحد" بدل "احد").
 * هذه الدالة تجد أقرب ملف صورة موجود فعليًا.
 */

function normalize_image_name(string $name): string
{
    $name = pathinfo($name, PATHINFO_FILENAME);
    $name = mb_strtolower($name, "UTF-8");
    $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $name); // التشكيل والتطويل
    $name = strtr($name, ["أ" => "ا", "إ" => "ا", "آ" => "ا", "ٱ" => "ا", "ى" => "ي", "ة" => "ه", "ؤ" => "و", "ئ" => "ي"]);
    return preg_replace('/[^\p{L}\p{N}]+/u', '', $name);
}

/**
 * ترجع مسار الصورة نسبةً إلى جذر الموقع (مثل: images/بئر غرس.png) أو "" إن لم توجد.
 * $root = المسار الفعلي لجذر المشروع.
 */
function resolve_image(?string $raw, string $root): string
{
    $raw = trim((string)$raw);
    if ($raw === "") {
        return "";
    }

    $raw = str_replace("\\", "/", $raw);
    $raw = preg_replace('#^\.?/*#', '', $raw);

    // 1) المسار كما هو (مثل uploads/xxxx.jpg أو images/xxxx.png)
    if (is_file($root . "/" . $raw)) {
        return $raw;
    }

    // 2) تصحيح image/ إلى images/
    $fixed = preg_replace('#^image/#u', 'images/', $raw);
    if (is_file($root . "/" . $fixed)) {
        return $fixed;
    }

    // 3) البحث عن أقرب اسم ملف داخل images/
    static $files = null;
    if ($files === null) {
        $files = [];
        foreach (glob($root . "/images/*.*") ?: [] as $f) {
            $base = basename($f);
            $files[$base] = normalize_image_name($base);
        }
    }

    $target = normalize_image_name(basename($raw));
    if ($target === "") {
        return "";
    }

    $best = "";
    $bestScore = 0;
    foreach ($files as $base => $norm) {
        if ($norm === $target) {
            return "images/" . $base;
        }
        // أحد الاسمين جزء من الآخر (مثل: "مسجد الفتح" داخل "مسجد الفتح (المسجد السبعة)")
        $shorter = min(mb_strlen($norm), mb_strlen($target));
        if (
            $shorter >= 8 &&
            (mb_strpos($norm, $target) !== false || mb_strpos($target, $norm) !== false)
        ) {
            $percent = 95;
        } else {
            similar_text($target, $norm, $percent);
        }
        if ($percent > $bestScore) {
            $bestScore = $percent;
            $best = $base;
        }
    }

    return ($bestScore >= 80) ? "images/" . $best : "";
}

/** ترميز مسار الصورة ليصلح داخل رابط (يدعم الأسماء العربية والمسافات) */
function image_url(string $relativePath, string $prefix = ""): string
{
    if ($relativePath === "") {
        return "";
    }
    return $prefix . implode("/", array_map("rawurlencode", explode("/", $relativePath)));
}
