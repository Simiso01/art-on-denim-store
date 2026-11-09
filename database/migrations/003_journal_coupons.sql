-- Art on Denim — journal and coupon column
-- Safe to run on a database imported before this update.
-- The website also applies this automatically on the next page load.

ALTER TABLE `orders`
  ADD COLUMN `coupon_code` VARCHAR(50) NULL AFTER `discount_amount`;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `excerpt` VARCHAR(500) NOT NULL,
  `body` MEDIUMTEXT NOT NULL,
  `author_name` VARCHAR(120) NOT NULL DEFAULT 'Art on Denim',
  `status` ENUM('draft','published') NOT NULL DEFAULT 'published',
  `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_slug` (`slug`),
  KEY `idx_blog_status` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
