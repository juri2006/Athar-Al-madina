<?php
session_start();
require_once "database/db.php";
require_once __DIR__ . "/lang.php";
require_once __DIR__ . "/helpers.php";

$userId = $_SESSION['user_id'] ?? null;
$trips = [];
$landmarks = [];

if ($userId) {
    $stmt = $conn->prepare(
        "SELECT id, trip_name, start_location, total_duration
         FROM trips WHERE user_id = ? ORDER BY id DESC"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $trips = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// المسارات الأكثر انتشارًا (من العرض popular_routes الموجود في city.sql)
$popularRoutes = [];
try {
    $pr = $conn->query(
        "SELECT signature, times_used FROM popular_routes
         WHERE signature LIKE '%-%'
         ORDER BY times_used DESC, signature
         LIMIT 5"
    );
    if ($pr) {
        while ($r = $pr->fetch_assoc()) {
            $popularRoutes[] = $r;
        }
    }
} catch (Throwable $ex) {
    // إن لم يكن العرض موجودًا يظهر القسم فارغًا بدل أن تتعطل الصفحة
}

$res = $conn->query(
    "SELECT ID, NAME, CATEGORY, DESCRIPTION, IMAGE_URL, LATITUDE, LONGITUDE
     FROM madinah_landmarks
     WHERE LATITUDE IS NOT NULL AND LONGITUDE IS NOT NULL
     ORDER BY NAME"
);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        // مسار صورة المعلم (يصحح الأسماء غير المطابقة لملفات مجلد images)
        $row['IMG'] = image_url(resolve_image($row['IMAGE_URL'], __DIR__));
        // مفتاح موحّد للتصنيف (يتجاهل المسافات الزائدة) + اسم التصنيف بلغة الواجهة
        $row['CAT_KEY'] = preg_replace('/\s+/u', '', trim((string)$row['CATEGORY']));
        $row['CAT_LABEL'] = cat_label($row['CATEGORY']);
        unset($row['IMAGE_URL'], $row['DESCRIPTION']);
        $landmarks[] = $row;
    }
}

// تحويل بصمة كل مسار (مثل 3-4-9) إلى أسماء المعالم بالترتيب
$namesById = [];
foreach ($landmarks as $l) {
    $namesById[(int)$l['ID']] = $l['NAME'];
}
foreach ($popularRoutes as $k => $r) {
    $ids = array_values(array_filter(
        array_map('intval', explode('-', $r['signature'])),
        fn($v) => $v > 0
    ));
    $names = [];
    foreach ($ids as $id) {
        if (isset($namesById[$id])) {
            $names[] = $namesById[$id];
        }
    }
    if (count($ids) < 2 || count($names) !== count($ids)) {
        unset($popularRoutes[$k]);
        continue;
    }
    $popularRoutes[$k]['ids'] = $ids;
    $popularRoutes[$k]['names'] = $names;
}
$popularRoutes = array_values($popularRoutes);

$current = $_SESSION['current_trip'] ?? null;

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?= e($LANG) ?>" dir="<?= e($LANG_DIR) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(t('pl_title')) ?></title>

<style>
/* الإعدادات العامة */
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f5f7f5;
    color: #26372f;
    font-family: Tahoma, Arial, sans-serif;
}

/* الشريط العلوي */
.top-nav {
    min-height: 66px;
    padding: 10px 5%;
    background: #073d35;
    display: flex;
    align-items: center;
    justify-content: flex-start;   /* يبدأ من جهة الشعار (اليمين في العربية) */
    gap: 30px;
    color: white;
}

.brand {
    display: flex;
    align-items: center;
    gap: 9px;
    color: white;
    text-decoration: none;
    flex-shrink: 0;
}

.brand-icon {
    background: #e7f0e9;
    color: #155b43;
    width: 42px;
    height: 42px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    font-size: 23px;
}

.brand strong {
    display: block;
    font-size: 18px;
}

.brand small {
    display: block;
    font-size: 9px;
    letter-spacing: 1px;
    margin-top: 3px;
    color: #c6d9d0;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
}

.nav-links a {
    color: #f3f7f4;
    text-decoration: none;
    font-size: 13px;
    padding: 10px 0;
    transition: color .2s;
}

.nav-links a:hover {
    color: #b5d9bf;
}

