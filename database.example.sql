-- ============================================================
--  متجر «لمسة» — قاعدة البيانات الكاملة
--  طريقة الاستخدام: phpMyAdmin > Import > اختر هذا الملف > Go
--  حساب المدير: admin@example.com / ChangeMe!2026 - غيّره فوراً بعد أول دخول.
--  لا تحتاج لإنشاء قاعدة البيانات يدوياً، الملف ينشئها تلقائياً.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `lamsa_store`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `lamsa_store`;

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- المستخدمون
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(80) DEFAULT NULL,
  `role` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- الأقسام
-- ------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(80) NOT NULL,
  `icon` VARCHAR(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- المنتجات
-- ------------------------------------------------------------
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `old_price` DECIMAL(10,2) DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_category_idx` (`category_id`),
  KEY `products_featured_idx` (`is_featured`),
  KEY `products_active_idx` (`is_active`),
  CONSTRAINT `products_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- الطلبات
-- ------------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(20) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `shipping` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('cod','transfer') NOT NULL DEFAULT 'cod',
  `customer_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `city` VARCHAR(80) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_number_unique` (`order_number`),
  KEY `orders_user_idx` (`user_id`),
  KEY `orders_status_idx` (`status`),
  CONSTRAINT `orders_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- عناصر الطلب
-- ------------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(160) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  `line_total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_idx` (`order_id`),
  KEY `order_items_product_idx` (`product_id`),
  CONSTRAINT `order_items_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- البيانات الأولية
-- ============================================================

-- كلمة مرور المدير: Admin@123
-- كلمة مرور العميل التجريبي: Test@1234
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `city`, `role`) VALUES
(1, 'مدير المتجر', 'admin@example.com', '$2y$10$6Myrovodrrx1wRVkTLi5FeIjhS/tPgFIehAn0BFUy9TfqWeYYa3CO', '0790000000', 'شارع المدينة المنورة', 'عمّان', 'admin'),
(2, 'أحمد العبدالله', 'ahmad@example.com', '$2y$10$2Hi9hzdaEUeBUKQP/b/zH.Z33wYAZld8/E/TcE1iN7I62uad4KtXW', '0781111222', 'شارع الجامعة - بناية 12', 'إربد', 'customer');

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'نساء', 'women', '👗'),
(2, 'رجال', 'men', '👔'),
(3, 'أطفال', 'kids', '🧸'),
(4, 'إلكترونيات', 'electronics', '🎧'),
(5, 'المنزل', 'home', '🛋️'),
(6, 'جمال', 'beauty', '💄');

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `old_price`, `stock`, `image`, `is_featured`, `is_active`, `created_at`) VALUES
(1, 1, 'فستان صيفي بنقشة زهور', 'summer-floral-dress',
 'فستان صيفي خفيف بقماش فيسكوز ناعم ونقشة زهور هادئة. تصميم واسع ومريح يناسب الإطلالات اليومية والمناسبات الصغيرة، مع حزام رفيع قابل للربط.',
 29.90, 45.00, 18, 'assets/img/products/dress.svg', 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),

(2, 1, 'بلوزة كتان بأكمام واسعة', 'linen-blouse',
 'بلوزة من الكتان المخلوط بقصة عصرية وأكمام واسعة تمنح إحساساً بالانتعاش. سهلة التنسيق مع الجينز أو التنورة وتأتي بثلاث درجات لونية.',
 18.50, 25.00, 32, 'assets/img/products/blouse.svg', 1, 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),

(3, 1, 'حقيبة يد جلدية صغيرة', 'leather-handbag',
 'حقيبة يد أنيقة بجلد صناعي عالي الجودة، بحجم عملي يتسع للأساسيات اليومية. تأتي بحزام كتف قابل للفصل وتفاصيل ذهبية بسيطة.',
 34.00, NULL, 12, 'assets/img/products/bag.svg', 0, 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),

(4, 2, 'قميص قطني كلاسيكي', 'classic-cotton-shirt',
 'قميص رجالي من القطن الخالص بقصة مستقيمة وياقة كلاسيكية. مناسب للعمل والإطلالات الكاجوال، ومتوفر بمقاسات متعددة.',
 22.00, 30.00, 25, 'assets/img/products/shirt.svg', 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),

