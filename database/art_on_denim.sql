-- =============================================================================
-- ART ON DENIM (AOD) — MySQL Database Schema
-- Compatible with: MySQL 5.7+ / MariaDB 10.3+ / phpMyAdmin (XAMPP / WAMP)
-- Import: Create database or run this entire script in phpMyAdmin
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+02:00';

CREATE DATABASE IF NOT EXISTS `art_on_denim`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `art_on_denim`;

-- -----------------------------------------------------------------------------
-- USERS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(80) NOT NULL,
  `last_name` VARCHAR(80) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
  `address_line1` VARCHAR(255) DEFAULT NULL,
  `address_line2` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `province` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'South Africa',
  `email_verified_at` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CATEGORIES
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(140) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_active` (`is_active`),
  KEY `idx_categories_parent` (`parent_id`),
  CONSTRAINT `fk_categories_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- BRANDS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(140) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_brands_slug` (`slug`),
  KEY `idx_brands_featured` (`is_featured`),
  KEY `idx_brands_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PRODUCTS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku` VARCHAR(60) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `brand_id` INT UNSIGNED DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `compare_at_price` DECIMAL(10,2) DEFAULT NULL,
  `cost_price` DECIMAL(10,2) DEFAULT NULL,
  `gender` ENUM('unisex', 'men', 'women') NOT NULL DEFAULT 'unisex',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_new` TINYINT(1) NOT NULL DEFAULT 0,
  `is_on_sale` TINYINT(1) NOT NULL DEFAULT 0,
  `is_aod_collection` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'active',
  `meta_title` VARCHAR(160) DEFAULT NULL,
  `meta_description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_brand` (`brand_id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_featured` (`is_featured`),
  KEY `idx_products_new` (`is_new`),
  KEY `idx_products_sale` (`is_on_sale`),
  KEY `idx_products_aod` (`is_aod_collection`),
  KEY `idx_products_price` (`price`),
  FULLTEXT KEY `ft_products_search` (`name`, `short_description`, `description`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_products_brand`
    FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PRODUCT IMAGES
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(200) DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`),
  KEY `idx_product_images_primary` (`is_primary`),
  CONSTRAINT `fk_product_images_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- INVENTORY (size-based stock)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `size` VARCHAR(20) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `reserved` INT NOT NULL DEFAULT 0,
  `low_stock_threshold` INT NOT NULL DEFAULT 5,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_product_size` (`product_id`, `size`),
  KEY `idx_inventory_qty` (`quantity`),
  CONSTRAINT `fk_inventory_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CART & CART ITEMS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(128) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cart_user` (`user_id`),
  KEY `idx_cart_session` (`session_id`),
  CONSTRAINT `fk_cart_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `size` VARCHAR(20) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_item` (`cart_id`, `product_id`, `size`),
  KEY `idx_cart_items_product` (`product_id`),
  CONSTRAINT `fk_cart_items_cart`
    FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- WISHLIST
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE `wishlist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wishlist_user_product` (`user_id`, `product_id`),
  KEY `idx_wishlist_product` (`product_id`),
  CONSTRAINT `fk_wishlist_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wishlist_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ORDERS & ORDER ITEMS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(30) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM(
    'pending',
    'paid',
    'processing',
    'shipped',
    'delivered',
    'cancelled',
    'refunded'
  ) NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('card', 'eft', 'payfast', 'cod') NOT NULL DEFAULT 'payfast',
  `subtotal` DECIMAL(10,2) NOT NULL,
  `shipping_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` VARCHAR(50) DEFAULT NULL,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'ZAR',
  `customer_email` VARCHAR(190) NOT NULL,
  `customer_phone` VARCHAR(30) DEFAULT NULL,
  `shipping_full_name` VARCHAR(160) NOT NULL,
  `shipping_address_line1` VARCHAR(255) NOT NULL,
  `shipping_address_line2` VARCHAR(255) DEFAULT NULL,
  `shipping_city` VARCHAR(100) NOT NULL,
  `shipping_province` VARCHAR(100) NOT NULL,
  `shipping_postal_code` VARCHAR(20) NOT NULL,
  `shipping_country` VARCHAR(100) NOT NULL DEFAULT 'South Africa',
  `tracking_number` VARCHAR(100) DEFAULT NULL,
  `tracking_carrier` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `placed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_payment_status` (`payment_status`),
  KEY `idx_orders_placed` (`placed_at`),
  CONSTRAINT `fk_orders_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `product_sku` VARCHAR(60) NOT NULL,
  `size` VARCHAR(20) NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `line_total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PAYMENTS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'payfast',
  `provider_ref` VARCHAR(120) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'ZAR',
  `status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `raw_response` TEXT DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_provider_ref` (`provider_ref`),
  CONSTRAINT `fk_payments_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PROMOTIONS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `promo_type` ENUM('banner', 'campaign', 'coupon', 'sale') NOT NULL DEFAULT 'campaign',
  `discount_type` ENUM('percent', 'fixed', 'none') NOT NULL DEFAULT 'none',
  `discount_value` DECIMAL(10,2) DEFAULT NULL,
  `coupon_code` VARCHAR(50) DEFAULT NULL,
  `brand_id` INT UNSIGNED DEFAULT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `cta_label` VARCHAR(80) DEFAULT NULL,
  `cta_url` VARCHAR(255) DEFAULT NULL,
  `placement` ENUM('hero', 'home_mid', 'home_sale', 'sidebar', 'checkout') NOT NULL DEFAULT 'hero',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_promotions_slug` (`slug`),
  UNIQUE KEY `uq_promotions_coupon` (`coupon_code`),
  KEY `idx_promotions_active` (`is_active`),
  KEY `idx_promotions_placement` (`placement`),
  KEY `idx_promotions_dates` (`starts_at`, `ends_at`),
  CONSTRAINT `fk_promotions_brand`
    FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_promotions_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- NOTIFICATIONS
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'general',
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_read` (`is_read`),
  CONSTRAINT `fk_notifications_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CONTACT MESSAGES
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'read', 'replied', 'archived') NOT NULL DEFAULT 'new',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_status` (`status`),
  KEY `idx_contact_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ORDER STATUS HISTORY (tracking)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `order_status_history`;
CREATE TABLE `order_status_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_osh_order` (`order_id`),
  CONSTRAINT `fk_osh_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_osh_user`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SEED DATA
-- Password for all demo users: Password123!
-- Hash generated with PHP password_hash(..., PASSWORD_DEFAULT)
-- =============================================================================

-- Demo password for all seed users: Password123!
INSERT INTO `users`
  (`first_name`, `last_name`, `email`, `phone`, `password_hash`, `role`, `status`, `city`, `province`, `postal_code`)
VALUES
  ('Admin', 'AOD', 'admin@artondenim.co.za', '0820000001',
   '$2y$10$I/l1.eNDftcyVtF9p3RkBOSpkOc.yAEAcwJDriAFwvDrf.c0Z9PBm',
   'admin', 'active', 'Johannesburg', 'Gauteng', '2000'),
  ('Lebo', 'Mokoena', 'lebo@example.com', '0820000002',
   '$2y$10$I/l1.eNDftcyVtF9p3RkBOSpkOc.yAEAcwJDriAFwvDrf.c0Z9PBm',
   'customer', 'active', 'Cape Town', 'Western Cape', '8001'),
  ('Thandi', 'Nkosi', 'thandi@example.com', '0820000003',
   '$2y$10$I/l1.eNDftcyVtF9p3RkBOSpkOc.yAEAcwJDriAFwvDrf.c0Z9PBm',
   'customer', 'active', 'Durban', 'KwaZulu-Natal', '4001');

INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`, `is_active`) VALUES
('Denim', 'denim', 'Premium denim silhouettes crafted for everyday edge.', 1, 1),
('Outerwear', 'outerwear', 'Layered statements for transitional weather.', 2, 1),
('Tops', 'tops', 'Essential and elevated tops.', 3, 1),
('Bottoms', 'bottoms', 'Tailored and casual lower pieces.', 4, 1),
('Footwear', 'footwear', 'Designer sneakers and boots.', 5, 1),
('Accessories', 'accessories', 'Finishing pieces that complete the look.', 6, 1);