/* الصورة والمنطقة الرئيسية */
.hero {
    min-height: 350px;
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
    padding: 35px 8%;
    background:
        linear-gradient(
            90deg,
            rgba(6, 50, 42, .18),
            rgba(6, 50, 42, .72)
        ),
        url('images/خلفية.jpeg'),
        linear-gradient(135deg, #dbe8dd, #9fb9a7 55%, #e9eee7);
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    color: #fff;
    position: relative;
    border-radius: 0 0 4px 4px;
}

.hero-content {
    max-width: 570px;
    text-align: right;
    margin-right: 0;
    margin-left: auto;
}

.hero-label {
    font-size: 13px;
    color: #e0eee4;
}

.hero-content h1 {
    font-size: 43px;
    margin: 0 0 10px;
    color: #fff;
}

.hero-content h2 {
    font-size: 21px;
    line-height: 1.7;
    margin: 0 0 12px;
    color: #fff;
}

.hero-content p {
    font-size: 14px;
    line-height: 2;
    color: #f1f6f2;
}

.hero-button {
    display: inline-block;
    background: #0b5143;
    color: white;
    text-decoration: none;
    padding: 12px 23px;
    border-radius: 25px;
    margin-top: 12px;
    font-size: 14px;
    transition: background .2s;
}

.hero-button:hover {
    background: #26734f;
}

/* الحاوية */
.container {
    max-width: 1100px;
    margin: auto;
    padding: 25px 15px 45px;
}

/* العناوين */
.heading {
    font-size: 21px;
    color: #26372f;
    margin: 0 0 13px;
}

.muted {
    color: #718078;
    font-size: 14px;
}

/* بطاقات المسارات */
.modes {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-top: 10px;
}

.mode {
    background: #f0f7f1;
    border: 1px solid #e0ebe2;
    border-radius: 15px;
    padding: 24px 18px;
    text-align: center;
    cursor: pointer;
    transition: .2s ease;
    min-height: 170px;
}

.mode:hover {
    background: #e8f3e9;
    border-color: #a9cbb1;
    transform: translateY(-3px);
    box-shadow: 0 7px 20px rgba(25, 80, 51, .08);
}

.mode .ico {
    width: 58px;
    height: 58px;
    margin: 0 auto 12px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: #fff;
    color: #27734e;
    box-shadow: 0 2px 8px rgba(35, 85, 55, .07);
    font-size: 27px;
}

.mode h2 {
    color: #185a40;
    font-size: 19px;
    margin: 8px 0;
}

.mode p {
    color: #718078;
    font-size: 13px;
    line-height: 1.8;
    margin: 0;
}

/* الأقسام والبطاقات */
section {
    margin-top: 27px;
}

.panel {
    background: #fff;
    border: 1px solid #e5ebe6;
    border-radius: 14px;
    padding: 17px;
    box-shadow: 0 3px 14px rgba(24, 65, 43, .035);
}

.trip {
    display: flex;
    background: #fff;
    border: 1px solid #e5ebe6;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 10px;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    color: inherit;
    text-decoration: none;
    transition: .2s;
}

.trip:hover {
    border-color: #b6d4bd;
    background: #f7fbf7;
    transform: translateY(-2px);
}

.trip:last-child {
    margin-bottom: 0;
}

.trip strong {
    display: block;
    margin-bottom: 6px;
    color: #26372f;
}

/* المسارات الأكثر انتشارًا */
.pop-desc {
    margin: 0 0 12px;
    font-size: 13px;
}

.pop-rank {
    flex: 0 0 34px;
    height: 34px;
    border-radius: 50%;
    background: #e9f4eb;
    color: #216b48;
    display: grid;
    place-items: center;
    font-weight: 700;
}

.pop-info {
    flex: 1;
    min-width: 0;
}

.pop-info strong {
    line-height: 1.7;
}

.current {
    border: 1px dashed #a6c9ad;
    background: #f2f8f3;
}

.empty {
    text-align: center;
    padding: 23px 8px;
    color: #718078;
}

.empty .ico {
    font-size: 28px;
    margin-bottom: 8px;
}

/* الأزرار */
.btn {
    display: inline-block;
    border: 0;
    border-radius: 9px;
    background: #26734f;
    color: white;
    padding: 11px 17px;
    text-decoration: none;
    cursor: pointer;
    font: inherit;
    transition: background .2s;
}

.btn:hover {
    background: #185a40;
}

.btn.light {
    background: #e9f4eb;
    color: #216b48;
}

.btn.light:hover {
    background: #dceee0;
}

/* اختيار المعالم اليدوي */
#manual {
    display: none;
    margin-top: 16px;
}

.filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