(5, 2, 'بنطال جينز بقصة مستقيمة', 'straight-jeans',
 'جينز بقصة مستقيمة وقماش مرن يوفر حرية حركة كاملة. لون أزرق كلاسيكي يغسل بسهولة ويليق مع القمصان والأحذية الرياضية.',
 39.90, 55.00, 15, 'assets/img/products/jeans.svg', 0, 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),

(6, 2, 'حذاء رياضي خفيف', 'light-sneakers',
 'حذاء رياضي خفيف الوزن بنعل مرن ونعل خارجي مضاد للانزلاق. تصميم بسيط يناسب المشي اليومي والاستخدام الكاجوال.',
 44.00, 60.00, 20, 'assets/img/products/sneaker.svg', 1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),

(7, 3, 'طقم أطفال قطني - 3 قطع', 'kids-cotton-set',
 'طقم مريح للأطفال من القطن الناعم يتكوّن من تيشيرت وشورت وبنطال طويل. ألوان ثابتة لا تبهت بعد الغسيل ومقاسات من 2 إلى 8 سنوات.',
 24.90, 35.00, 22, 'assets/img/products/kidsset.svg', 0, 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),

(8, 3, 'جاكيت أطفال مبطن', 'kids-padded-jacket',
 'جاكيت مبطن خفيف يحمي من برودة الصباح، بسحاب أمامي وجيوب جانبية. تصميم عملي وسهل التنظيف للأطفال في الحركة الدائمة.',
 31.50, NULL, 9, 'assets/img/products/jacket.svg', 0, 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),

(9, 4, 'سماعات لاسلكية حول الأذن', 'wireless-headphones',
 'سماعات لاسلكية بتقنية بلوتوث 5.3 وعزل سلبي للضوضاء. بطارية تدوم حتى 30 ساعة، وسائد ناعمة، وميكروفون مدمج للمكالمات.',
 59.00, 89.00, 14, 'assets/img/products/headphones.svg', 1, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),

(10, 4, 'ساعة ذكية بشاشة AMOLED', 'smart-watch',
 'ساعة ذكية بشاشة AMOLED واضحة وتتبع للنشاط ونبضات القلب والنوم. مقاومة للماء وبطارية تدوم حتى 7 أيام، مع إشعارات الهاتف.',
 79.00, 99.00, 10, 'assets/img/products/watch.svg', 0, 1, DATE_SUB(NOW(), INTERVAL 9 DAY)),

(11, 5, 'مصباح طاولة خشبي', 'wooden-table-lamp',
 'مصباح طاولة بقاعدة خشبية دافئة وغطاء قماشي يوزّع إضاءة هادئة. يضيف لمسة ديكور أنيقة لغرفة النوم أو مكتب العمل.',
 26.00, NULL, 17, 'assets/img/products/lamp.svg', 0, 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),

(12, 6, 'طقم عطور - 3 قطع', 'perfume-set',
 'طقم عطور أنيق يضم ثلاث زجاجات صغيرة بروائح شرقية ومنعشة. مثالي للاستخدام اليومي أو كهدية بتغليف أنيق.',
 42.00, 58.00, 11, 'assets/img/products/perfume.svg', 1, 1, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- ------------------------------------------------------------
-- طلبات تجريبية تظهر في حساب العميل ولوحة الإدارة
-- ------------------------------------------------------------
INSERT INTO `orders` (`id`, `user_id`, `order_number`, `subtotal`, `shipping`, `total`, `status`, `payment_method`, `customer_name`, `phone`, `city`, `address`, `notes`, `created_at`) VALUES
(1, 2, 'LM-00001', 48.40, 2.00, 50.40, 'delivered', 'cod',
 'أحمد العبدالله', '0781111222', 'إربد', 'شارع الجامعة - بناية 12', 'الرجاء الاتصال قبل التوصيل', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2, 2, 'LM-00002', 59.00, 0.00, 59.00, 'shipped', 'transfer',
 'أحمد العبدالله', '0781111222', 'إربد', 'شارع الجامعة - بناية 12', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `unit_price`, `quantity`, `line_total`) VALUES
(1, 1, 'فستان صيفي بنقشة زهور', 29.90, 1, 29.90),
(1, 2, 'بلوزة كتان بأكمام واسعة', 18.50, 1, 18.50),
(2, 9, 'سماعات لاسلكية حول الأذن', 59.00, 1, 59.00);

SET FOREIGN_KEY_CHECKS = 1;
