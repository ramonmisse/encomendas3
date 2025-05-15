<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'dili_encomendas'); // Change to your database username
define('DB_PASS', 'PfHMnqmM4#yci@HJ'); // Change to your database password
define('DB_NAME', 'dili_encomendas_teste');

// Create database if it doesn't exist
try {
    $pdo_setup = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo_setup->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Select the database
    $pdo_setup->exec("USE `" . DB_NAME . "`");
    
    // Create tables if they don't exist
    $pdo_setup->exec("CREATE TABLE IF NOT EXISTS `sales_representatives` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `avatar_url` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $pdo_setup->exec("CREATE TABLE IF NOT EXISTS `product_models` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `image_url` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $pdo_setup->exec("CREATE TABLE IF NOT EXISTS `companies` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo_setup->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` enum('admin','user') NOT NULL DEFAULT 'user',
        `company_id` int(11) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        KEY `company_id` (`company_id`),
        CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo_setup->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sales_representative_id` int(11) NOT NULL,
        `client_name` varchar(100) NOT NULL,
        `delivery_date` date NOT NULL,
        `model_id` int(11) NOT NULL,
        `metal_type` varchar(20) NOT NULL,
        `notes` text DEFAULT NULL,
        `image_urls` text DEFAULT NULL,
        `company_id` int(11) DEFAULT NULL,
        `created_at` datetime NOT NULL,
        CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
        PRIMARY KEY (`id`),
        KEY `sales_representative_id` (`sales_representative_id`),
        KEY `model_id` (`model_id`),
        CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`sales_representative_id`) REFERENCES `sales_representatives` (`id`),
        CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`model_id`) REFERENCES `product_models` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
} catch(PDOException $e) {
    die("ERROR: Could not set up database. " . $e->getMessage());
}

// Attempt to connect to MySQL database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>