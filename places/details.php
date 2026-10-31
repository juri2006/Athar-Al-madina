<?php
/* صفحة تفاصيل المعالم */

include "../database/db.php";
require_once "../helpers.php";

$id = intval($_GET["ID"] ?? $_GET["id"] ?? 0);

$sql = "SELECT * FROM madinah_landmarks WHERE ID = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$place = mysqli_fetch_assoc($result);

if (!$place) {
    die("المعلم غير موجود.");
}

$imgSrc = image_url(resolve_image($place["IMAGE_URL"] ?? "", dirname(__DIR__)), "../");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($place["NAME"]); ?>
        | أثر المدينة
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../style.css">

    <style>

        body {
            margin: 0;
            font-family: 'Cairo', Arial, sans-serif;
        }

        .details-page {

            min-height: 100vh;

            padding: 60px 7%;

            background: #f8f6f0;

        }

        .details-container {

            max-width: 1000px;

            margin: auto;

            background: white;

            border-radius: 20px;

            overflow: hidden;

            box-shadow: 0 10px 30px rgba(0,0,0,.08);

        }

        .details-image {

            width: 100%;

            height: 400px;

            object-fit: cover;

            display: block;

        }

        .details-content {

            padding: 35px;

        }

        .details-category {

            color: #b99652;

            font-size: 13px;

            font-weight: 700;

        }

        .details-content h1 {

            color: #174e45;

            font-size: 32px;

            margin: 8px 0 15px;

        }

        .details-location {

            color: #55736c;

            font-size: 13px;

            margin-bottom: 25px;

        }

        .details-description {

            color: #687772;

            line-height: 2;

            font-size: 14px;

            margin-bottom: 20px;

        }

        /* أزرار العودة */

        .navigation-buttons {

            display: flex;

            justify-content: center;

            gap: 12px;

            flex-wrap: wrap;

            margin-top: 35px;

            padding-top: 25px;

            border-top: 1px solid #eee;

        }

        .back-button {

            display: inline-block;

            text-decoration: none;

            padding: 11px 22px;

            border-radius: 25px;

            font-size: 12px;

            font-weight: 600;

            transition: 0.3s;

        }

        /* العودة للرئيسية */

        .home-button {

            background: #103f36;

            color: white;

        }

        .home-button:hover {

            background: #174e45;

        }

        /* العودة للمعالم */

        .places-button {

            background: #b99652;

            color: white;

        }

        .places-button:hover {

            background: #a48142;

        }

        /* العودة لمنظم الزيارات */

        .planner-button {

            background: #55736c;

            color: white;

        }

        .planner-button:hover {

            background: #405b55;

        }

        @media(max-width:600px) {

            .details-page {

                padding: 25px 5%;

            }

            .details-image {

                height: 250px;

            }

            .details-content {

                padding: 22px;

            }

            .details-content h1 {

                font-size: 25px;

            }

            .navigation-buttons {

                flex-direction: column;

            }

            .back-button {

                width: 100%;

                box-sizing: border-box;

                text-align: center;

            }

        }

    </style>

</head>

<body>

<div class="details-page">

    <div class="details-container">

        <?php if ($imgSrc !== "") { ?>

            <img
                src="<?php echo htmlspecialchars($imgSrc); ?>"
                class="details-image"
                alt="<?php echo htmlspecialchars($place["NAME"]); ?>"
            >

        <?php } ?>

        <div class="details-content">

            <!-- نوع المعلم -->

            <span class="details-category">

                <?php echo htmlspecialchars($place["CATEGORY"]); ?>

            </span>


            <!-- اسم المعلم -->

            <h1>

                <?php echo htmlspecialchars($place["NAME"]); ?>

            </h1>


            <!-- الموقع -->

            <?php if (!empty($place["LOCATION"])) { ?>

                <div class="details-location">

                    📍
                    <?php echo htmlspecialchars($place["LOCATION"]); ?>

                </div>

            <?php } ?>


            <!-- وصف المعلم -->

            <p class="details-description">

                <?php echo nl2br(htmlspecialchars($place["DESCRIPTION"])); ?>

            </p>


            <!-- أزرار التنقل -->

            <div class="navigation-buttons">

                <!-- الرئيسية -->

                <a
                    href="../home.php"
                    class="back-button home-button"
                >
                    🏠 العودة إلى القائمة الرئيسية
                </a>


                <!-- صفحة المعالم -->

                <a
                    href="../home.php#places"
                    class="back-button places-button"
                >
                    🏛️ العودة إلى المعالم
                </a>


                <!-- منظم الزيارات -->

                <a
                    href="../visit_planner.php"
                    class="back-button planner-button"
                >
                    🗺️ العودة إلى منظم الزيارات
                </a>

            </div>

        </div>

    </div>

</div>

<?php include __DIR__ . "/../translate_widget.php"; ?>
</body>

</html>