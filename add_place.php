<?php
session_start();

require_once __DIR__ . "/database/db.php";
require_once __DIR__ . "/lang.php";

// التأكد من الاتصال بقاعدة البيانات
if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات.");
}

mysqli_set_charset($conn, "utf8mb4");

// إنشاء رمز حماية للنموذج
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "success";

// التصنيفات المسموح بها
$categories = [
    "معالم دينية",
    "معالم تاريخية",
    "متاحف",
    "أماكن تراثية",
    "أماكن زراعية",
    "معالم أخرى"
];

// القيم الافتراضية
$NAME = "";
$CATEGORY = "";
$DESCRIPTION = "";
$MAPS_URL = "";
$LATITUDE = "";
$LONGITUDE = "";

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // استلام البيانات
    $NAME = trim($_POST["NAME"] ?? "");
    $CATEGORY = trim($_POST["CATEGORY"] ?? "");
    $DESCRIPTION = trim($_POST["DESCRIPTION"] ?? "");
    $MAPS_URL = trim($_POST["MAPS_URL"] ?? "");
    $LATITUDE = trim($_POST["LATITUDE"] ?? "");
    $LONGITUDE = trim($_POST["LONGITUDE"] ?? "");

    $errors = [];
    $IMAGE_URL = "";
    $uploadedFile = "";

    // التحقق من رمز الحماية
    $csrf = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION["csrf_token"], $csrf)
    ) {
        $errors[] = t('ad_err_csrf');
    }

    // التحقق من الاسم
    if ($NAME === "" || mb_strlen($NAME) > 255) {
        $errors[] = t('ad_err_name');
    }

    // التحقق من التصنيف
    if (!in_array($CATEGORY, $categories, true)) {
        $errors[] = t('ad_err_cat');
    }

    // التحقق من الوصف
    if ($DESCRIPTION === "") {
        $errors[] = t('ad_err_desc');
    }

    // التحقق من رابط خرائط Google
    if ($MAPS_URL !== "") {
        if (
            !filter_var($MAPS_URL, FILTER_VALIDATE_URL) ||
            !in_array(
                strtolower(parse_url($MAPS_URL, PHP_URL_SCHEME) ?? ""),
                ["http", "https"],
                true
            )
        ) {
            $errors[] = t('ad_err_url');
        }
    }

    // التحقق من الإحداثيات
    $lat = null;
    $lng = null;

    if (($LATITUDE === "") !== ($LONGITUDE === "")) {
        $errors[] = t('ad_err_pair');
    } elseif ($LATITUDE !== "" && $LONGITUDE !== "") {

        if (
            !is_numeric($LATITUDE) ||
            !is_numeric($LONGITUDE)
        ) {
            $errors[] = t('ad_err_num');
        } else {
            $lat = (float)$LATITUDE;
            $lng = (float)$LONGITUDE;

            if ($lat < -90 || $lat > 90) {
                $errors[] = t('ad_err_lat');
            }

            if ($lng < -180 || $lng > 180) {
                $errors[] = t('ad_err_lng');
            }
        }
    }

    // التحقق من الصورة ورفعها
    if (isset($_FILES["IMAGE_URL"])) {

        $file = $_FILES["IMAGE_URL"];

        if ($file["error"] !== UPLOAD_ERR_NO_FILE) {

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $errors[] = t('ad_err_upload');
            } elseif ($file["size"] > 5 * 1024 * 1024) {
                $errors[] = t('ad_err_size');
            } else {

                // السماح بأنواع صور محددة فقط
                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file["tmp_name"]);

                if (!isset($allowedTypes[$mimeType])) {
                    $errors[] = t('ad_err_type');
                } elseif (getimagesize($file["tmp_name"]) === false) {
                    $errors[] = t('ad_err_img');
                } else {

                    $uploadDir = __DIR__ . "/uploads/";

                    if (
                        !is_dir($uploadDir) &&
                        !mkdir($uploadDir, 0755, true)
                    ) {
                        $errors[] = t('ad_err_dir');
                    } elseif (!is_writable($uploadDir)) {
                        $errors[] = t('ad_err_write');
                    } else {

                        $imageName = bin2hex(random_bytes(16))
                            . "." . $allowedTypes[$mimeType];

                        $destination = $uploadDir . $imageName;

                        if (
                            move_uploaded_file(
                                $file["tmp_name"],
                                $destination
                            )
                        ) {
                            $IMAGE_URL = "uploads/" . $imageName;
                            $uploadedFile = $destination;
                        } else {
                            $errors[] = t('ad_err_save_img');
                        }
                    }
                }
            }
        }
    }

    // حفظ البيانات في قاعدة البيانات إذا لم توجد أخطاء
    if (empty($errors)) {

        $sql = "INSERT INTO madinah_landmarks
                (
                    NAME,
                    CATEGORY,
                    DESCRIPTION,
                    MAPS_URL,
                    IMAGE_URL,
                    LATITUDE,
                    LONGITUDE
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                throw new Exception(mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $stmt,
                "sssssdd",
                $NAME,
                $CATEGORY,
                $DESCRIPTION,
                $MAPS_URL,
                $IMAGE_URL,
                $lat,
                $lng
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = t('ad_ok');
                $messageType = "success";

                // تفريغ الحقول بعد نجاح الإضافة
                $NAME = "";
                $CATEGORY = "";
                $DESCRIPTION = "";
                $MAPS_URL = "";
                $LATITUDE = "";
                $LONGITUDE = "";

                // إنشاء رمز جديد بعد الإرسال
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            } else {
                throw new Exception(mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);

        } catch (Throwable $ex) {

            // تسجيل تفاصيل الخطأ في سجل الخادم
            error_log($ex->getMessage());

            // حذف الصورة إذا فشلت إضافة البيانات
            if ($uploadedFile !== "" && file_exists($uploadedFile)) {
                unlink($uploadedFile);
            }

            $message = t('ad_err_db');
            $messageType = "error";
        }

    } else {
        $message = implode("\n", $errors);
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= e($LANG) ?>" dir="<?= e($LANG_DIR) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e(t('ad_title')) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Tahoma, sans-serif;
            background: #f5f7f7;
            padding: 30px 15px;
            margin: 0;
            color: #263838;
        }

        .container {
            max-width: 700px;
            width: 100%;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.07);
        }

        h1 {
            text-align: center;
            color: #174c4c;
            margin-bottom: 25px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
            line-height: 1.8;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 8px;
            font-weight: bold;
            color: #174c4c;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 13px;
            border: 1px solid #d8e0e0;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            background: #fff;
            transition: border-color 0.2s;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #174c4c;
            box-shadow: 0 0 0 3px rgba(23, 76, 76, 0.08);
        }

        textarea {
            height: 130px;
            resize: vertical;
        }

        .hint {
            font-size: 12px;
            color: #777;
            margin-top: 6px;
        }

        .coordinates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        button {
            width: 100%;
            margin-top: 28px;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background: #174c4c;
            color: white;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: #226565;
        }

        /* زر العودة للصفحة الرئيسية */
        .back-home {
            display: block;
            width: 100%;
            margin-top: 12px;
            padding: 15px;
            border: 2px solid #174c4c;
            border-radius: 8px;
            background: #fff;
            color: #174c4c;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .back-home:hover {
            background: #174c4c;
            color: #fff;
        }

        .lang-row {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }

        .message {
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            white-space: pre-line;
            line-height: 1.8;
        }

        .success {
            background: #e7f5ed;
            color: #176b42;
        }

        .error {
            background: #fdecec;
            color: #a12626;
        }

        @media (max-width: 500px) {
            .container {
                padding: 20px;
            }

            .coordinates {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
    <?= lang_switcher_css() ?>
</head>

<body>

<div class="container">

    <div class="lang-row">
        <?= lang_switcher("on-light") ?>
    </div>

    <h1><?= e(t('ad_h1')) ?></h1>

    <p class="subtitle">
        <?= e(t('ad_subtitle')) ?>
    </p>

    <?php if ($message !== ""): ?>
        <div class="message <?= e($messageType) ?>" role="alert">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action=""
          enctype="multipart/form-data">

        <input type="hidden"
               name="csrf_token"
               value="<?= e($_SESSION["csrf_token"]) ?>">

        <label for="NAME"><?= e(t('ad_name')) ?></label>
        <input
            type="text"
            id="NAME"
            name="NAME"
            maxlength="255"
            value="<?= e($NAME) ?>"
            placeholder="<?= e(t('ad_name_ph')) ?>"
            required
        >

        <label for="CATEGORY"><?= e(t('ad_category')) ?></label>
        <select id="CATEGORY" name="CATEGORY" required>
            <option value=""><?= e(t('ad_choose_cat')) ?></option>

            <?php foreach ($categories as $category): ?>
                <option
                    value="<?= e($category) ?>"
                    <?= $CATEGORY === $category ? "selected" : "" ?>
                >
                    <?= e(cat_label($category)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="DESCRIPTION"><?= e(t('ad_desc')) ?></label>
        <textarea
            id="DESCRIPTION"
            name="DESCRIPTION"
            placeholder="<?= e(t('ad_desc_ph')) ?>"
            required
        ><?= e($DESCRIPTION) ?></textarea>

        <label for="MAPS_URL"><?= e(t('ad_maps')) ?></label>
        <input
            type="url"
            id="MAPS_URL"
            name="MAPS_URL"
            value="<?= e($MAPS_URL) ?>"
            placeholder="https://maps.google.com/..."
        >

        <label for="IMAGE_URL"><?= e(t('ad_image')) ?></label>
        <input
            type="file"
            id="IMAGE_URL"
            name="IMAGE_URL"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        >
        <div class="hint">
            <?= e(t('ad_image_hint')) ?>
        </div>

        <div class="coordinates">

            <div>
                <label for="LATITUDE"><?= e(t('ad_lat')) ?></label>
                <input
                    type="number"
                    id="LATITUDE"
                    name="LATITUDE"
                    step="any"
                    min="-90"
                    max="90"
                    value="<?= e($LATITUDE) ?>"
                    placeholder="<?= e(t('ad_lat_ph')) ?>"
                >
            </div>

            <div>
                <label for="LONGITUDE"><?= e(t('ad_lng')) ?></label>
                <input
                    type="number"
                    id="LONGITUDE"
                    name="LONGITUDE"
                    step="any"
                    min="-180"
                    max="180"
                    value="<?= e($LONGITUDE) ?>"
                    placeholder="<?= e(t('ad_lng_ph')) ?>"
                >
            </div>

        </div>

        <button type="submit">
            <?= e(t('ad_submit')) ?>
        </button>

    </form>

    <!-- زر العودة للصفحة الرئيسية (رابط وليس زر إرسال) -->
    <a href="home.php" class="back-home">
        <?= e(t('back_home')) ?>
    </a>

</div>

<?php include __DIR__ . "/translate_widget.php"; ?>
</body>
</html>