<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>أثر المدينة | مرحباً بك</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet" href="style.css">

</head>

<body class="welcome-page">

    <div class="welcome-overlay"></div>

    <div class="welcome-content">

        <div class="welcome-logo">
            أثَر المدينة
        </div>

        <div class="welcome-en">
            ATHAR AL-MADINA
        </div>

        <h1>
            مرحباً بك في أثر المدينة
        </h1>

        <p>
            اكتشف تاريخ المدينة المنورة ومعالمها
            الدينية والتاريخية والثقافية بطريقة أذكى.
        </p>

        <div class="welcome-buttons">

            <a href="login.php" class="welcome-login">
                تسجيل الدخول
            </a>

            <a href="register.php" class="welcome-register">
                إنشاء حساب
            </a>

        </div>

        <a href="home.php" class="guest-link">
            الدخول كزائر
        </a>

    </div>

<?php include __DIR__ . "/translate_widget.php"; ?>
</body>

</html>