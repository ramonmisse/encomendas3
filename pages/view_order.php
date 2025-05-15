<?php
// Get order ID from URL parameter
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch order details
$order = getOrderById($pdo, $orderId);

// If order not found, redirect to order listing
if (!$order) {
    $_SESSION['error'] = 'Pedido não encontrado.';
    header('Location: index.php?page=home&tab=orders');
    exit;
}

// Get sales representative details
$stmt = $pdo->prepare("SELECT name FROM sales_representatives WHERE id = ?");
$stmt->execute([$order['sales_representative_id']]);
$salesRep = $stmt->fetchColumn();

// Get model details
$stmt = $pdo->prepare("SELECT name, image_url FROM product_models WHERE id = ?");
$stmt->execute([$order['model_id']]);
$model = $stmt->fetch();

// Format metal type for display
$metalTypes = [
    'gold' => 'Ouro',
    'silver' => 'Prata',
    'not_applicable' => 'Não Aplicável'
];
$metalType = isset($metalTypes[$order['metal_type']]) ? $metalTypes[$order['metal_type']] : $order['metal_type'];

// Process image URLs
$images = [];
if (!empty($order['image_urls'])) {
    $images = json_decode($order['image_urls'], true) ?: [];
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title h5 mb-0">Visualizar Pedido</h2>
        <a href="index.php?page=home&tab=orders" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar para Listagem
        </a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h3 class="h6 fw-bold">Informações do Pedido</h3>
                <table class="table table-sm">
                    <tr>
                        <th style="width: 40%">ID do Pedido:</th>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                    </tr>
                    <tr>
                        <th>Representante:</th>
                        <td><?php echo htmlspecialchars($salesRep); ?></td>
                    </tr>
                    <tr>
                        <th>Cliente:</th>
                        <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Data de Criação:</th>
                        <td><?php echo formatDate($order['created_at']); ?></td>
                    </tr>
                    <tr>
                        <th>Data de Entrega:</th>
                        <td><?php echo formatDate($order['delivery_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Tipo de Metal:</th>
                        <td><?php echo htmlspecialchars($metalType); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h3 class="h6 fw-bold">Modelo do Produto</h3>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <img src="<?php echo htmlspecialchars($model['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($model['name']); ?>" 
                             class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <div>
                        <h4 class="h6 mb-1"><?php echo htmlspecialchars($model['name']); ?></h4>
                        <p class="small text-muted mb-0">Ref: <?php echo htmlspecialchars($model['reference']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($order['notes'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="h6 fw-bold">Observações</h3>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($images)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="h6 fw-bold">Imagens de Referência</h3>
                <div class="row">
                    <?php foreach ($images as $image): ?>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top" alt="Imagem de referência">
                            <div class="card-footer p-2 text-center">
                                <a href="<?php echo htmlspecialchars($image); ?>" download class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-1"></i> Baixar
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="index.php?page=home&tab=orders" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
            <a href="index.php?page=order_form&id=<?php echo $order['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Editar Pedido
            </a>
        </div>
    </div>
</div>