input,
select {
    font: inherit;
    padding: 10px;
    border: 1px solid #dceee0;
    border-radius: 9px;
    background: white;
    color: #26372f;
    outline: none;
}

input:focus,
select:focus {
    border-color: #83b994;
    box-shadow: 0 0 0 3px rgba(131, 185, 148, .15);
}

.filters input {
    flex: 1;
    min-width: 180px;
}

.list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 14px;
}

/* بطاقة المعلم: مستطيل فيه صورة واسم المعلم */
.item {
    position: relative;
    display: flex;
    flex-direction: column;
    border: 2px solid #dceee0;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    overflow: hidden;
    transition: .2s;
}

.item:hover {
    border-color: #a9cbb1;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(25, 80, 51, .10);
}

.item.selected {
    border-color: #26734f;
    background: #f0f7f1;
    box-shadow: 0 0 0 3px rgba(38, 115, 79, .15);
}

.item .thumb {
    width: 100%;
    height: 125px;
    background: #e4ece8;
    display: grid;
    place-items: center;
    font-size: 38px;
    overflow: hidden;
}

.item .thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.item .info {
    padding: 10px 12px 12px;
}

.item .info strong {
    display: block;
    margin-bottom: 4px;
    color: #26372f;
    font-size: 14px;
    line-height: 1.6;
}

.item .info small {
    font-size: 12px;
}

/* مربع الاختيار فوق الصورة */
.item input {
    position: absolute;
    top: 8px;
    inset-inline-start: 8px;
    width: 22px;
    height: 22px;
    margin: 0;
    padding: 0;
    accent-color: #26734f;
    cursor: pointer;
    z-index: 2;
}

.list-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 20px 0;
}

.actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

button:disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* اتجاه من اليسار لليمين */
[dir="ltr"] .hero-content {
    text-align: left;
    margin-right: auto;
    margin-left: 0;
}

.nav-tools {
    flex: 1;                        /* يأخذ بقية الشريط */
    display: flex;
    align-items: center;
    justify-content: space-between; /* الروابط في البداية وأداة اللغة في النهاية */
    gap: 18px;
    flex-wrap: wrap;
}

/* الهواتف */
@media (max-width: 700px) {
    .top-nav {
        align-items: flex-start;
        flex-direction: column ;
    }

    .nav-links {
        gap: 14px;
    }

    .hero {
        min-height: 310px;
        padding: 35px 6%;
    }

    .hero-content h1 {
        font-size: 34px;
    }

    .hero-content h2 {
        font-size: 18px;
    }

    .modes {
        grid-template-columns: 1fr;
    }

    .list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .item .thumb {
        height: 100px;
    }

    .trip {
        align-items: flex-start;
    }
}

@media (max-width: 420px) {
    .nav-links {
        gap: 10px;
    }

    .nav-links a {
        font-size: 12px;
    }

    .hero-content h1 {
        font-size: 29px;
    }

    .hero-content h2 {
        font-size: 16px;
    }

    .trip {
        flex-direction: column;
    }
}
</style>
<?= lang_switcher_css() ?>
</head>

<body>

<!-- الشريط العلوي -->
<nav class="top-nav">
    <a href="visit_planner.php" class="brand">
        <span class="brand-icon">🕌</span>
        <span>
            <strong><?= e(t('app_name')) ?></strong>
            <small>ATHAR AL-MADINA</small>
        </span>
    </a>

    <div class="nav-tools">
        <div class="nav-links">
            <a href="home.php"><?= e(t('nav_home')) ?></a>
            <a href="home.php#places"><?= e(t('nav_places')) ?></a>
            <a href="#planner"><?= e(t('nav_planner')) ?></a>
            <a href="#popular"><?= e(t('nav_popular')) ?></a>
            <a href="#past"><?= e(t('nav_past')) ?></a>
        </div>

        <?= lang_switcher() ?>
    </div>
</nav>

<!-- الصورة الرئيسية -->
<header class="hero" id="top">
    <div class="hero-content">
        <span class="hero-label"><?= e(t('pl_hero_label')) ?></span>
        <h1><?= e(t('app_name')) ?></h1>
        <h2><?= e(t('pl_hero_h2')) ?></h2>
        <p>
            <?= e(t('pl_hero_p')) ?>
        </p>
        <a href="#planner" class="hero-button"><?= e(t('pl_hero_btn')) ?></a>
    </div>
</header>

<div class="container">

