-- Steven Picknwear — database schema
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── users ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(180) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('customer','editor','admin') NOT NULL DEFAULT 'customer',
  `phone`      VARCHAR(20) DEFAULT NULL,
  `avatar`     VARCHAR(500) DEFAULT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `reset_token`   VARCHAR(64) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── categories ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `slug`       VARCHAR(140) NOT NULL UNIQUE,
  `parent_id`  INT UNSIGNED DEFAULT NULL,
  `image`      VARCHAR(500) DEFAULT NULL,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── products ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `products` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`        INT UNSIGNED DEFAULT NULL,
  `name`               VARCHAR(200) NOT NULL,
  `slug`               VARCHAR(220) NOT NULL UNIQUE,
  `description`        LONGTEXT DEFAULT NULL,
  `short_desc`         TEXT DEFAULT NULL,
  `type`               ENUM('simple','variable') NOT NULL DEFAULT 'variable',
  `base_price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `sale_price`         DECIMAL(10,2) DEFAULT NULL,
  `sku`                VARCHAR(80) DEFAULT NULL UNIQUE,
  `stock_status`       ENUM('in_stock','out_of_stock','on_backorder') NOT NULL DEFAULT 'in_stock',
  `stock_qty`          INT NOT NULL DEFAULT 0,
  `featured_image`     VARCHAR(500) DEFAULT NULL,
  `is_featured`        TINYINT(1) NOT NULL DEFAULT 0,
  `is_sale`            TINYINT(1) NOT NULL DEFAULT 0,
  `is_one_of_a_kind`   TINYINT(1) NOT NULL DEFAULT 0,
  `status`             ENUM('published','draft') NOT NULL DEFAULT 'draft',
  `tags`                VARCHAR(500) DEFAULT NULL,
  `meta_title`         VARCHAR(200) DEFAULT NULL,
  `meta_desc`          VARCHAR(320) DEFAULT NULL,
  `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_sale` (`is_sale`),
  KEY `idx_stock` (`stock_status`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── product_variants (each row = one size of a product) ──────────────────
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`       INT UNSIGNED NOT NULL,
  `size_sa`          VARCHAR(20) DEFAULT NULL,
  `color_name`       VARCHAR(60) DEFAULT NULL,
  `color_hex`        VARCHAR(10) DEFAULT NULL,
  `image`            VARCHAR(500) DEFAULT NULL,
  `pattern_note`     VARCHAR(160) DEFAULT NULL,
  `additional_price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `stock_qty`        INT NOT NULL DEFAULT 1,
  `sku`              VARCHAR(80) DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── product_gallery ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `product_gallery` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image`      VARCHAR(500) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_gallery_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── orders ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number`     VARCHAR(40) NOT NULL UNIQUE,
  `user_id`          INT UNSIGNED DEFAULT NULL,
  `guest_name`       VARCHAR(120) DEFAULT NULL,
  `guest_email`      VARCHAR(180) DEFAULT NULL,
  `guest_phone`      VARCHAR(20) DEFAULT NULL,
  `total_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code`      VARCHAR(60) DEFAULT NULL,
  `status`           ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method`   ENUM('pop') NOT NULL DEFAULT 'pop',
  `payment_status`   ENUM('unpaid','paid','failed') NOT NULL DEFAULT 'unpaid',
  `shipping_method`  VARCHAR(60) DEFAULT NULL,
  `shipping_address` TEXT DEFAULT NULL,
  `billing_address`  TEXT DEFAULT NULL,
  `pop_path`         VARCHAR(255) DEFAULT NULL,
  `pop_uploaded_at`  DATETIME DEFAULT NULL,
  `notes`            TEXT DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── order_items ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `variant_id` INT UNSIGNED DEFAULT NULL,
  `name`       VARCHAR(220) NOT NULL,
  `size_sa`    VARCHAR(20) DEFAULT NULL,
  `qty`        INT NOT NULL DEFAULT 1,
  `price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `image`      VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── reviews ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `reviews` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`  INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `rating`      TINYINT NOT NULL DEFAULT 5,
  `comment`     TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_approved` (`is_approved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── wishlist ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_product` (`user_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── coupons ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `coupons` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`           VARCHAR(60) NOT NULL UNIQUE,
  `discount_type`  ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `min_order`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_uses`       INT DEFAULT NULL,
  `used_count`     INT NOT NULL DEFAULT 0,
  `expiry_date`    DATE DEFAULT NULL,
  `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── blogs (Journal articles) ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `blogs` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(250) NOT NULL,
  `slug`        VARCHAR(270) NOT NULL UNIQUE,
  `image`       VARCHAR(500) DEFAULT NULL,
  `excerpt`     TEXT DEFAULT NULL,
  `content`     LONGTEXT DEFAULT NULL,
  `author_id`   INT UNSIGNED NOT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status`      ENUM('published','draft') NOT NULL DEFAULT 'draft',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── settings (key/value) ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(80) NOT NULL,
  `value`    TEXT DEFAULT NULL,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── newsletter_subscribers ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`           VARCHAR(180) NOT NULL UNIQUE,
  `name`            VARCHAR(120) DEFAULT NULL,
  `status`          ENUM('active','unsubscribed') NOT NULL DEFAULT 'active',
  `token`           VARCHAR(64) NOT NULL,
  `source`          VARCHAR(40) NOT NULL DEFAULT 'footer',
  `subscribed_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── newsletter_templates (WYSIWYG-authored, reusable HTML) ───────────────
CREATE TABLE IF NOT EXISTS `newsletter_templates` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `body_html`   LONGTEXT NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── newsletter_campaigns ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `newsletter_campaigns` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject`       VARCHAR(255) NOT NULL,
  `body_html`     LONGTEXT NOT NULL,
  `template_id`   INT UNSIGNED DEFAULT NULL,
  `status`        ENUM('draft','sending','sent','failed') NOT NULL DEFAULT 'draft',
  `sent_count`    INT UNSIGNED NOT NULL DEFAULT 0,
  `sent_at`       DATETIME DEFAULT NULL,
  `created_by`    INT UNSIGNED NOT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── gallery_images (lookbook, separate from product photos) ───────────────
CREATE TABLE IF NOT EXISTS `gallery_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(160) DEFAULT NULL,
  `caption`    VARCHAR(255) DEFAULT NULL,
  `image`      VARCHAR(500) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── contact_messages (Contact page; saved before any email is attempted) ──
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `message`    TEXT NOT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`is_read`), INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Seed data ────────────────────────────────────────────────────────────

-- Default admin: admin@stevenpickandwear.co.za (password handed over separately).
-- Change it after the first login: Dashboard > Users.
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Store Admin', 'admin@stevenpickandwear.co.za', '$2y$12$tKajjSPuOjKWebUFqn3b6OTKG00CScKx6LcvbDpHzr064lX/7dnyC', 'admin')
ON DUPLICATE KEY UPDATE `email` = `email`;

INSERT INTO `categories` (`name`, `slug`, `sort_order`) VALUES
('Women', 'women', 1),
('Men', 'men', 2),
('Kids', 'kids', 3)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Bank details are placeholders: set the real ones in Admin > Settings before launch.
INSERT INTO `settings` (`key_name`, `value`) VALUES
('site_name', 'Steven Picknwear'),
('site_tagline', 'The International Brand Outlet'),
('currency', 'ZAR'),
('currency_symbol', 'R'),
('free_shipping_threshold', '1500'),
('shipping_flat_rate', '120'),
('bank_name', 'YOUR BANK'),
('bank_account_holder', 'Steven Picknwear'),
('bank_account_number', '0000000000'),
('bank_branch_code', '000000'),
('bank_account_type', 'Business Cheque'),
('maintenance_mode', '0'),
('smtp_host', ''),
('smtp_port', '587')
ON DUPLICATE KEY UPDATE `key_name` = `key_name`;
