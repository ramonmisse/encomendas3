<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
require_once 'includes/config.php';

// Create database and tables
try {
    echo "<h2>Atualizando banco de dados...</h2>";

    // Connect to MySQL server
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Update user role enum to include superadmin
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin','user') NOT NULL DEFAULT 'user'");
    echo "<p>Coluna 'role' da tabela 'users' atualizada.</p>";

    // Add reference column to product_models if it doesn't exist
    $pdo->exec("ALTER TABLE product_models ADD COLUMN IF NOT EXISTS reference varchar(50) DEFAULT NULL AFTER name");
    echo "<p>Coluna 'reference' adicionada à tabela 'product_models'.</p>";

    // Create model_variations table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `model_variations` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `model_id` int(11) NOT NULL,
        `name` varchar(100) NOT NULL,
        `description` text DEFAULT NULL,
        `image_url` varchar(255) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `status` enum('active','inactive') NOT NULL DEFAULT 'active',
        PRIMARY KEY (`id`),
        KEY `model_id` (`model_id`),
        CONSTRAINT `model_variations_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `product_models` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p>Tabela 'model_variations' criada.</p>";

    // Update orders table foreign keys
    $pdo->exec("ALTER TABLE orders DROP FOREIGN KEY IF EXISTS orders_ibfk_1");
    $pdo->exec("ALTER TABLE orders DROP FOREIGN KEY IF EXISTS orders_ibfk_2");
    $pdo->exec("ALTER TABLE orders DROP FOREIGN KEY IF EXISTS orders_ibfk_3");
    $pdo->exec("ALTER TABLE orders DROP FOREIGN KEY IF EXISTS orders_ibfk_4");

    $pdo->exec("ALTER TABLE orders 
        ADD CONSTRAINT orders_ibfk_2 FOREIGN KEY (model_id) REFERENCES product_models (id),
        ADD CONSTRAINT orders_ibfk_3 FOREIGN KEY (company_id) REFERENCES companies (id),
        ADD CONSTRAINT orders_ibfk_4 FOREIGN KEY (user_id) REFERENCES users (id)");
    echo "<p>Chaves estrangeiras da tabela 'orders' atualizadas.</p>";

    // Create sales_representatives table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sales_representatives` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `avatar_url` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p>Tabela 'sales_representatives' criada.</p>";

    echo "<h2>Atualização concluída com sucesso!</h2>";
    echo "<p>Você pode agora <a href='index.php'>acessar o sistema</a>.</p>";

} catch(PDOException $e) {
    die("<h2>ERRO: Não foi possível atualizar o banco de dados.</h2><p>" . $e->getMessage() . "</p>");
}
?>