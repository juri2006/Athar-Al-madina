<?php
/* المتاحف والثقافة */

include "../database/db.php";
require_once "../lang.php";
require_once "../helpers.php";

/*
 * سبب المشكلة القديمة: التصنيف في قاعدة البيانات مكتوب "متاحف و ثقافة " (بمسافات)
 * بينما الاستعلام كان يبحث عن "متاحف وثقافة" بالضبط، فلا تُرجع أي نتيجة.
 * الحل: نتجاهل المسافات ونقبل أي تصنيف يبدأ بكلمة "متاحف"
 * (فيشمل أيضًا المعالم التي تُضاف من صفحة إضافة معلم تحت تصنيف "متاحف").
 */
$sql = "SELECT * FROM madinah_landmarks
        WHERE REPLACE(TRIM(CATEGORY), ' ', '') LIKE 'متاحف%'
        ORDER BY ID";

$result = mysqli_query($conn, $sql);

if (!$result) {
    error_log("museums.php query error: " . mysqli_error($conn));
}

$root = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="<?= $LANG ?>" dir="<?= $LANG_DIR ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars(t('mu_title')) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="../style.css">
    <?= lang_switcher_css() ?>

    <style>
        .place-card { display: block; text-decoration: none; color: inherit; }
        .place-card:hover { transform: translateY(-3px); transition: .2s; }
        .place-image-empty { background: #e4ece8; font-size: 48px; }
        .place-content p {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .museums-empty { grid-column: 1 / -1; text-align: center; color: #718078; padding: 30px 0; }
        .museums-lang { display: flex; justify-content: flex-end; margin-bottom: 12px; }
    </style>
</head>

<body>

<section class="section">

    <div class="museums-lang">
        <?= lang_switcher("on-light") ?>
    </div>

    <div class="section-heading">

        <div>
            <span><?= htmlspecialchars(t('mu_kicker')) ?></span>
            <h2><?= htmlspecialchars(t('mu_heading')) ?></h2>
        </div>

        <div class="line"></div>

    </div>

    <div class="places-grid">

        <?php
        $count = 0;
        while ($result && ($place = mysqli_fetch_assoc($result))):
            $count++;
            $img = image_url(resolve_image($place["IMAGE_URL"], $root), "../");
            $hasMap = preg_match('#^https?://#i', (string)$place["MAPS_URL"]) && strlen($place["MAPS_URL"]) > 12;
        ?>

            <a href="details.php?ID=<?= (int)$place["ID"] ?>" class="place-card">

                <?php if ($img !== ""): ?>
                    <div class="place-image"
                         style="background-image: url('<?= htmlspecialchars($img, ENT_QUOTES) ?>');"></div>
                <?php else: ?>
                    <div class="place-image place-image-empty"><span>🏛️</span></div>
                <?php endif; ?>

                <div class="place-content">

                    <h3><?= htmlspecialchars(trim($place["NAME"])) ?></h3>

                    <p><?= htmlspecialchars($place["DESCRIPTION"]) ?></p>

                    <?php if ($hasMap): ?>
                        <small>📍 <?= htmlspecialchars(t('mu_view_map')) ?></small>
                    <?php endif; ?>

                </div>

            </a>

        <?php endwhile; ?>

        <?php if ($count === 0): ?>
            <p class="museums-empty"><?= htmlspecialchars(t('mu_empty')) ?></p>
        <?php endif; ?>

    </div>

    <a href="../home.php"
       class="back-button"
       style="display:inline-block; margin-top:30px; background:#103f36; color:white; padding:10px 25px; border-radius:25px; text-decoration:none;">
        <?= $LANG_ARROW === "←" ? "→" : "←" ?> <?= htmlspecialchars(t('back_home')) ?>
    </a>

</section>

<?php include __DIR__ . "/../translate_widget.php"; ?>
</body>

</html>
