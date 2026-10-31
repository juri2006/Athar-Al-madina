<?php
/* إنشاء الحساب */

session_start();

include("database/db.php");

$error = "";

// التأكد من الاتصال بقاعدة البيانات
if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات.");
}

mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // استلام البيانات
    $name = trim($_POST["name"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["teiephone_number"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // التحقق من الحقول المطلوبة
    if (
        empty($name) ||
        empty($username) ||
        empty($email) ||
        empty($phone) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        $error = "الرجاء تعبئة جميع الحقول.";

    // التحقق من البريد الإلكتروني
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "البريد الإلكتروني غير صحيح.";

    // التحقق من رقم الهاتف
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {

        $error = "رقم الهاتف غير صحيح.";

    // التحقق من تطابق كلمات المرور
    } elseif ($password !== $confirm_password) {

        $error = "كلمتا المرور غير متطابقتين.";

    // التحقق من طول كلمة المرور
    } elseif (strlen($password) < 6) {

        $error = "كلمة المرور يجب أن تكون 6 أحرف على الأقل.";

    } else {

        /*
         * التحقق من عدم تكرار:
         * اسم المستخدم
         * البريد الإلكتروني
         * رقم الهاتف
         */

        $check = "SELECT user_id
                  FROM users
                  WHERE username = ?
                     OR email = ?
                     OR phone = ?";

        $stmt = mysqli_prepare($conn, $check);

        if (!$stmt) {

            $error = "حدث خطأ في الاتصال بقاعدة البيانات.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $username,
                $email,
                $phone
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {

                $error = "اسم المستخدم أو البريد الإلكتروني أو رقم الهاتف مستخدم مسبقاً.";

                mysqli_stmt_close($stmt);

            } else {

                mysqli_stmt_close($stmt);

                // تشفير كلمة المرور
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                /*
                 * إضافة المستخدم إلى قاعدة البيانات
                 *
                 * ملاحظة:
                 * اسم عمود الهاتف يحتوي على مسافة،
                 * لذلك نضعه بين ` `
                 */

                $sql = "INSERT INTO users
                        (
                            name,
                            username,
                            email,
                            phone,
                            password
                        )
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($conn, $sql);

                if (!$stmt) {

                    $error = "حدث خطأ أثناء تجهيز عملية إنشاء الحساب.";

                } else {

                    /*
                     * ملاحظة:
                     * لا يُفضل تخزين confirm_password في قاعدة البيانات.
                     * لكن بما أن العمود موجود عندك، سنخزنه هنا
                     * حتى يعمل التصميم الحالي.
                     */

                    mysqli_stmt_bind_param(
                        $stmt,
                        "sssss",
                        $name,
                        $username,
                        $email,
                        $phone,
                        $hashed_password
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        mysqli_stmt_close($stmt);

                        // الانتقال إلى تسجيل الدخول
                        header("Location: login.php");

                        exit();

                    } else {

                        $error = "حدث خطأ أثناء إنشاء الحساب.";

                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        إنشاء حساب | أثر المدينة
    </title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="style.css"
    >

    <link
        rel="stylesheet"
        href="auth.css"
    >

</head>

<body class="auth-page">

    <div class="auth-card">

        <a
            href="index.php"
            class="auth-logo"
        >
            أثَر المدينة
        </a>

        <h1>
            إنشاء حساب
        </h1>

        <p class="auth-subtitle">
            أنشئ حسابك واستمتع باستكشاف المدينة المنورة
        </p>

        <?php if ($error != ""): ?>

            <div
                class="error-message"
            >
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <!-- الاسم -->
            <label>
                الاسم
            </label>

            <input
                type="text"
                name="name"
                placeholder="أدخل اسمك"
                value="<?php echo htmlspecialchars($_POST["name"] ?? ""); ?>"
                required
            >

            <!-- اسم المستخدم -->
            <label>
                اسم المستخدم
            </label>

            <input
                type="text"
                name="username"
                placeholder="أدخل اسم المستخدم"
                value="<?php echo htmlspecialchars($_POST["username"] ?? ""); ?>"
                required
            >

            <!-- البريد الإلكتروني -->
            <label>
                البريد الإلكتروني
            </label>

            <input
                type="email"
                name="email"
                placeholder="example@email.com"
                value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                required
            >

            <!-- رقم الهاتف -->
            <label>
                رقم الهاتف
            </label>

            <input
                type="tel"
                name="teiephone_number"
                placeholder="مثال: 0501234567"
                value="<?php echo htmlspecialchars($_POST["teiephone_number"] ?? ""); ?>"
                maxlength="20"
                required
            >

            <small>
                يجب إدخال رقم هاتف صحيح.
            </small>

            <!-- كلمة المرور -->
            <label>
                كلمة المرور
            </label>

            <input
                type="password"
                name="password"
                placeholder="أدخل كلمة المرور"
                required
            >

            <!-- تأكيد كلمة المرور -->
            <label>
                تأكيد كلمة المرور
            </label>

            <input
                type="password"
                name="confirm_password"
                placeholder="أعد إدخال كلمة المرور"
                required
            >

            <!-- زر إنشاء الحساب -->
            <button type="submit">
                إنشاء الحساب
            </button>

        </form>

        <p class="switch-auth">

            لديك حساب بالفعل؟

            <a href="login.php">
                تسجيل الدخول
            </a>

        </p>

        <a
            href="index.php"
            class="back-home"
        >
            ← العودة
        </a>

    </div>

<?php include __DIR__ . "/translate_widget.php"; ?>
</body>

</html>