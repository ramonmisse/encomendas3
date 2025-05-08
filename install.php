<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'reve_controle'); // Change to your database username
define('DB_PASS', 'PfHMnqmM4#yci@HJ'); // Change to your database password
define('DB_NAME', 'reve_links_controle');

// Create database and tables
try {
    echo "<h2>Configurando banco de dados...</h2>";

    // Connect to MySQL server without selecting a database
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("DROP DATABASE IF EXISTS `" . DB_NAME . "`");
    $pdo->exec("CREATE DATABASE `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>Banco de dados criado.</p>";

    // Select the database
    $pdo->exec("USE `" . DB_NAME . "`");

    // Create companies table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `companies` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p>Tabela 'companies' criada.</p>";

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` enum('superadmin','admin','user') NOT NULL DEFAULT 'user',
        `company_id` int(11) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        KEY `company_id` (`company_id`),
        CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p>Tabela 'users' criada.</p>";

    // Create product_models table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `product_models` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `image_url` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p>Tabela 'product_models' criada.</p>";

    // Create orders table (updated to use user_id instead of sales_rep_id)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `client_name` varchar(100) NOT NULL,
        `delivery_date` date NOT NULL,
        `model_id` int(11) NOT NULL,
        `metal_type` varchar(20) NOT NULL,
        `status` varchar(20) DEFAULT 'Em produção',
        `notes` text DEFAULT NULL,
        `image_urls` text DEFAULT NULL,
        `company_id` int(11) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `model_id` (`model_id`),
        KEY `company_id` (`company_id`),
        CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
        CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`model_id`) REFERENCES `product_models` (`id`),
        CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<p>Tabela 'orders' criada.</p>";

    // Create default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute(['admin', $adminPassword]);
    echo "<p>Usuário admin criado (usuário: admin, senha: admin123)</p>";

    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
        chmod('uploads', 0777);
        echo "<p>Diretório 'uploads' criado.</p>";
    }

    echo "<h2>Instalação concluída com sucesso!</h2>";
    echo "<p>Você pode agora <a href='index.php'>acessar o sistema</a>.</p>";

} catch(PDOException $e) {
    die("<h2>ERRO: Não foi possível configurar o banco de dados.</h2><p>" . $e->getMessage() . "</p>");
}
?>