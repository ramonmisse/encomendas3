
<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    die('Usuário não está logado');
}

if (!isset($_GET['id'])) {
    die('ID não fornecido');
}

$orderId = (int)$_GET['id'];
$order = getOrderById($pdo, $orderId);

if (!$order) {
    die('Pedido não encontrado');
}

// Buscar modelos para o select
$models = getProductModels($pdo)['data'];
?>

<form id="editOrderForm" action="actions/save_order.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
    
    <!-- Usuário que criou -->
    <div class="mb-3">
        <label class="form-label">Usuário que Criou</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
    </div>

    <!-- Cliente -->
    <div class="mb-3">
        <label for="client_name" class="form-label">Cliente</label>
        <input type="text" class="form-control" id="client_name" name="client_name" 
               value="<?php echo htmlspecialchars($order['client_name']); ?>" required>
    </div>

    <!-- Modelo -->
    <div class="mb-3">
        <label for="model_id" class="form-label">Modelo</label>
        <select class="form-select" id="model_id" name="model_id" required>
            <?php foreach ($models as $model): ?>
                <option value="<?php echo $model['id']; ?>" 
                        <?php echo $order['model_id'] == $model['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($model['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Data de Entrega -->
    <div class="mb-3">
        <label for="delivery_date" class="form-label">Data de Entrega</label>
        <input type="datetime-local" class="form-control" id="delivery_date" name="delivery_date" 
               value="<?php echo date('Y-m-d\TH:i', strtotime($order['delivery_date'])); ?>" required>
    </div>

    <!-- Tipo de Metal -->
    <div class="mb-3">
        <label for="metal_type" class="form-label">Tipo de Metal</label>
        <select class="form-select" id="metal_type" name="metal_type" required>
            <option value="gold" <?php echo $order['metal_type'] == 'gold' ? 'selected' : ''; ?>>Banho de Ouro</option>
            <option value="silver" <?php echo $order['metal_type'] == 'silver' ? 'selected' : ''; ?>>Banho de Prata</option>
            <option value="not_applicable" <?php echo $order['metal_type'] == 'not_applicable' ? 'selected' : ''; ?>>Não Aplicável</option>
        </select>
    </div>

    <!-- Status -->
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status" required>
            <option value="Em produção" <?php echo $order['status'] == 'Em produção' ? 'selected' : ''; ?>>Em produção</option>
            <option value="Gravado" <?php echo $order['status'] == 'Gravado' ? 'selected' : ''; ?>>Gravado</option>
            <option value="Separado" <?php echo $order['status'] == 'Separado' ? 'selected' : ''; ?>>Separado</option>
            <option value="Enviado" <?php echo $order['status'] == 'Enviado' ? 'selected' : ''; ?>>Enviado</option>
            <option value="Entregue" <?php echo $order['status'] == 'Entregue' ? 'selected' : ''; ?>>Entregue</option>
        </select>
    </div>

    <!-- Observações -->
    <div class="mb-3">
        <label for="notes" class="form-label">Observações</label>
        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
    </div>

    <!-- Imagens Existentes -->
    <?php if (isset($order['image_urls']) && !empty($order['image_urls'])): ?>
        <div class="mb-3">
            <label class="form-label">Imagens Existentes</label>
            <div class="row">
                <?php 
                $imageUrls = json_decode($order['image_urls'], true);
                if (is_array($imageUrls)):
                    foreach ($imageUrls as $index => $imageUrl): 
                ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" class="card-img-top" alt="Imagem do pedido">
                            <div class="card-body">
                                <a href="<?php echo htmlspecialchars($imageUrl); ?>" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Upload de Novas Imagens -->
    <div class="mb-3">
        <label for="new_images" class="form-label">Adicionar Novas Imagens</label>
        <input type="file" class="form-control" id="new_images" name="new_images[]" multiple accept="image/*">
        <div class="form-text">Você pode selecionar múltiplas imagens para adicionar às existentes.</div>
    </div>

    <div class="text-end mt-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
    </div>
</form>

<script>
// Validação do formulário
(function() {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>
