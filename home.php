<?php
session_start();
require_once __DIR__ . "/lang.php";

/* ===== [جديد] جلب المعالم من قاعدة البيانات للخريطة ===== */
include_once __DIR__ . "/database/db.php";
include_once __DIR__ . "/helpers.php";

$landmarks = [];
$res = mysqli_query($conn, "SELECT ID, NAME, CATEGORY, DESCRIPTION, IMAGE_URL, MAPS_URL, latitude, longitude
                            FROM madinah_landmarks
                            WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row["IMAGE_URL"] = image_url(resolve_image($row["IMAGE_URL"], __DIR__));
        $landmarks[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($LANG) ?>" dir="<?= htmlspecialchars($LANG_DIR) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>أثر المدينة | اكتشف المدينة المنورة</title>

    <!-- الخط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- ملف CSS -->
    <link rel="stylesheet" href="style.css">
    <?= lang_switcher_css() ?>
    <style>
        .navbar .lang-switcher { flex-wrap: nowrap; }
        .navbar .lang-switcher a { padding: 3px 8px; font-size: 11px; white-space: nowrap; }
    </style>

    <!-- [جديد] Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        #home-map { height: 450px; border-radius: 16px; z-index: 0; }
        .popup { width: 230px; text-align: right; direction: rtl; }
        .popup img { width: 100%; height: 130px; object-fit: cover; border-radius: 9px; margin-bottom: 8px; }
        .popup h3 { margin: 4px 0; color: #194d44; font-size: 16px; }
        .popup .pcat { color: #a48343; font-size: 11px; margin-bottom: 6px; }
        .popup p { color: #687773; font-size: 11px; line-height: 1.8; margin: 5px 0 10px; }
        .popup a.pbtn { display: inline-block; background: #103f36; color: #fff; text-decoration: none;
                        padding: 7px 11px; border-radius: 20px; font-size: 10px; }
    </style>
</head>

<body>

<!-- ================= HEADER ================= -->

<header class="navbar">

    <div class="logo">
        <img src="images/logo.jpeg" alt="أثر المدينة" class="logo-image">

        <div class="logo-text">
        <div class="logo-ar">أثَر المدينة</div>
        <div class="logo-en">ATHAR AL-MADINA</div>
</div>
    </div>

   <nav class="nav-links">

        <a href="#" class="active"><?= htmlspecialchars(t('nav_home')) ?></a>

        <a href="#places"><?= htmlspecialchars(t('nav_places')) ?></a>

        <a href="visit_planner.php"><?= htmlspecialchars(t('nav_planner')) ?></a>

        <a href="map.php"><?= htmlspecialchars(t('nav_map')) ?></a>

        <a href="#about"><?= htmlspecialchars(t('nav_about')) ?></a>

        <a href="add_place.php"><?= htmlspecialchars(t('nav_add')) ?></a>
    </nav>

    <!-- أزرار تبديل اللغة -->
    <?= lang_switcher() ?>

    <div class="account">

    <?php if (isset($_SESSION["user_id"])): ?>

        <span>
            <?= htmlspecialchars(t('acc_welcome')) ?> <?php echo htmlspecialchars($_SESSION["name"]); ?>
        </span>

        <a href="logout.php" class="account-btn">
            <?= htmlspecialchars(t('acc_logout')) ?>
        </a>

    <?php else: ?>

        <a href="login.php">
            <?= htmlspecialchars(t('acc_login')) ?>
        </a>

        <span class="user">●</span>

        <a href="register.php" class="account-btn">
            <?= htmlspecialchars(t('acc_register')) ?>
        </a>

    <?php endif; ?>

</div>

</header>


<!-- ================= HERO ================= -->

<section class="hero">

    <div class="hero-overlay"></div>

    <div class="hero-content">

        <span class="small-title">
            دليلك إلى تاريخ المدينة المنورة
        </span>

        <h1>
            أثَر المدينة
        </h1>

        <h2>
            اكتشف تاريخ المدينة المنورة .. بطريقة أذكى
        </h2>

        <p>
            منصة تجمع لك أهم المعالم التاريخية والدينية والثقافية
            في المدينة المنورة، مع إمكانية تنظيم زيارتك
            واستكشافها بسهولة.
        </p>

        <div class="hero-buttons">

            <a href="#places" class="main-btn">
                استكشف المعالم ✧
            </a>
            
            <a href="visit_planner.php" class="second-btn" >
             منظم الزيارة
             </a>

        </div>

    </div>


    <!-- SEARCH -->

    <div class="search-box">

        <button class="search-icon">
            ⌕
        </button>

        <input
            type="text"
            placeholder="ابحث عن معلم .."
        >

        <div class="category">

            <span>
                جميع التصنيفات
            </span>

            <span>
                ⌄
            </span>

        </div>

    </div>

</section>


<!-- ================= CATEGORIES ================= -->

<section class="section categories">

    <div class="section-heading">

        <div>

            <span>
                اكتشف أكثر
            </span>

            <h2>
                تصفح حسب التصنيف
            </h2>

        </div>

        <div class="line"></div>

    </div>


    <div class="category-grid">


        <!-- دينية -->

        <a href="places/religious.php" class="category-card religious">

            <div class="category-icon">
                🕌
            </div>

            <div>

                <h3>
                    معالم دينية
                </h3>

                <p>
                    أماكن مباركة وذات قيمة عظيمة
                </p>

            </div>

        </a>


        <!-- تاريخية -->

        <a href="places/historical.php" class="category-card historical">

            <div class="category-icon">
                🏰
            </div>

            <div>

                <h3>
                    معالم تاريخية
                </h3>

                <p>
                    آثار تحكي قصص الماضي
                </p>

            </div>

        </a>


        <!-- متاحف -->

        <a href="places/museums.php" class="category-card museums">

            <div class="category-icon">
                🏛
            </div>

            <div>

                <h3>
                    متاحف وثقافة
                </h3>

                <p>
                    تعرّف على تاريخ وثقافة المدينة
                </p>

            </div>

        </a>


        <!-- طبيعة -->

        <a href="places/nature.php" class="category-card nature">

            <div class="category-icon">
                🌿
            </div>

            <div>

                <h3>
                    أماكن طبيعية
                </h3>

                <p>
                    اكتشف جمال الطبيعة في المدينة
                </p>

            </div>

        </a>

    </div>

</section>


<!-- ================= PLACES ================= -->

<section class="section places" id="places">

    <div class="section-heading">

        <div>

            <span>
                أماكن لا تفوّت زيارتها
            </span>

            <h2>
                أبرز المعالم
            </h2>

        </div>

        <a href="map.php" class="view-all">
            عرض الكل على الخريطة ←
        </a>

    </div>


    <div class="places-grid">


        <!-- المسجد النبوي -->
       <a href="places/details.php?id=1" class="place-card">
            <div class="place-image mosque">
                <span>🕌</span>
            </div>
            <div class="place-content">
                <h3>المسجد النبوي</h3>
                <p>أشرف بقاع الأرض وثاني الحرمين الشريفين ومثوى النبي صلى الله عليه وسلم.</p>
                <small>📍 موقع مركزي في المدينة المنورة</small>
            </div>
        </a>


        <!-- مسجد قباء -->
        <a href="places/details.php?id=3" class="place-card">
            <div class="place-image quba">
                <span>🕌</span>
            </div>
            <div class="place-content">
                <h3>مسجد قباء</h3>
                <p>أول مسجد أسس على التقوى في الإسلام، وفضل الصلاة فيه كعمرة.</p>
                <small>📍 جنوب المدينة المنورة</small>
            </div>
        </a>


        <!-- جبل أحد -->
        <a href="places/details.php?id=2" class="place-card">
            <div class="place-image mountain">
                <span>⛰️</span>
            </div>
            <div class="place-content">
                <h3>جبل أحد</h3>
                <p>الجبل الذي أحبه النبي صلى الله عليه وسلم ويشهد على تاريخ غزوة أحد العظيمة.</p>
                <small>📍 شمال المدينة المنورة</small>
            </div>
        </a>


        <!-- مقبرة البقيع -->
        <a href="places/details.php?id=4" class="place-card">
            <div class="place-image museum">
                <span>🏛</span>
            </div>
            <div class="place-content">
                <h3>مقبرة البقيع</h3>
                <p>مضجع آل بيت النبي صلى الله عليه وسلم وجمع غفير من الصحابة الكرام.</p>
                <small>📍 بجوار المسجد النبوي الشريف</small>
            </div>
        </a>

    </div>
</section>

<!-- ================= PLANNER ================= -->

<section class="planner" id="planner">

    <div class="planner-content">

        <div class="planner-icon">
            ⌖
        </div>

        <div class="planner-text">

            <h2>
                منظم الزيارة
            </h2>

            <p>
                اختر الأماكن التي تود زيارتها،
                وسنساعدك في إنشاء مسارك السياحي المثالي
                مع عرضه على الخريطة.
            </p>

        </div>

        <a href="visit_planner.php" >
              منظم الزيارة
        </a>

    </div>

</section>


<!-- ================= MAP ================= -->

<section class="section map-section" id="map">

    <div class="section-heading">

        <div>

            <span>
                استكشف مواقع المعالم
            </span>

            <h2>
                الخريطة
            </h2>

        </div>

        <div class="line"></div>

    </div>

    <!-- [جديد] الخريطة الحقيقية بدل الخريطة الوهمية -->
    <div id="home-map"></div>
    <a href="map.php" class="view-all" style="display:inline-block; margin-top:12px;">
        افتح الخريطة الكاملة ←
    </a>

</section>


<!-- ================= FOOTER ================= -->

<footer class="footer" id="about">


    <div class="footer-column brand-column">

        <div class="footer-logo">
            أثَر المدينة
        </div>

        <p>
            المدينة .. تاريخ يُروى.
        </p>

    </div>


    <div class="footer-column">

        <h4>
            معلومات
        </h4>

        <a href="#">
            عن المشروع
        </a>

        <a href="#">
            تواصل معنا
        </a>

        <a href="#">
            سياسة الخصوصية
        </a>

    </div>

<div class="footer-column">

        <h4>
            روابط سريعة
        </h4>

        <a href="#">
            الرئيسية
        </a>

        <a href="#places">
            المعالم
        </a>

        <a href="#planner">
            منظم الزيارة
        </a>

        <a href="map.php">
            الخريطة
        </a>

        <a href="add_place.php" class="add-place-link">
        + إضافة معلم
    </a>

    </div>
    


    <div class="footer-column">

        

        <div class="social">

            

        </div>

        <small>
            جميع الحقوق محفوظة ©️ 2025 - أثر المدينة
        </small>

    </div>


</footer>

<!-- [جديد] Leaflet JS + كود الخريطة -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const landmarks = <?= json_encode($landmarks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const map = L.map('home-map', { scrollWheelZoom: false }).setView([24.4672, 39.6111], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

function esc(t) {
    return String(t ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

const markers = [];
landmarks.forEach(p => {
    const lat = parseFloat(p.latitude), lng = parseFloat(p.longitude);
    if (isNaN(lat) || isNaN(lng)) return;

    const img = p.IMAGE_URL
        ? `<img src="${esc(p.IMAGE_URL)}" alt="${esc(p.NAME)}" onerror="this.style.display='none'">` : '';
    const link = p.MAPS_URL
        ? `<a class="pbtn" href="${esc(p.MAPS_URL)}" target="_blank" rel="noopener">📍 فتح Google Maps</a>` : '';

    const m = L.marker([lat, lng]).addTo(map).bindPopup(`
        <div class="popup">
            ${img}
            <h3>${esc(p.NAME)}</h3>
            <div class="pcat">${esc(p.CATEGORY)}</div>
            <p>${esc(p.DESCRIPTION)}</p>
            ${link}
        </div>`);
    markers.push(m);
});

if (markers.length) map.fitBounds(L.featureGroup(markers).getBounds().pad(0.15));
</script>

<?php include __DIR__ . "/translate_widget.php"; ?>
</body>
</html>
