-- ============================================================
-- قاعدة بيانات أثر المدينة (city) - نسخة مصححة
-- الترميز: UTF-8 (utf8mb4) | متوافقة مع MariaDB 10.4 / phpMyAdmin
-- عند الاستيراد في phpMyAdmin اختر الترميز: utf-8
-- تنبيه: هذا الملف يحذف الجداول الموجودة ويعيد إنشاءها. خذ نسخة احتياطية أولاً.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `city` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `city`;

DROP VIEW IF EXISTS `popular_routes`;
DROP VIEW IF EXISTS `route_signatures`;
DROP VIEW IF EXISTS `popular_landmarks`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `trip_places`;
DROP TABLE IF EXISTS `trips`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `madinah_landmarks`;

-- ------------------------------------------------------------
-- 1) المعالم
-- ------------------------------------------------------------
CREATE TABLE `madinah_landmarks` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(255) NOT NULL,
  `CATEGORY` varchar(50) DEFAULT NULL,
  `DESCRIPTION` text DEFAULT NULL,
  `MAPS_URL` text DEFAULT NULL,
  `IMAGE_URL` varchar(255) DEFAULT NULL,
  `LATITUDE` decimal(10,7) DEFAULT NULL,
  `LONGITUDE` decimal(10,7) DEFAULT NULL,
  `OPEN_TIME` time DEFAULT NULL,
  `CLOSE_TIME` time DEFAULT NULL,
  `VISIT_DURATION` int(11) DEFAULT NULL,
  `RATING` decimal(2,1) DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `idx_category` (`CATEGORY`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `madinah_landmarks` (`ID`, `NAME`, `CATEGORY`, `DESCRIPTION`, `MAPS_URL`, `IMAGE_URL`, `LATITUDE`, `LONGITUDE`, `OPEN_TIME`, `CLOSE_TIME`, `VISIT_DURATION`, `RATING`, `CREATED_AT`) VALUES
(1, 'على خطاه', 'متاحف و ثقافة', 'مسار وجولات سياحية وإثرائية مخصصة لتتبع خطوات الرسول صلى الله عليه وسلم والأحداث النبوية العظيمة بالمدينة المنورة.', 'https://www.google.com/maps/search/?api=1&query=24.4380414,39.6163079', 'images/على خطاه.jpg', 24.4380414, 39.6163079, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(2, 'جولة سياحية المدينة (الباص السياحي)', 'متاحف و ثقافة', 'خدمة النقل السياحي عبر حافلات مكشوفة السقف لنقل الزوار بين أبرز المعالم التاريخية والثقافية.', 'https://www.google.com/maps/search/?api=1&query=24.4948637,39.5944923', 'images/الباص السياحي.jpg', 24.4948637, 39.5944923, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(3, 'المسجد النبوي', 'معالم دينية', 'ثاني أقدس المساجد في الإسلام وأحد المساجد الثلاثة التي تشد إليها الرحال، يضم الحجرة النبوية والروضة الشريفة.', 'https://www.google.com/maps/search/?api=1&query=24.4672000,39.6111000', 'images/المسجد النبوي.jpeg', 24.4672000, 39.6111000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(4, 'مسجد قباء', 'معالم دينية', 'أول مسجد أسس على التقوى وبناه النبي صلى الله عليه وسلم فور وصوله إلى المدينة المنورة في الهجرة.', 'https://www.google.com/maps/search/?api=1&query=24.4392000,39.6172000', 'images/مسجد قباء.png', 24.4392000, 39.6172000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(5, 'مسجد القبلتين', 'معالم دينية', 'المسجد الذي نزل فيه الأمر الإلهي بالتحول من استقبال بيت المقدس إلى الكعبة المشرفة في مكة المكرمة.', 'https://www.google.com/maps/search/?api=1&query=24.4839000,39.5794000', 'images/مسجد القبلتين.jpeg', 24.4839000, 39.5794000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(6, 'المساجد السبعة', 'معالم دينية', 'من أهم المعالم التي يزورها القادمون إلى المدينة المنورة، وهي مجموعة مساجد صغيرة عددها الحقيقي ستة وليس سبعة ولكنها اشتهرت بهذا الاسم مسجد الفتح\r\nو', 'https://www.google.com/maps/search/?api=1&query=24.4768000,39.5959000', 'images/مسجد الفتح (المسجد السبعة).jpeg', 24.4768000, 39.5959000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(7, 'مسجد بني أنيف', 'معالم دينية', 'مسجد أثري صلى فيه النبي صلى الله عليه وسلم عند عيادته لطلحة بن البراء رضي الله عنه.', 'https://www.google.com/maps/search/?api=1&query=24.4371000,39.6106000', 'images/مسجد بني انيف.jpg', 24.4371000, 39.6106000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(8, 'مسجد العصبة', 'معالم دينية', 'يقع بجنوب المدينة المنورة وكان يصلي فيه المهاجرون الأوائل قبل مقدم النبي صلى الله عليه وسلم. يقع المسجد الأثري الصغير وبئر الهجيم داخل أحد بساتين', 'https://www.google.com/maps/search/?api=1&query=24.4309000,39.6053000', 'images/مسجد العصبية.jpeg', 24.4309000, 39.6053000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(9, 'بئر غرس', 'معالم تاريخية', 'من أهم الآبار النبوية التي كان يستطيب النبي صلى الله عليه وسلم ماءها وأوصى أن يغسل من مائها.', 'https://www.google.com/maps/search/?api=1&query=24.4449721,39.6272551', 'images/بئر غرس.png', 24.4449721, 39.6272551, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(10, 'بئر الهجيم', 'معالم تاريخية', 'بئر أثرية تاريخية تقع ضمن النطاق النبوي والتاريخي لآبار المدينة المنورة.', 'https://www.google.com/maps/search/?api=1&query=24.4307157,39.6060153', 'images/بئر الهجيم.jpeg', 24.4307157, 39.6060153, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(11, 'بئر الفقير (بئر سلمان الفارسي)', 'معالم تاريخية', 'تقع في العوالي وتعود قصتها للصحابي سلمان الفارسي في الأرض التي كاتب عليها سيده ليعتق.', 'https://www.google.com/maps/search/?api=1&query=24.4446323,39.6326158', 'images/بئر الفقير (بئر سلمان الفارسي).jpeg', 24.4446323, 39.6326158, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(12, 'بستان المستظل عذق', 'معالم تاريخية', 'موقع تاريخي استظل فيه النبي وصاحبه أبو بكر الصديق فور وصولهما إلى المدينة في الهجرة النبوية.', 'https://www.google.com/maps/search/?api=1&query=24.4385747,39.6158305', 'images/مسجد بئر عذق.jpg', 24.4385747, 39.6158305, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(13, 'المعرض والمتحف الدولي للسيرة النبوية والحضارة الإسلامية', 'متاحف و ثقافة', 'متحف عالمي متقدم بجوار المسجد النبوي يعتمد على تقنيات الشاشات التفاعلية والهولوغرام لعرض السيرة النبوية.', 'https://www.google.com/maps/search/?api=1&query=24.4655000,39.6113000', 'images/المعرض و المتحف الدولي للسيرة النبوية.jpeg', 24.4655000, 39.6113000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(14, 'متحف وبستان الصافية', 'متاحف و ثقافة', 'مشروع ثقافي بيئي متكامل يدمج بين الهوية العمرانية الحجازية والمعارض الرقمية الحديثة.', 'https://www.google.com/maps/search/?api=1&query=24.4639000,39.6116000', 'images/متحف وبستان الصافية.jpeg', 24.4639000, 39.6116000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(15, 'متحف سكة الحديد الحجاز', 'متاحف و ثقافة', 'متحف تاريخي في محطة القطار القديمة يعرض عربات القطار والأجهزة التاريخية والقطع الأثرية.', 'https://www.google.com/maps/search/?api=1&query=24.4619462,39.6009017', 'images/متحف سكة الحديد الحجاز.webp', 24.4619462, 39.6009017, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(16, 'متحف دار المدينة', 'متاحف و ثقافة', 'أول متحف متخصص بتاريخ وتراث المدينة المنورة العمراني والاجتماعي ويضم مجسمات دقيقة للغاية.', 'https://www.google.com/maps/search/?api=1&query=24.4533000,39.6974000', 'images/متحف دار المدينة.jpeg', 24.4533000, 39.6974000, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(17, 'متحف دار السلام', 'متاحف و ثقافة', 'متحف يعرض المئات من المخطوطات والقطع الأثرية التي تؤرخ لتاريخ المدينة والحرمين الشريفين.', 'https://www.google.com/maps/search/?api=1&query=24.4679183,39.6074040', 'images/متحف دار السلام.jpg', 24.4679183, 39.6074040, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(18, 'متحف ذات نخل', 'متاحف و ثقافة', 'متحف متخصص يبرز المكانة التاريخية والزراعية للنخيل وأدوات الفلاحة والري في المدينة المنورة.', 'https://www.google.com/maps/search/?api=1&query=24.4055756,39.7015757', 'images/متحف ذات نخل.jpeg', 24.4055756, 39.7015757, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(19, 'بستان المستظل', 'أماكن طبيعية', 'من البساتين التاريخية القريبة من مسجد قباء وترتبط بذكريات استقبال أهل المدينة للنبي بالترحاب.', 'https://www.google.com/maps/search/?api=1&query=24.4385935,39.6158380', 'images/بستان المستظل.jpeg', 24.4385935, 39.6158380, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(20, 'جبل أحد', 'أماكن طبيعية', 'أشهر جبال المدينة وقال عنه النبي: احد جبل يحبنا ونحبه، وشهد سفحه معركة أحد ومقبرة الشهداء.', 'https://www.google.com/maps/search/?api=1&query=24.5236960,39.6263621', 'images/جبل احد.png', 24.5236960, 39.6263621, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(21, 'جبل عير', 'أماكن طبيعية', 'جبل يقع في الجزء الجنوبي للمدينة المنورة ويشكل الحد الجنوبي لحرم المدينة المنورة.', 'https://www.google.com/maps/search/?api=1&query=24.3897692,39.5742543', 'images/جبل عير.webp', 24.3897692, 39.5742543, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(22, 'السيرة الرحلة إلى قباء', 'متاحف و ثقافة', 'هي فعالية تفاعلية غامرة بجوار مسجد قباء في المدينة المنورة تسرد قصة الأنصار واستقبالهم للنبي محمد ﷺ بعد الهجرة النبوية وتأسيس أول مسجد في الإسلام', 'https://www.google.com/maps/search/?api=1&query=24.4394125,39.6157747', 'images/السيرة.avif', 24.4394125, 39.6157747, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15'),
(23, 'مزرعة سلمان الفارسي', 'أماكن طبيعية', 'مزرعة أثرية ذات قيمة تاريخية ارتبطت بقصة مكاتبة سلمان الفارسي وغرس النبوة فيها للنخيل.', 'https://www.google.com/maps/search/?api=1&query=24.4447281,39.6326130', 'images/مزرعة سلمان.png', 24.4447281, 39.6326130, NULL, NULL, NULL, NULL, '2026-09-24 22:00:15');

ALTER TABLE `madinah_landmarks` AUTO_INCREMENT = 24;

-- ------------------------------------------------------------
-- 2) المستخدمون
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3) الرحلات
-- ------------------------------------------------------------
CREATE TABLE `trips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `trip_name` varchar(200) NOT NULL,
  `start_location` varchar(200) NOT NULL,
  `total_duration` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_trips_user` (`user_id`),
  CONSTRAINT `fk_trips_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4) معالم كل رحلة (planned_arrival يقبل NULL لأن save_trip.php يحفظه فارغاً)
-- ------------------------------------------------------------
CREATE TABLE `trip_places` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL,
  `landmark_id` int(11) NOT NULL,
  `visit_order` int(11) NOT NULL,
  `planned_arrival` varchar(20) DEFAULT NULL,
  `planned_duration` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_trip_order` (`trip_id`,`visit_order`),
  KEY `idx_tp_landmark` (`landmark_id`),
  CONSTRAINT `fk_tp_trip` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tp_landmark` FOREIGN KEY (`landmark_id`) REFERENCES `madinah_landmarks` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5) التقييمات
-- ------------------------------------------------------------
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `landmark_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review` (`user_id`,`landmark_id`),
  KEY `idx_rev_landmark` (`landmark_id`),
  CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5),
  CONSTRAINT `fk_rev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_landmark` FOREIGN KEY (`landmark_id`) REFERENCES `madinah_landmarks` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6) المسارات الأكثر انتشاراً (تُحسب تلقائياً من الرحلات المحفوظة)
-- ------------------------------------------------------------
CREATE VIEW `route_signatures` AS
SELECT tp.`trip_id`,
       GROUP_CONCAT(tp.`landmark_id` ORDER BY tp.`visit_order` SEPARATOR '-') AS `signature`,
       GROUP_CONCAT(l.`NAME` ORDER BY tp.`visit_order` SEPARATOR ' ← ') AS `route_names`
FROM `trip_places` tp
JOIN `madinah_landmarks` l ON l.`ID` = tp.`landmark_id`
GROUP BY tp.`trip_id`;

CREATE VIEW `popular_routes` AS
SELECT `signature`, MIN(`route_names`) AS `route_names`, COUNT(*) AS `times_used`
FROM `route_signatures`
GROUP BY `signature`
ORDER BY `times_used` DESC;

CREATE VIEW `popular_landmarks` AS
SELECT l.`ID`, l.`NAME`, COUNT(tp.`id`) AS `times_added`
FROM `madinah_landmarks` l
LEFT JOIN `trip_places` tp ON tp.`landmark_id` = l.`ID`
GROUP BY l.`ID`, l.`NAME`
ORDER BY `times_added` DESC;

SET FOREIGN_KEY_CHECKS = 1;