INSERT INTO `brands` (`name`, `slug`, `description`, `is_featured`, `is_active`) VALUES
('Art on Denim', 'art-on-denim', 'House label — premium streetwear rooted in denim craft.', 1, 1),
('Rick Owens', 'rick-owens', 'Avant-garde designer silhouettes and dark luxury.', 1, 1),
('Fear of God', 'fear-of-god', 'Elevated essentials with American luxury codes.', 1, 1),
('AMI Paris', 'ami-paris', 'Parisian ready-to-wear with clean tailoring.', 1, 1),
('Stone Island', 'stone-island', 'Technical outerwear and research-driven design.', 1, 1),
('Maison Margiela', 'maison-margiela', 'Deconstructed luxury and conceptual fashion.', 0, 1);

INSERT INTO `products`
(`sku`, `name`, `slug`, `description`, `short_description`, `category_id`, `brand_id`,
 `price`, `compare_at_price`, `gender`, `is_featured`, `is_new`, `is_on_sale`, `is_aod_collection`, `status`)
VALUES
('AOD-DN-001', 'Indigo Raw Selvedge Jean', 'indigo-raw-selvedge-jean',
 'House-cut raw selvedge denim with a clean taper and reinforced stitching.',
 'Raw selvedge denim with a modern taper.',
 1, 1, 1899.00, NULL, 'unisex', 1, 1, 0, 1, 'active'),

