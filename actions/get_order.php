<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type header
header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID não fornecido']);
    exit;
}

$orderId = (int)$_GET['id'];

try {
    // Get order details including joins
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            u.username as user,
            m.name as model,
            m.reference as reference,
            o.client_name as client,
            o.metal_type,
            o.delivery_date,
            o.status,
            o.image_urls,
            o.notes
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN product_models m ON o.model_id = m.id
        WHERE o.id = ?
    ");

    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['error' => 'Pedido não encontrado']);
        exit;
    }

    // Format dates
    if ($order['delivery_date']) {
        $order['delivery_date'] = date('d/m/Y', strtotime($order['delivery_date']));
    }

    echo json_encode($order);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar pedido', 'details' => $e->getMessage()]);
}