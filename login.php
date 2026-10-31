<?php
/*تسجيل الدخول */
session_start();

include("database/db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {

        $error = "الرجاء إدخال اسم المستخدم وكلمة المرور.";

    } else {

        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "ss",
            $username,
            $username
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["name"] = $user["name"];

                header("Location: home.php");

                exit();

            } else {

                $error = "كلمة المرور غير صحيحة.";

            }

        } else {

            $error = "اسم المستخدم أو البريد الإلكتروني غير موجود.";

        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>تسجيل الدخول | أثر المدينة</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth.css">

</head>

<body class="auth-page">

    <div class="auth-card">

        <a href="index.php" class="auth-logo">
            أثَر المدينة
        </a>

        <h1>تسجيل الدخول</h1>

        <p class="auth-subtitle">
            أهلاً بعودتك، سجّل دخولك للمتابعة
        </p>

        <?php if ($error != ""): ?>

            <div class="error-message">
                <?php echo $error; ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <label>
                اسم المستخدم أو البريد الإلكتروني
            </label>

            <input
                type="text"
                name="username"
                placeholder="أدخل اسم المستخدم أو البريد"
                required
            >

            <label>
                كلمة المرور
            </label>

            <input
                type="password"
                name="password"
                placeholder="أدخل كلمة المرور"
                required
            >

            <button type="submit">
                تسجيل الدخول
            </button>

        </form>

        <p class="switch-auth">
            ليس لديك حساب؟
            <a href="register.php">
                إنشاء حساب
            </a>
        </p>

        <a href="index.php" class="back-home">
            ← العودة
        </a>

    </div>

<?php include __DIR__ . "/translate_widget.php"; ?>
</body>

</html>