('AOD-DN-002', 'Washed Black Straight Jean', 'washed-black-straight-jean',
 'Soft washed black denim with a straight leg and subtle fade.',
 'Washed black straight-leg denim.',
 1, 1, 1599.00, 1899.00, 'unisex', 1, 0, 1, 1, 'active'),

('AOD-OT-003', 'Canvas Utility Jacket', 'canvas-utility-jacket',
 'Structured canvas jacket with dual pockets and matte hardware.',
 'Utility jacket in heavyweight canvas.',
 2, 1, 2499.00, NULL, 'unisex', 1, 1, 0, 1, 'active'),

('RO-DRP-004', 'Rick Owens Drop-Crotch Pant', 'rick-owens-drop-crotch-pant',
 'Signature elongated silhouette in soft black cotton.',
 'Avant-garde drop-crotch pant.',
 4, 2, 8900.00, 14900.00, 'unisex', 1, 0, 1, 0, 'active'),

('RO-TEE-005', 'Rick Owens Level Tee', 'rick-owens-level-tee',
 'Oversized level tee with elongated sleeves and soft handfeel.',
 'Oversized designer tee.',
 3, 2, 4200.00, 7000.00, 'unisex', 1, 1, 1, 0, 'active'),

('RO-JK-006', 'Rick Owens Bauhaus Jacket', 'rick-owens-bauhaus-jacket',
 'Architectural outerwear with draped panels and asymmetrical zip.',
 'Architectural draped jacket.',
 2, 2, 18500.00, NULL, 'unisex', 1, 1, 0, 0, 'active'),

('FOG-HD-007', 'Fear of God Essentials Hoodie', 'fear-of-god-essentials-hoodie',
 'Relaxed fleece hoodie with tonal branding.',
 'Relaxed luxury fleece hoodie.',
 3, 3, 2899.00, NULL, 'unisex', 1, 1, 0, 0, 'active'),

('AMI-CD-008', 'AMI De Coeur Cardigan', 'ami-de-coeur-cardigan',
 'Fine-knit cardigan with signature heart embroidery.',
 'Parisian knit cardigan.',
 3, 4, 4599.00, NULL, 'unisex', 0, 1, 0, 0, 'active'),

('SI-SL-009', 'Stone Island Soft Shell', 'stone-island-soft-shell',
 'Weather-resistant soft shell with compass badge.',
 'Technical soft shell outerwear.',
 2, 5, 7999.00, NULL, 'men', 1, 0, 0, 0, 'active'),

('AOD-ACC-010', 'AOD Brass Cap', 'aod-brass-cap',
 'Structured cap with embroidered AOD mark and brass hardware detail.',
 'Structured house-brand cap.',
 6, 1, 599.00, NULL, 'unisex', 0, 1, 0, 1, 'active'),

('MM-SN-011', 'Maison Margiela Replica Sneaker', 'maison-margiela-replica-sneaker',
 'Iconic replica low-top with painted sole detailing.',
 'Painted-sole replica sneaker.',
 5, 6, 9200.00, NULL, 'unisex', 1, 0, 0, 0, 'active'),

('AOD-DN-012', 'AOD Wide Crop Denim', 'aod-wide-crop-denim',
 'Wide cropped denim with a soft rinse and clean hem.',
 'Wide-crop rinse denim.',
 1, 1, 1799.00, 2199.00, 'women', 0, 0, 1, 1, 'active');

