
<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID não fornecido']);
    exit;
}

$orderId = (int)$_GET['id'];

// Get order details including joins
$stmt = $pdo->prepare("
    SELECT 
        o.*,
        u.username as user,
        m.name as model,
        m.reference as reference,
        o.client_name,
        o.metal_type,
        o.delivery_date,
        o.status,
        o.image_urls,
        o.notes
    FROM orders o
    LEFT JOIN users u ON o.sales_representative_id = u.id
    LEFT JOIN product_models m ON o.model_id = m.id
    WHERE o.id = ?
");

$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido não encontrado']);
    exit;
}

// Format dates
if ($order['delivery_date']) {
    $order['delivery_date'] = date('d/m/Y', strtotime($order['delivery_date']));
}

echo json_encode($order);
