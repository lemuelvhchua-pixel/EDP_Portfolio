-- Kabakal Fitness Equipment, demo database for the portfolio (no real customer data)
SET FOREIGN_KEY_CHECKS=0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS `kabakal_demo` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `kabakal_demo`;
DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `admin_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admin_username` (`username`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `cart_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `status` enum('active','checked_out','abandoned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cart_id`),
  KEY `idx_cart_customer` (`customer_id`),
  CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cart_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart_item` (
  `cart_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int(10) unsigned NOT NULL,
  `equipment_id` int(10) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1 CHECK (`quantity` > 0),
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_item_id`),
  UNIQUE KEY `uq_cart_equipment` (`cart_id`,`equipment_id`),
  KEY `idx_cart_item_equipment` (`equipment_id`),
  CONSTRAINT `fk_cart_item_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_item_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_category_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation` (
  `conversation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `equipment_id` int(10) unsigned DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `customer_unread` tinyint(1) NOT NULL DEFAULT 0,
  `admin_unread` tinyint(1) NOT NULL DEFAULT 1,
  `last_message_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`conversation_id`),
  KEY `idx_conversation_customer` (`customer_id`),
  KEY `fk_conversation_equipment` (`equipment_id`),
  CONSTRAINT `fk_conversation_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_conversation_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer` (
  `customer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `theme` enum('dark','light') NOT NULL DEFAULT 'dark',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `uq_customer_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment` (
  `equipment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `supplier_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `stock_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','discontinued') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_restocked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`equipment_id`),
  KEY `idx_equipment_category` (`category_id`),
  KEY `idx_equipment_supplier` (`supplier_id`),
  CONSTRAINT `fk_equipment_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_equipment_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `inventory_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('available','reserved','sold','damaged','out_of_stock') NOT NULL DEFAULT 'available',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`inventory_id`),
  UNIQUE KEY `uq_inventory_equipment_status` (`equipment_id`,`status`),
  KEY `idx_inventory_admin` (`admin_id`),
  CONSTRAINT `fk_inventory_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) unsigned NOT NULL,
  `sender` enum('customer','admin') NOT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `idx_message_conversation` (`conversation_id`),
  CONSTRAINT `fk_message_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversation` (`conversation_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_notification_customer` (`customer_id`,`is_read`),
  CONSTRAINT `fk_notification_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_item` (
  `order_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `equipment_id` int(10) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL CHECK (`quantity` > 0),
  `unit_price` decimal(10,2) NOT NULL CHECK (`unit_price` >= 0),
  `subtotal` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  PRIMARY KEY (`order_item_id`),
  KEY `idx_order_item_order` (`order_id`),
  KEY `idx_order_item_equipment` (`equipment_id`),
  CONSTRAINT `fk_order_item_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `order_date` date NOT NULL DEFAULT curdate(),
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00 CHECK (`total_amount` >= 0),
  `payment_method` enum('gcash','cod') NOT NULL DEFAULT 'gcash',
  `status` enum('pending','paid','shipped','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_date` date DEFAULT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `idx_orders_customer` (`customer_id`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment` (
  `payment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `verified_by` int(10) unsigned DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'gcash',
  `reference_number` varchar(50) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL CHECK (`amount` >= 0),
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `remarks` varchar(255) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `idx_payment_order` (`order_id`),
  KEY `idx_payment_admin` (`verified_by`),
  CONSTRAINT `fk_payment_admin` FOREIGN KEY (`verified_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_media` (
  `media_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` int(10) unsigned NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`media_id`),
  KEY `idx_media_equipment` (`equipment_id`,`sort_order`),
  CONSTRAINT `fk_media_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promo_video`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_video` (
  `video_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `source_type` enum('file','facebook') NOT NULL DEFAULT 'file',
  `file_path` varchar(255) DEFAULT NULL,
  `embed_url` varchar(500) DEFAULT NULL,
  `poster` varchar(255) DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `review` (
  `review_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `equipment_id` int(10) unsigned DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  KEY `idx_review_customer` (`customer_id`),
  KEY `idx_review_equipment` (`equipment_id`),
  CONSTRAINT `fk_review_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_review_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_like`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `review_like` (
  `review_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`,`customer_id`),
  KEY `idx_review_like_customer` (`customer_id`),
  CONSTRAINT `fk_review_like_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_review_like_review` FOREIGN KEY (`review_id`) REFERENCES `review` (`review_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `review_reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `review_reply` (
  `reply_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reply_id`),
  KEY `idx_review_reply_review` (`review_id`),
  KEY `fk_review_reply_admin` (`admin_id`),
  CONSTRAINT `fk_review_reply_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_review_reply_review` FOREIGN KEY (`review_id`) REFERENCES `review` (`review_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier` (
  `supplier_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `uq_supplier_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


USE kabakal_demo;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` (`category_id`, `name`, `description`) VALUES (1,'Boxing Equipment',NULL),(2,'Gym Equipment',NULL),(3,'Gym Machines',NULL);
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` (`equipment_id`, `category_id`, `supplier_id`, `name`, `description`, `price`, `stock_quantity`, `image_url`, `is_featured`, `status`, `created_at`, `last_restocked_at`) VALUES (1,3,NULL,'Smith Machine Red',NULL,30000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(2,3,NULL,'Monster Smith Machine w/ Pec Deck (New)',NULL,60000.00,9,NULL,1,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(3,3,NULL,'Monster Smith Machine w/ Leg Press & Row',NULL,50000.00,5,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(4,3,NULL,'Power Cage With Pulley',NULL,16500.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(5,2,NULL,'7-in-1 Bench (Fabricated)',NULL,8000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(6,2,NULL,'3-in-1 Bench (Fabricated)',NULL,6500.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(7,2,NULL,'Plate Tree (Standard)',NULL,2799.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(8,2,NULL,'Plate Tree (Olympic)',NULL,2499.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(9,2,NULL,'3-Layer Dumbbell Rack',NULL,5000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(10,2,NULL,'Pyramid Rack',NULL,2500.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(11,2,NULL,'7-in-1 Bench with 80 lbs Weight',NULL,8500.00,9,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(12,2,NULL,'6-in-1 Bench with 80 lbs Weight',NULL,9000.00,2,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(13,2,NULL,'5-in-1 Bench',NULL,5500.00,1,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(14,3,NULL,'Cable Crossover Machine 150lbs/150lbs',NULL,25000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(15,3,NULL,'Spin Bike White',NULL,9000.00,13,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(16,3,NULL,'Spin Bike Yellow/Black',NULL,13000.00,15,NULL,1,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(17,3,NULL,'Elliptical Bike w/ Twister',NULL,10000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(18,3,NULL,'Premium Elliptical Bike',NULL,7300.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(19,1,NULL,'Heavy Duty Punching Bag Set',NULL,5500.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(20,1,NULL,'Premium Punching Bag Set',NULL,3000.00,19,NULL,1,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(21,2,NULL,'Resistance Band',NULL,700.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(22,2,NULL,'Push Up Board',NULL,500.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(23,2,NULL,'Ab Wheel',NULL,300.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(24,1,NULL,'Boxing Gloves',NULL,750.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(25,2,NULL,'Hand Gloves',NULL,100.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(26,2,NULL,'Rubber Belt',NULL,200.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(27,2,NULL,'Dumbbell/Barbell Set 40 kg',NULL,2000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(28,2,NULL,'Dumbbell/Barbell Set 30 kg',NULL,2500.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(29,2,NULL,'Dumbbell/Barbell Set 20 kg',NULL,3000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(30,3,NULL,'3 Station Home Gym',NULL,33000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(31,3,NULL,'4 Station Home Gym',NULL,34000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(32,3,NULL,'1 Station Home Gym (Live Up)',NULL,12500.00,5,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(33,3,NULL,'1 Station Home Gym w/ Leg Press',NULL,21000.00,4,NULL,1,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(34,2,NULL,'Standard Bar 7ft',NULL,1500.00,9,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(35,2,NULL,'Standard Bar 6ft',NULL,1200.00,16,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(36,2,NULL,'Standard Bar 5ft',NULL,950.00,17,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(37,2,NULL,'Curl Bar (Standard)',NULL,950.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(38,2,NULL,'Standard Plate 2.5 lbs',NULL,112.50,22,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(39,2,NULL,'Standard Plate 5 lbs',NULL,225.00,64,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(40,2,NULL,'Standard Plate 10 lbs',NULL,450.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(41,2,NULL,'Standard Plate 15 lbs',NULL,675.00,13,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(42,2,NULL,'Standard Plate 20 lbs',NULL,900.00,16,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(43,2,NULL,'Standard Plate 30 lbs',NULL,1350.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(44,2,NULL,'Standard Plate 40 lbs',NULL,1800.00,2,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(45,2,NULL,'Standard Plate 50 lbs',NULL,2250.00,8,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(46,2,NULL,'Olympic Bar 7ft',NULL,3500.00,7,NULL,1,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(47,2,NULL,'Olympic Curl Bar',NULL,2000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(48,2,NULL,'Olympic Plate 2.5 kg',NULL,247.50,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(49,2,NULL,'Olympic Plate 5 kg',NULL,495.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(50,2,NULL,'Olympic Plate 10 kg',NULL,990.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(51,2,NULL,'Olympic Plate 15 kg',NULL,1485.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(52,2,NULL,'Olympic Plate 20 kg',NULL,1980.00,12,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(53,2,NULL,'Olympic Plate 25 kg',NULL,2475.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(54,2,NULL,'Hex Dumbbell 5 lbs',NULL,250.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(55,2,NULL,'Hex Dumbbell 10 lbs',NULL,500.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(56,2,NULL,'Hex Dumbbell 15 lbs',NULL,750.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(57,2,NULL,'Hex Dumbbell 20 lbs',NULL,1000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(58,2,NULL,'Hex Dumbbell 25 lbs',NULL,1250.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(59,2,NULL,'Hex Dumbbell 30 lbs',NULL,1500.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(60,2,NULL,'Hex Dumbbell 35 lbs',NULL,1750.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(61,2,NULL,'Hex Dumbbell 40 lbs',NULL,2000.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(62,2,NULL,'Hex Dumbbell 45 lbs',NULL,2250.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(63,2,NULL,'Hex Dumbbell 50 lbs',NULL,2500.00,4,NULL,1,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(64,2,NULL,'Mat 4ft x 8ft (1 inch)',NULL,2000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(65,2,NULL,'Mat 4ft x 8ft (1/2 inch)',NULL,1200.00,14,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(66,2,NULL,'Short Bar (Standard)',NULL,400.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(67,3,NULL,'Treadmill KML',NULL,12000.00,1,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(68,2,NULL,'Kettlebell 5 lbs',NULL,300.00,2,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(69,2,NULL,'Kettlebell 10 lbs',NULL,600.00,7,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(70,2,NULL,'Kettlebell 15 lbs',NULL,900.00,2,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(71,2,NULL,'Kettlebell 20 lbs',NULL,1200.00,5,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(72,2,NULL,'Kettlebell 25 lbs',NULL,1500.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(73,2,NULL,'Kettlebell 30 lbs',NULL,1800.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(74,2,NULL,'Kettlebell 35 lbs',NULL,2100.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(75,2,NULL,'Kettlebell 40 lbs',NULL,2400.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(76,2,NULL,'Vinyl Dumbbell 1 lbs',NULL,60.00,2,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(77,2,NULL,'Vinyl Dumbbell 2 lbs',NULL,120.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(78,2,NULL,'Vinyl Dumbbell 3 lbs',NULL,180.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(79,2,NULL,'Vinyl Dumbbell 4 lbs',NULL,240.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(80,2,NULL,'Vinyl Dumbbell 5 lbs',NULL,300.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(81,2,NULL,'Vinyl Dumbbell 6 lbs',NULL,360.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(82,2,NULL,'Vinyl Dumbbell 7 lbs',NULL,420.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(83,2,NULL,'Vinyl Dumbbell 8 lbs',NULL,480.00,6,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(84,2,NULL,'Vinyl Dumbbell 9 lbs',NULL,540.00,8,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(85,2,NULL,'Vinyl Dumbbell 10 lbs',NULL,600.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(86,1,NULL,'Speed Ball',NULL,420.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(87,3,NULL,'Multi Ab Tower Bench with Lat',NULL,6000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(88,2,NULL,'Hex Dumbbell 60 lbs',NULL,6000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(89,2,NULL,'Hex Dumbbell 70 lbs',NULL,6000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(90,2,NULL,'Hex Dumbbell 80 lbs',NULL,6000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(91,2,NULL,'Hex Dumbbell 90 lbs',NULL,6000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(92,2,NULL,'Hex Dumbbell 100 lbs',NULL,6000.00,0,NULL,0,'active','2026-10-29 01:17:47',NULL),(93,2,NULL,'High Density Mat',NULL,1250.00,29,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(94,2,NULL,'3-in-1 Bench (Live Up)',NULL,3000.00,4,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(95,3,NULL,'Manual Treadmill Commercial',NULL,55000.00,5,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(96,3,NULL,'Transformer Bike',NULL,15000.00,8,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(97,2,NULL,'Kettlebell 3 lbs',NULL,180.00,3,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(98,2,NULL,'Kettlebell 7 lbs',NULL,420.00,1,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(99,2,NULL,'Tricep Bar',NULL,1500.00,5,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(100,3,NULL,'Stair Machine',NULL,70000.00,2,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(101,3,NULL,'Hip Thrust Machine',NULL,21000.00,5,NULL,0,'active','2026-10-29 01:17:47','2026-10-29 09:17:47'),(102,3,NULL,'Lat Pull Down Machine',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(103,2,NULL,'Incline/Flat Bench with Rack',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(104,2,NULL,'Decline Bench With Rack',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(105,2,NULL,'Flat Bench With Rack',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(106,3,NULL,'Dip / Leg Raise',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(107,3,NULL,'Pec Deck Machine',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(108,3,NULL,'Leg Press Hack Squat',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(109,3,NULL,'Leg Press Machine',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(110,2,NULL,'Bicep Preacher Curl with Rack',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(111,2,NULL,'Utility Stool',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(112,2,NULL,'Utility Bench Adjustable',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(113,2,NULL,'Multi Bench 7-in-1 with Bicep Preacher',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(114,3,NULL,'Leg Extension & Hamstring Curl',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(115,3,NULL,'Shoulder Press Machine',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(116,3,NULL,'Calf Raise',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(117,3,NULL,'Rowing Machine',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(118,3,NULL,'Roman Chair',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL),(119,2,NULL,'Roller',NULL,100.00,0,NULL,0,'inactive','2026-10-29 01:17:47',NULL);
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` (`inventory_id`, `equipment_id`, `admin_id`, `quantity`, `status`, `updated_at`) VALUES (1,1,NULL,0,'available','2026-10-29 01:17:47'),(2,2,NULL,9,'available','2026-10-29 01:17:47'),(3,3,NULL,5,'available','2026-10-29 01:17:47'),(4,4,NULL,0,'available','2026-10-29 01:17:47'),(5,5,NULL,0,'available','2026-10-29 01:17:47'),(6,6,NULL,0,'available','2026-10-29 01:17:47'),(7,7,NULL,0,'available','2026-10-29 01:17:47'),(8,8,NULL,0,'available','2026-10-29 01:17:47'),(9,9,NULL,0,'available','2026-10-29 01:17:47'),(10,10,NULL,4,'available','2026-10-29 01:17:47'),(11,11,NULL,9,'available','2026-10-29 01:17:47'),(12,12,NULL,2,'available','2026-10-29 01:17:47'),(13,13,NULL,1,'available','2026-10-29 01:17:47'),(14,14,NULL,0,'available','2026-10-29 01:17:47'),(15,15,NULL,13,'available','2026-10-29 01:17:47'),(16,16,NULL,15,'available','2026-10-29 01:17:47'),(17,17,NULL,0,'available','2026-10-29 01:17:47'),(18,18,NULL,0,'available','2026-10-29 01:17:47'),(19,19,NULL,0,'available','2026-10-29 01:17:47'),(20,20,NULL,19,'available','2026-10-29 01:21:22'),(21,21,NULL,0,'available','2026-10-29 01:17:47'),(22,22,NULL,0,'available','2026-10-29 01:17:47'),(23,23,NULL,0,'available','2026-10-29 01:17:47'),(24,24,NULL,0,'available','2026-10-29 01:17:47'),(25,25,NULL,0,'available','2026-10-29 01:17:47'),(26,26,NULL,0,'available','2026-10-29 01:17:47'),(27,27,NULL,0,'available','2026-10-29 01:17:47'),(28,28,NULL,0,'available','2026-10-29 01:17:47'),(29,29,NULL,0,'available','2026-10-29 01:17:47'),(30,30,NULL,0,'available','2026-10-29 01:17:47'),(31,31,NULL,0,'available','2026-10-29 01:17:47'),(32,32,NULL,5,'available','2026-10-29 01:17:47'),(33,33,NULL,4,'available','2026-10-29 01:17:47'),(34,34,NULL,9,'available','2026-10-29 01:17:47'),(35,35,NULL,16,'available','2026-10-29 01:17:47'),(36,36,NULL,17,'available','2026-10-29 01:17:47'),(37,37,NULL,4,'available','2026-10-29 01:17:47'),(38,38,NULL,22,'available','2026-10-29 01:17:47'),(39,39,NULL,64,'available','2026-10-29 01:17:47'),(40,40,NULL,4,'available','2026-10-29 01:17:47'),(41,41,NULL,13,'available','2026-10-29 01:17:47'),(42,42,NULL,16,'available','2026-10-29 01:17:47'),(43,43,NULL,0,'available','2026-10-29 01:17:47'),(44,44,NULL,2,'available','2026-10-29 01:17:47'),(45,45,NULL,8,'available','2026-10-29 01:17:47'),(46,46,NULL,7,'available','2026-10-29 01:17:47'),(47,47,NULL,0,'available','2026-10-29 01:17:47'),(48,48,NULL,6,'available','2026-10-29 01:17:47'),(49,49,NULL,6,'available','2026-10-29 01:17:47'),(50,50,NULL,6,'available','2026-10-29 01:17:47'),(51,51,NULL,6,'available','2026-10-29 01:17:47'),(52,52,NULL,12,'available','2026-10-29 01:17:47'),(53,53,NULL,0,'available','2026-10-29 01:17:47'),(54,54,NULL,0,'available','2026-10-29 01:17:47'),(55,55,NULL,0,'available','2026-10-29 01:17:47'),(56,56,NULL,0,'available','2026-10-29 01:17:47'),(57,57,NULL,0,'available','2026-10-29 01:17:47'),(58,58,NULL,0,'available','2026-10-29 01:17:47'),(59,59,NULL,0,'available','2026-10-29 01:17:47'),(60,60,NULL,4,'available','2026-10-29 01:17:47'),(61,61,NULL,4,'available','2026-10-29 01:17:47'),(62,62,NULL,0,'available','2026-10-29 01:17:47'),(63,63,NULL,4,'available','2026-10-29 01:17:47'),(64,64,NULL,0,'available','2026-10-29 01:17:47'),(65,65,NULL,14,'available','2026-10-29 01:17:47'),(66,66,NULL,0,'available','2026-10-29 01:17:47'),(67,67,NULL,1,'available','2026-10-29 01:17:47'),(68,68,NULL,2,'available','2026-10-29 01:17:47'),(69,69,NULL,7,'available','2026-10-29 01:17:47'),(70,70,NULL,2,'available','2026-10-29 01:17:47'),(71,71,NULL,5,'available','2026-10-29 01:17:47'),(72,72,NULL,6,'available','2026-10-29 01:17:47'),(73,73,NULL,6,'available','2026-10-29 01:17:47'),(74,74,NULL,4,'available','2026-10-29 01:17:47'),(75,75,NULL,4,'available','2026-10-29 01:17:47'),(76,76,NULL,2,'available','2026-10-29 01:17:47'),(77,77,NULL,6,'available','2026-10-29 01:17:47'),(78,78,NULL,6,'available','2026-10-29 01:17:47'),(79,79,NULL,6,'available','2026-10-29 01:17:47'),(80,80,NULL,4,'available','2026-10-29 01:17:47'),(81,81,NULL,6,'available','2026-10-29 01:17:47'),(82,82,NULL,4,'available','2026-10-29 01:17:47'),(83,83,NULL,6,'available','2026-10-29 01:17:47'),(84,84,NULL,8,'available','2026-10-29 01:17:47'),(85,85,NULL,4,'available','2026-10-29 01:17:47'),(86,86,NULL,0,'available','2026-10-29 01:17:47'),(87,87,NULL,0,'available','2026-10-29 01:17:47'),(88,88,NULL,0,'available','2026-10-29 01:17:47'),(89,89,NULL,0,'available','2026-10-29 01:17:47'),(90,90,NULL,0,'available','2026-10-29 01:17:47'),(91,91,NULL,0,'available','2026-10-29 01:17:47'),(92,92,NULL,0,'available','2026-10-29 01:17:47'),(93,93,NULL,29,'available','2026-10-29 01:17:47'),(94,94,NULL,4,'available','2026-10-29 01:17:47'),(95,95,NULL,5,'available','2026-10-29 01:17:47'),(96,96,NULL,8,'available','2026-10-29 01:17:47'),(97,97,NULL,3,'available','2026-10-29 01:17:47'),(98,98,NULL,1,'available','2026-10-29 01:17:47'),(99,99,NULL,5,'available','2026-10-29 01:17:47'),(100,100,NULL,2,'available','2026-10-29 01:17:47'),(101,101,NULL,5,'available','2026-10-29 01:17:47'),(102,102,NULL,0,'available','2026-10-29 01:17:47'),(103,103,NULL,0,'available','2026-10-29 01:17:47'),(104,104,NULL,0,'available','2026-10-29 01:17:47'),(105,105,NULL,0,'available','2026-10-29 01:17:47'),(106,106,NULL,0,'available','2026-10-29 01:17:47'),(107,107,NULL,0,'available','2026-10-29 01:17:47'),(108,108,NULL,0,'available','2026-10-29 01:17:47'),(109,109,NULL,0,'available','2026-10-29 01:17:47'),(110,110,NULL,0,'available','2026-10-29 01:17:47'),(111,111,NULL,0,'available','2026-10-29 01:17:47'),(112,112,NULL,0,'available','2026-10-29 01:17:47'),(113,113,NULL,0,'available','2026-10-29 01:17:47'),(114,114,NULL,0,'available','2026-10-29 01:17:47'),(115,115,NULL,0,'available','2026-10-29 01:17:47'),(116,116,NULL,0,'available','2026-10-29 01:17:47'),(117,117,NULL,0,'available','2026-10-29 01:17:47'),(118,118,NULL,0,'available','2026-10-29 01:17:47'),(119,119,NULL,0,'available','2026-10-29 01:17:47'),(129,20,1,0,'reserved','2026-10-29 01:21:57'),(131,20,1,1,'sold','2026-10-29 01:21:57');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- ---------- demo settings and accounts (password for both: kabakal-demo) ----------
INSERT INTO settings (setting_key, setting_value) VALUES
 ('shop_name', 'Kabakal Fitness Equipment'),
 ('shop_about', 'Gym gear, equipment and machines for gym owners, athletes and home workouts. (Demo copy)'),
 ('shop_address', 'Demo address, Valenzuela City'),
 ('shop_email', 'shop@kabakal.local'),
 ('shop_phone', '0000-000-0000'),
 ('shop_mobile', '0900-000-0000'),
 ('shop_facebook', ''),
 ('shop_instagram', ''),
 ('shop_map', ''),
 ('gcash_name', 'DEMO ACCOUNT'),
 ('gcash_number', '0900-000-0000'),
 ('gcash_qr', '');
INSERT INTO admin (username, password, full_name, email, role) VALUES
 ('admin', '$2y$10$JH0mo3ixFM7rSXTxYYcF3uaZh7.SQeG8Mc7j1ZWqfQEwpqB7vwvBi', 'Demo Admin', 'admin@kabakal.local', 'admin');
INSERT INTO customer (first_name, last_name, full_name, email, phone, password, address) VALUES
 ('Demo', 'Customer', 'Demo Customer', 'customer@kabakal.local', NULL, '$2y$10$Q66LH9OO.kgaz6Ti02bUJ..apu5ulkaNu1yGuVtjBJEf2MHcCElri', 'Demo address');

SET FOREIGN_KEY_CHECKS=1;