INSERT INTO `product_images` (`product_id`, `image_path`, `alt_text`, `is_primary`, `sort_order`) VALUES
(1,  'assets/images/products/indigo-selvedge-jean.jpg', 'Indigo Raw Selvedge Jean', 1, 0),
(1,  'assets/images/products/indigo-selvedge-jean-2.jpg', 'Indigo Raw Selvedge Jean alternate view', 0, 1),
(2,  'assets/images/products/black-straight-jean.jpg', 'Washed Black Straight Jean', 1, 0),
(3,  'assets/images/products/canvas-utility-jacket.jpg', 'Canvas Utility Jacket', 1, 0),
(4,  'assets/images/products/drop-crotch-pant.jpg', 'Rick Owens Drop-Crotch Pant', 1, 0),
(5,  'assets/images/products/oversized-level-tee.jpg', 'Rick Owens Level Tee', 1, 0),
(6,  'assets/images/products/architectural-jacket.jpg', 'Rick Owens Bauhaus Jacket', 1, 0),
(6,  'assets/images/products/architectural-jacket-2.jpg', 'Rick Owens Bauhaus Jacket alternate view', 0, 1),
(7,  'assets/images/products/essentials-hoodie.jpg', 'Fear of God Essentials Hoodie', 1, 0),
(7,  'assets/images/products/essentials-hoodie-2.jpg', 'Fear of God Essentials Hoodie alternate view', 0, 1),
(8,  'assets/images/products/knit-cardigan.jpg', 'AMI De Coeur Cardigan', 1, 0),
(9,  'assets/images/products/soft-shell-outerwear.jpg', 'Stone Island Soft Shell', 1, 0),
(10, 'assets/images/products/structured-cap.jpg', 'AOD Brass Cap', 1, 0),
(11, 'assets/images/products/replica-sneaker.jpg', 'Maison Margiela Replica Sneaker', 1, 0),
(11, 'assets/images/products/replica-sneaker-2.jpg', 'Maison Margiela Replica Sneaker alternate view', 0, 1),
(12, 'assets/images/products/wide-crop-denim.jpg', 'AOD Wide Crop Denim', 1, 0);

INSERT INTO `inventory` (`product_id`, `size`, `quantity`, `low_stock_threshold`) VALUES
(1, '28', 8, 5), (1, '30', 12, 5), (1, '32', 15, 5), (1, '34', 10, 5), (1, '36', 6, 5),
(2, '28', 5, 5), (2, '30', 9, 5), (2, '32', 11, 5), (2, '34', 7, 5), (2, '36', 4, 5),
(3, 'S', 6, 5), (3, 'M', 10, 5), (3, 'L', 8, 5), (3, 'XL', 5, 5),
(4, 'S', 3, 3), (4, 'M', 4, 3), (4, 'L', 3, 3),
(5, 'S', 7, 5), (5, 'M', 9, 5), (5, 'L', 6, 5), (5, 'XL', 4, 5),
(6, 'S', 2, 2), (6, 'M', 3, 2), (6, 'L', 2, 2),
(7, 'S', 10, 5), (7, 'M', 14, 5), (7, 'L', 12, 5), (7, 'XL', 8, 5),
(8, 'S', 5, 5), (8, 'M', 7, 5), (8, 'L', 4, 5),
(9, 'M', 6, 5), (9, 'L', 5, 5), (9, 'XL', 4, 5),
(10, 'OS', 20, 5),
(11, '40', 4, 3), (11, '41', 5, 3), (11, '42', 6, 3), (11, '43', 4, 3), (11, '44', 3, 3),
(12, '24', 5, 5), (12, '26', 7, 5), (12, '28', 8, 5), (12, '30', 6, 5);

INSERT INTO `promotions`
(`title`, `slug`, `subtitle`, `description`, `promo_type`, `discount_type`, `discount_value`,
 `brand_id`, `cta_label`, `cta_url`, `placement`, `sort_order`, `is_active`, `starts_at`, `ends_at`)
VALUES
('40% Off Selected Rick Owens', '40-off-selected-rick-owens',
 'Dark luxury, reworked for the season.',
 'Discover selected Rick Owens pieces with up to 40% off.',
 'campaign', 'percent', 40.00, 2,
 'Shop Rick Owens', 'pages/brands.php?slug=rick-owens',
 'hero', 1, 1, '2026-01-01 00:00:00', '2026-12-31 23:59:59'),

('New AOD Collection', 'new-aod-collection',
 'House denim, redefined.',
 'Explore the latest Art on Denim collection.',
 'banner', 'none', NULL, 1,
 'Explore Collection', 'pages/shop.php?collection=aod',
 'home_mid', 2, 1, '2026-01-01 00:00:00', '2026-12-31 23:59:59'),

('Seasonal Sale', 'seasonal-sale',
 'Selected styles, reduced.',
 'Shop marked-down designer and house pieces.',
 'sale', 'percent', 25.00, NULL,
 'Shop Sale', 'pages/sale.php',
 'home_sale', 3, 1, '2026-01-01 00:00:00', '2026-12-31 23:59:59');

-- Seed complete. Demo login: admin@artondenim.co.za / Password123!

-- -----------------------------------------------------------------------------
-- API TOKENS (Android / mobile clients)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `device_name` VARCHAR(120) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_token_hash` (`token_hash`),
  KEY `idx_api_tokens_user` (`user_id`),
  KEY `idx_api_tokens_expires` (`expires_at`),
  CONSTRAINT `fk_api_tokens_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