<!-- اختيار نوع المسار -->
<div class="modes" id="planner">

    <div class="mode" onclick="location.href='smart_planner.php'">
        <div class="ico">✨</div>
        <h2><?= e(t('pl_smart')) ?></h2>
        <p><?= e(t('pl_smart_d')) ?></p>
    </div>

    <div class="mode" onclick="toggleManual()">
        <div class="ico">🗺️</div>
        <h2><?= e(t('pl_manual')) ?></h2>
        <p><?= e(t('pl_manual_d')) ?></p>
    </div>

</div>

<!-- اختيار المعالم يدويًا -->
<div id="manual" class="panel">
    <h2 class="heading"><?= e(t('pl_choose')) ?></h2>

    <div class="filters">
        <input id="search" type="search" placeholder="<?= e(t('pl_search')) ?>">

        <select id="category">
            <option value=""><?= e(t('pl_all_cats')) ?></option>
            <?php
            // قائمة التصنيفات بدون تكرار (المفتاح الموحّد => الاسم المترجم)
            $cats = [];
            foreach ($landmarks as $lm) {
                if ($lm['CAT_KEY'] !== '') {
                    $cats[$lm['CAT_KEY']] = $lm['CAT_LABEL'];
                }
            }

            foreach ($cats as $key => $label):
            ?>
                <option value="<?=e($key)?>"><?=e($label)?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="landmarkList" class="list"></div>

    <div class="actions">
        <span id="count" class="muted"><?= e(t('pl_selected', 0)) ?></span>
        <button
            class="btn"
            id="makeRoute"
            disabled
            onclick="makeManual()"
        >
            <?= e(t('pl_make')) ?>
        </button>
    </div>
</div>

<!-- المسارات الأكثر انتشارًا -->
<section id="popular">
    <h2 class="heading"><?= e(t('nav_popular')) ?></h2>

    <div class="panel">

        <?php if (!$popularRoutes): ?>

            <div class="empty">
                <div class="ico">🔥</div>
                <strong><?= e(t('pl_popular_empty')) ?></strong>
                <p><?= e(t('pl_popular_empty_d')) ?></p>
            </div>

        <?php else: ?>

            <p class="muted pop-desc"><?= e(t('pl_popular_d')) ?></p>

            <?php foreach ($popularRoutes as $i => $r): ?>
                <form class="trip pop-route" method="POST" action="route_result.php">
                    <input type="hidden" name="action" value="start">
                    <input type="hidden" name="place_ids" value="<?= e(json_encode($r['ids'])) ?>">
                    <input type="hidden" name="route_type" value="manual">
                    <input type="hidden" name="start_lat" value="24.4672">
                    <input type="hidden" name="start_lng" value="39.6111">

                    <span class="pop-rank"><?= $i + 1 ?></span>

                    <span class="pop-info">
                        <strong><?= e(implode(' ' . $LANG_ARROW . ' ', $r['names'])) ?></strong>
                        <span class="muted">
                            <?= e(t('pl_places_count', count($r['ids']))) ?>
                            ·
                            <?= e(t('pl_popular_used', (int)$r['times_used'])) ?>
                        </span>
                    </span>

                    <button type="submit" class="btn light">
                        <?= e(t('pl_popular_start')) ?> <?= $LANG_ARROW ?>
                    </button>
                </form>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</section>

<!-- المسارات السابقة -->
<section id="past">
    <h2 class="heading"><?= e(t('nav_past')) ?></h2>

    <div class="panel">

        <?php if (!$userId): ?>

            <div class="empty">
                <div class="ico">🔐</div>
                <strong><?= e(t('pl_login_req')) ?></strong>
                <p><?= e(t('pl_login_req_d')) ?></p>
            </div>

        <?php elseif (!$trips): ?>

            <div class="empty">
                <div class="ico">🧭</div>
                <strong><?= e(t('pl_no_trips')) ?></strong>
                <p><?= e(t('pl_no_trips_d')) ?></p>
            </div>

        <?php else: ?>

            <?php foreach ($trips as $trip): ?>
                <a
                    class="trip"
                    href="route_result.php?trip_id=<?=(int)$trip['id']?>"
                >
                    <span>
                        <strong>
                            <?=e($trip['trip_name'] ?: t('pl_unnamed'))?>
                        </strong>
                        <span class="muted">
                            <?=e(t('pl_start_point'))?>
                            <?=e($trip['start_location'] ?: t('pl_undefined'))?>
                        </span>
                    </span>

                    <span class="btn light"><?=e(t('pl_view_route'))?> <?=$LANG_ARROW?></span>
                </a>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</section>

