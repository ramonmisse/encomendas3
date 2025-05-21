<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
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

$salesReps = getSalesReps($pdo);
$models = getProductModels($pdo);
?>

<form id="editOrderForm" action="actions/save_edit_order.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $order['id']; ?>">

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Representante</label>
            <input type="text" class="form-control" value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" readonly>
            <input type="hidden" name="sales_representative_id" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
        </div>

        <div class="col-md-6">
            <label for="client_name" class="form-label">Cliente</label>
            <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo htmlspecialchars($order['client_name']); ?>" required>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="model_id" class="form-label">Modelo</label>
            <select class="form-select" id="model_id" name="model_id" required>
                <?php if (!empty($models)): ?>
                    <?php foreach ($models['data'] as $model): ?>
                        <option value="<?php echo isset($model['id']) ? $model['id'] : ''; ?>" 
                            <?php echo (isset($order['model_id']) && isset($model['id']) && $order['model_id'] == $model['id']) ? 'selected' : ''; ?>>
                            <?php echo isset($model['reference']) ? htmlspecialchars($model['reference']) : ''; ?> - 
                            <?php echo isset($model['name']) ? htmlspecialchars($model['name']) : ''; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="metal_type" class="form-label">Tipo de Metal</label>
            <select class="form-select" id="metal_type" name="metal_type" required>
                <option value="gold" <?php echo $order['metal_type'] == 'gold' ? 'selected' : ''; ?>>Banho de Ouro</option>
                <option value="silver" <?php echo $order['metal_type'] == 'silver' ? 'selected' : ''; ?>>Banho de Prata</option>
                <option value="not_applicable" <?php echo $order['metal_type'] == 'not_applicable' ? 'selected' : ''; ?>>Não Aplicável</option>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="delivery_date" class="form-label">Data de Entrega</label>
            <input type="date" class="form-control" id="delivery_date" name="delivery_date" value="<?php echo date('Y-m-d', strtotime($order['delivery_date'])); ?>" required>
        </div>
        <div class="col-md-6">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="Em produção" <?php echo $order['status'] == 'Em produção' ? 'selected' : ''; ?>>Em produção</option>
                <option value="Gravado" <?php echo $order['status'] == 'Gravado' ? 'selected' : ''; ?>>Gravado</option>
                <option value="Separado" <?php echo $order['status'] == 'Separado' ? 'selected' : ''; ?>>Separado</option>
                <option value="Enviado" <?php echo $order['status'] == 'Enviado' ? 'selected' : ''; ?>>Enviado</option>
                <option value="Entregue" <?php echo $order['status'] == 'Entregue' ? 'selected' : ''; ?>>Entregue</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label for="notes" class="form-label">Observações</label>
        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
    </div>

    <div class="mb-3">
        <label for="images" class="form-label">Novas Imagens (opcional)</label>
        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
    </div>

    <div id="existingImages" class="row">
        <?php if (!empty($order['image_urls'])): ?>
            <?php $images = json_decode($order['image_urls'], true); ?>
            <?php if (is_array($images)): ?>
                <?php foreach ($images as $index => $image): ?>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top" alt="Preview">
                            <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($image); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="text-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
    </div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const editOrderModal = document.getElementById('editOrderModal');

    if (editOrderModal) {
      editOrderModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const orderId = button.getAttribute('data-order-id');

        if (orderId) {
          loadEditOrderForm(orderId);
        }
      });
    }

    function loadEditOrderForm(orderId) {
      const modalBody = editOrderModal.querySelector('.modal-body');
      modalBody.innerHTML = '<p>Carregando...</p>';

      fetch(`get_edit_order_form.php?id=${orderId}`)
        .then(response => response.text())
        .then(data => {
          modalBody.innerHTML = data;

          // Adicionar handler para remoção de imagens
          modalBody.querySelectorAll('.remove-image').forEach(button => {
            button.addEventListener('click', function() {
              this.closest('.col-6').remove();
            });
          });
        })
        .catch(error => {
          console.error('Error loading order form:', error);
          alert('Erro ao carregar o formulário de edição');
        });
    }

    
  });
</script>