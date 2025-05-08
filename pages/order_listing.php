<?php
// Get filter parameters
$filters = [
    'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : '',
    'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : '',
    'model_id' => isset($_GET['model_id']) ? $_GET['model_id'] : '',
    'status' => isset($_GET['status']) ? $_GET['status'] : '',
    'company_id' => ($_SESSION['role'] === 'admin' && isset($_GET['company_id'])) ? $_GET['company_id'] : $_SESSION['company_id']
];

// Get companies for admin filter
$companies = [];
if ($_SESSION['role'] === 'admin') {
    $companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();
}

// Get current page from URL
$currentPage = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;

// Fetch orders from database with filters and pagination
$result = getOrders($pdo, $filters, $currentPage, 20);
$orders = $result['data'];
?>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title h5 mb-0">Filtros</h2>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <input type="hidden" name="page" value="order_listing">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Data Inicial</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Data Final</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
            </div>
            <div class="col-md-4">
                <label for="model_id" class="form-label">Modelo</label>
                <select class="form-select" id="model_id" name="model_id">
                    <option value="">Todos os Modelos</option>
                    <?php
                    $models = getProductModels($pdo);
                    foreach ($models as $model) {
                        $selected = ($filters['model_id'] == $model['id']) ? 'selected' : '';
                        echo "<option value=\"{$model['id']}\" {$selected}>{$model['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos os Status</option>
                    <option value="Em produção" <?php echo ($filters['status'] == 'Em produção') ? 'selected' : ''; ?>>Em produção</option>
                    <option value="Gravado" <?php echo ($filters['status'] == 'Gravado') ? 'selected' : ''; ?>>Gravado</option>
                    <option value="Separado" <?php echo ($filters['status'] == 'Separado') ? 'selected' : ''; ?>>Separado</option>
                    <option value="Enviado" <?php echo ($filters['status'] == 'Enviado') ? 'selected' : ''; ?>>Enviado</option>
                    <option value="Entregue" <?php echo ($filters['status'] == 'Entregue') ? 'selected' : ''; ?>>Entregue</option>
                </select>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="col-md-3">
                <label for="company_id" class="form-label">Empresa</label>
                <select class="form-select" id="company_id" name="company_id">
                    <option value="">Todas as Empresas</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['id']; ?>" <?php echo ($filters['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="index.php?page=order_listing" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title h5 mb-0">Listagem de Pedidos</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Criado por</th>
                        <th>Cliente</th>
                        <th>Modelo</th>
                        <th>Tipo de Metal</th>
                        <th>Status</th>
                        <th>Data de Entrega</th>
                        <th>Imagem</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <p class="text-muted mb-0">Nenhum pedido encontrado. Crie um novo pedido para começar.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></td>
                                <td><?php echo isset($order['client']) ? htmlspecialchars($order['client']) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($order['model']); ?></td>
                                <td><?php echo htmlspecialchars($order['metal_type']); ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'Em produção' => 'bg-primary',
                                        'Gravado' => 'bg-info',
                                        'Separado' => 'bg-warning',
                                        'Enviado' => 'bg-success',
                                        'Entregue' => 'bg-secondary'
                                    ];
                                    $statusColor = isset($statusColors[$order['status']]) ? $statusColors[$order['status']] : 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $statusColor; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                                </td>
                                <td><?php echo formatDate($order['delivery_date']); ?></td>
                                <td>
                                    <?php if (isset($order['image_urls']) && !empty($order['image_urls'])): ?>
                                        <?php 
                                            $imageUrlsArray = json_decode($order['image_urls'], true);
                                            $firstImage = is_array($imageUrlsArray) && !empty($imageUrlsArray) ? $imageUrlsArray[0] : '';
                                        ?>
                                        <?php if (!empty($firstImage)): ?>
                                        <div class="hover-card">
                                            <button class="btn btn-sm btn-outline-secondary image-preview-link" data-image-url="<?php echo htmlspecialchars($firstImage); ?>">
                                                <i class="fas fa-image"></i>
                                            </button>
                                            <div class="hover-card-content">
                                                <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="Order reference" class="img-fluid rounded" onerror="this.onerror=null; this.src='assets/images/no-image.png'; this.alt='Image not found';">
                                            </div>
                                        </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sem imagem</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sem imagem</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <!-- View button with tooltip -->
                                        <div class="tooltip-wrapper">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-icon" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <span class="tooltip-content">Ver Pedido</span>
                                        </div>

                                        <!-- Edit button with tooltip -->
                                        <div class="tooltip-wrapper">
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" onclick="editOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <span class="tooltip-content">Editar Pedido</span>
                                        </div>

                                        <?php if (isset($order['image_urls']) && !empty($order['image_urls'])): ?>
                                            <?php 
                                                $imageUrlsArray = json_decode($order['image_urls'], true);
                                                $firstImage = is_array($imageUrlsArray) && !empty($imageUrlsArray) ? $imageUrlsArray[0] : '';
                                            ?>
                                            <?php if (!empty($firstImage)): ?>
                                            <!-- Download button with tooltip -->
                                            <div class="tooltip-wrapper">
                                                <a href="<?php echo htmlspecialchars($firstImage); ?>" download class="btn btn-sm btn-outline-info btn-icon">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <span class="tooltip-content">Baixar Imagem</span>
                                            </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- Delete button with tooltip - only for admin -->
                                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin'): ?>
                                        <div class="tooltip-wrapper">
                                            <button class="btn btn-sm btn-outline-danger btn-icon delete-btn" data-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <span class="tooltip-content">Excluir Pedido</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($result['pages'] > 1): ?>
        <div class="d-flex justify-content-center mt-3">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=order_listing&pg=<?php echo ($currentPage - 1); ?><?php echo isset($_GET['company_id']) ? '&company_id=' . htmlspecialchars($_GET['company_id']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                    <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=order_listing&pg=<?php echo $i; ?><?php echo isset($_GET['company_id']) ? '&company_id=' . htmlspecialchars($_GET['company_id']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $result['pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=order_listing&pg=<?php echo ($currentPage + 1); ?><?php echo isset($_GET['company_id']) ? '&company_id=' . htmlspecialchars($_GET['company_id']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePreviewModalLabel">Imagem de Referência</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" alt="Preview" class="img-fluid rounded">
            </div>
            <div class="modal-footer">
                <a id="downloadImageLink" href="" download class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Baixar Imagem
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderModalLabel">Detalhes do Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewOrderDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOrderModalLabel">Editar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editOrderForm">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este pedido? Esta ação não pode ser desfeita.</p>
                <form id="deleteForm" action="actions/delete_order.php" method="post">
                    <input type="hidden" name="id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="deleteForm" class="btn btn-danger">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewOrder(orderId) {
        fetch(`actions/get_order.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                const modalBody = document.getElementById('viewOrderDetails');
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Criado por:</strong> ${data.username}</p>
                            <p><strong>Cliente:</strong> ${data.client_name}</p>
                            <p><strong>Modelo:</strong> ${data.model_name}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tipo de Metal:</strong> ${data.metal_type}</p>
                            <p><strong>Data de Entrega:</strong> ${data.delivery_date}</p>
                        </div>
                    </div>
                    ${data.notes ? `<div class="mt-3"><strong>Observações:</strong><p>${data.notes}</p></div>` : ''}
                    ${data.image_urls ? `
                        <div class="mt-3">
                            <strong>Imagens:</strong>
                            <div class="row mt-2">
                                ${JSON.parse(data.image_urls).map(url => `
                                    <div class="col-md-4 mb-2">
                                        <img src="${url}" class="img-fluid rounded" alt="Imagem do pedido">
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                `;
                new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
            });
    }

    function editOrder(orderId) {
        const modalBody = document.getElementById('editOrderForm');
        fetch(`actions/load_order_form.php?id=${orderId}`)
            .then(response => response.text())
            .then(html => {
                modalBody.innerHTML = html;
                new bootstrap.Modal(document.getElementById('editOrderModal')).show();
            })
            .catch(error => {
                console.error('Error loading order form:', error);
                alert('Erro ao carregar o formulário de edição');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Image preview functionality
        const imagePreviewLinks = document.querySelectorAll('.image-preview-link');
        const previewImage = document.getElementById('previewImage');
        const downloadImageLink = document.getElementById('downloadImageLink');
        const imagePreviewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));

        imagePreviewLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const imageUrl = this.getAttribute('data-image-url');
                previewImage.src = imageUrl;
                downloadImageLink.href = imageUrl;
                imagePreviewModal.show();
            });
        });

        // No need for special handling of view and edit buttons - let them work with their native href behavior

        // Delete confirmation functionality
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const deleteForm = document.getElementById('deleteForm');
        const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-id');
                deleteForm.querySelector('input[name="id"]').value = orderId;
                deleteConfirmModal.show();
            });
        });

        // Tooltip hover functionality
        const tooltipWrappers = document.querySelectorAll('.tooltip-wrapper');

        tooltipWrappers.forEach(wrapper => {
            const tooltip = wrapper.querySelector('.tooltip-content');

            wrapper.addEventListener('mouseenter', function() {
                tooltip.style.visibility = 'visible';
                tooltip.style.opacity = '1';
            });

            wrapper.addEventListener('mouseleave', function() {
                tooltip.style.visibility = 'hidden';
                tooltip.style.opacity = '0';
            });
        });
    });
</script>