<!-- المسار الحالي -->
<section>
    <h2 class="heading"><?= e(t('pl_current')) ?></h2>

    <div class="panel current">

        <?php if (!$current): ?>

            <div class="empty">
                <div class="ico">📍</div>
                <strong><?= e(t('pl_no_current')) ?></strong>
                <p><?= e(t('pl_no_current_d')) ?></p>
            </div>

        <?php else: ?>

            <a class="trip" href="route_result.php?current=1">
                <span>
                    <strong>
                        <?=e($current['trip_name'] ?? t('pl_current'))?>
                    </strong>

                    <span class="muted">
                        <?=e(t('pl_places_count', count($current['place_ids'] ?? [])))?>
                    </span>
                </span>

                <span class="btn"><?=e(t('pl_continue'))?> <?=$LANG_ARROW?></span>
            </a>

        <?php endif; ?>

    </div>
</section>

</div>

<script>
const landmarks = <?=json_encode(
    $landmarks,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
)?>;

const selected = new Set();

/* نصوص الواجهة بلغة المستخدم */
const I18N = <?=json_encode([
    'selected'   => t('pl_selected', 999999),
    'noneFound'  => t('pl_none_found'),
    'defaultName'=> t('pl_default_name'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;

/* إظهار أو إخفاء اختيار المعالم */
function toggleManual() {
    const el = document.getElementById('manual');

    el.style.display =
        el.style.display === 'block' ? 'none' : 'block';

    if (el.style.display === 'block') {
        render();
        el.scrollIntoView({ behavior: 'smooth' });
    }
}

/* عرض المعالم كبطاقات: صورة + اسم المعلم */
function render() {
    const q = document
        .getElementById('search')
        .value.trim()
        .toLowerCase();

    const cat = document.getElementById('category').value;
    const list = document.getElementById('landmarkList');

    list.replaceChildren();

    const shown = landmarks.filter(x =>
        String(x.NAME || '').toLowerCase().includes(q) &&
        (!cat || x.CAT_KEY === cat)
    );

    if (!shown.length) {
        const empty = document.createElement('p');
        empty.className = 'muted list-empty';
        empty.textContent = I18N.noneFound;
        list.append(empty);
    }

    shown.forEach(x => {
        const id = String(x.ID);
        const name = String(x.NAME || I18N.defaultName).trim();

        const label = document.createElement('label');
        label.className = 'item' + (selected.has(id) ? ' selected' : '');

        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.checked = selected.has(id);

        cb.onchange = () => {
            if (cb.checked) {
                selected.add(id);
            } else {
                selected.delete(id);
            }

            label.classList.toggle('selected', cb.checked);
            updateCount();
        };

        /* صورة المعلم (وإن لم توجد أو تعذر تحميلها تظهر أيقونة بدلها) */
        const thumb = document.createElement('div');
        thumb.className = 'thumb';

        if (x.IMG) {
            const img = document.createElement('img');
            img.src = x.IMG;
            img.alt = name;
            img.loading = 'lazy';
            img.onerror = () => {
                img.remove();
                thumb.textContent = '🕌';
            };
            thumb.append(img);
        } else {
            thumb.textContent = '🕌';
        }

        const info = document.createElement('div');
        info.className = 'info';

        const strong = document.createElement('strong');
        const small = document.createElement('small');

        strong.textContent = name;
        small.className = 'muted';
        small.textContent = x.CAT_LABEL || '';

        info.append(strong, small);
        label.append(cb, thumb, info);
        list.append(label);
    });

    updateCount();
}

/* تحديث عدد المعالم المختارة */
function updateCount() {
    document.getElementById('count').textContent =
        I18N.selected.replace('999999', selected.size);

    document.getElementById('makeRoute').disabled =
        selected.size === 0;
}

/* إرسال المسار اليدوي إلى صفحة النتيجة */
function makeManual() {
    if (!selected.size) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'route_result.php';

    const data = {
        action: 'start',
        place_ids: JSON.stringify([...selected]),
        route_type: 'manual',
        start_lat: '24.4672',
        start_lng: '39.6111'
    };

    for (const [name, value] of Object.entries(data)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.append(input);
    }

    document.body.append(form);
    form.submit();
}

/* البحث والتصفية */
document.getElementById('search')
    .addEventListener('input', render);

document.getElementById('category')
    .addEventListener('change', render);
</script>

<?php include __DIR__ . "/translate_widget.php"; ?>
</body>
</html>