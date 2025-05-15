<?php
// Get active admin tab from query string or set default to 'models'
$adminTab = isset($_GET['admin_tab']) ? $_GET['admin_tab'] : 'models';

// All users have admin access

// Get search parameters and current page
$modelSearch = isset($_GET['model_search']) ? $_GET['model_search'] : '';
$currentPage = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;

// Get product models with search and pagination
$result = getProductModels($pdo, $modelSearch, $currentPage);
$models = $result['data'];

// Get companies and users
$companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();
$users = $pdo->query("SELECT u.*, c.name as company_name FROM users u LEFT JOIN companies c ON u.company_id = c.id ORDER BY username")->fetchAll();

// Add company
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_company'])) {
    $stmt = $pdo->prepare("INSERT INTO companies (name, email, phone) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone']]);
    header('Location: index.php?page=admin_panel');
    exit;
}

// Add user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$_POST['username'], $hashedPassword]);
    header('Location: index.php?page=admin_panel');
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title h5 mb-0">Painel de Administração</h2>
    </div>
    <div class="card-body">
        <!-- Admin Tabs -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $adminTab == 'models' ? 'active' : ''; ?>" 
                   href="index.php?page=admin&admin_tab=models" role="tab">Modelos de Produtos</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo $adminTab == 'users' ? 'active' : ''; ?>" 
                   href="index.php?page=admin&admin_tab=users" role="tab">Usuários</a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Models Tab -->
            <div class="tab-pane fade <?php echo $adminTab == 'models' ? 'show active' : ''; ?>" id="models" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 mb-0">Modelos de Produtos</h3>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModelModal">
                        <i class="fas fa-plus-circle me-1"></i> Adicionar Modelo
                    </button>
                </div>

                <div class="mb-3">
                    <form class="row g-3" method="get">
                        <input type="hidden" name="page" value="admin">
                        <input type="hidden" name="admin_tab" value="models">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="model_search" value="<?php echo htmlspecialchars($modelSearch); ?>" 
                                       placeholder="Buscar por nome ou referência...">
                                <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                                <?php if (!empty($modelSearch)): ?>
                                    <a href="?page=admin&admin_tab=models" class="btn btn-outline-secondary">Limpar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Visualização</th>
                                <th>Nome</th>
                                <th>Referência</th>
                                <th>Descrição</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($models)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <p class="text-muted mb-0">Nenhum modelo cadastrado. Adicione um modelo para começar.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($models as $model): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($model['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($model['name']); ?>" 
                                                     class="image-thumbnail me-2">
                                            </div>
                                        </td>
                                        <td class="fw-medium"><?php echo htmlspecialchars($model['name']); ?></td>
                                        <td><?php echo htmlspecialchars($model['reference']); ?></td>
                                        <td><?php echo htmlspecialchars($model['description']); ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-icon edit-model-btn" 
                                                        data-id="<?php echo $model['id']; ?>" 
                                                        data-name="<?php echo htmlspecialchars($model['name']); ?>" 
                                                        data-image-url="<?php echo htmlspecialchars($model['image_url']); ?>" 
                                                        data-description="<?php echo htmlspecialchars($model['description']); ?>" 
                                                        data-bs-toggle="modal" data-bs-target="#editModelModal">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-icon delete-model-btn" 
                                                        data-id="<?php echo $model['id']; ?>" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModelModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($result['pages'] > 1): ?>
                <nav aria-label="Navegação da página" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($result['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=admin&admin_tab=models&pg=<?php echo ($result['current_page'] - 1); ?><?php echo !empty($modelSearch) ? '&model_search=' . urlencode($modelSearch) : ''; ?>">Anterior</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                        <li class="page-item <?php echo $i === $result['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=admin&admin_tab=models&pg=<?php echo $i; ?><?php echo !empty($modelSearch) ? '&model_search=' . urlencode($modelSearch) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($result['current_page'] < $result['pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=admin&admin_tab=models&pg=<?php echo ($result['current_page'] + 1); ?><?php echo !empty($modelSearch) ? '&model_search=' . urlencode($modelSearch) : ''; ?>">Próximo</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        <!-- Users Tab -->
            <div class="tab-pane fade <?php echo $adminTab == 'users' ? 'show active' : ''; ?>" id="users" role="tabpanel">
                <!-- Companies Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gerenciar Empresas</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                            <i class="fas fa-plus-circle me-1"></i> Adicionar Empresa
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($company['name']); ?></td>
                                        <td><?php echo htmlspecialchars($company['email']); ?></td>
                                        <td><?php echo htmlspecialchars($company['phone']); ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-icon edit-company-btn"
                                                        data-id="<?php echo $company['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($company['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($company['email']); ?>"
                                                        data-phone="<?php echo htmlspecialchars($company['phone']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editCompanyModal">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Users Section -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gerenciar Usuários</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus-circle me-1"></i> Adicionar Usuário
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Empresa</th>
                                        <th>Tipo</th>
                                        <th>Data de Criação</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['company_name'] ?? 'Nenhuma'); ?></td>
                                        <td><?php echo $user['role'] == 'admin' ? 'Administrador' : 'Usuário'; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-icon edit-user-btn"
                                                        data-id="<?php echo $user['id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                        data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                        data-company-id="<?php echo htmlspecialchars($user['company_id'] ?? ''); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-icon delete-user-btn" 
                                                        data-id="<?php echo $user['id']; ?>" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Add Model Modal -->
<div class="modal fade" id="addModelModal" tabindex="-1" aria-labelledby="addModelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModelModalLabel">Adicionar Novo Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/add_model.php" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modelName" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="modelName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="modelReference" class="form-label">Referência</label>
                        <input type="text" class="form-control" id="modelReference" name="reference" required>
                    </div>
                    <div class="mb-3">
                        <label for="modelImageUrl" class="form-label">URL da Imagem</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="modelImageUrl" name="image_url" required>
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="modelDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" id="modelDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar Modelo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Model Modal -->
<div class="modal fade" id="editModelModal" tabindex="-1" aria-labelledby="editModelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModelModalLabel">Editar Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/edit_model.php" method="post">
                <input type="hidden" name="id" id="editModelId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editModelName" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="editModelName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editModelReference" class="form-label">Referência</label>
                        <input type="text" class="form-control" id="editModelReference" name="reference" required>
                    </div>
                    <div class="mb-3">
                        <label for="editModelImageUrl" class="form-label">URL da Imagem</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="editModelImageUrl" name="image_url" required>
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-upload"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editModelDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" id="editModelDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar Modelo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Model Modal -->
<div class="modal fade" id="deleteModelModal" tabindex="-1" aria-labelledby="deleteModelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModelModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este modelo? Esta ação não pode ser desfeita.</p>
                <form id="deleteModelForm" action="actions/delete_model.php" method="post">
                    <input type="hidden" name="id" id="deleteModelId">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="deleteModelForm" class="btn btn-danger">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script>
// Script to handle modal data for editing models
document.addEventListener('DOMContentLoaded', function() {
    // Edit model button click
    const editModelBtns = document.querySelectorAll('.edit-model-btn');
    editModelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const imageUrl = this.getAttribute('data-image-url');
            const description = this.getAttribute('data-description');

            document.getElementById('editModelId').value = id;
            document.getElementById('editModelName').value = name;
            document.getElementById('editModelImageUrl').value = imageUrl;
            document.getElementById('editModelDescription').value = description;
        });
    });

    // Delete model button click
    const deleteModelBtns = document.querySelectorAll('.delete-model-btn');
    deleteModelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('deleteModelId').value = id;
        });
    });
});
</script>

<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="add_company" value="1">
                    <div class="mb-3">
                        <label class="form-label">Nome da Empresa</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Company Modal -->
<div class="modal fade" id="editCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="actions/edit_company.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editCompanyId">
                    <div class="mb-3">
                        <label class="form-label">Nome da Empresa</label>
                        <input type="text" name="name" id="editCompanyName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editCompanyEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="phone" id="editCompanyPhone" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="add_user" value="1">
                    <div class="mb-3">
                        <label class="form-label">Usuário</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Usuário</label>
                        <select name="role" class="form-select" required>
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Empresa</label>
                        <select name="company_id" class="form-select">
                            <option value="">Nenhuma</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="actions/edit_user.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label">Usuário</label>
                        <input type="text" name="username" id="editUsername" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nova Senha</label>
                        <input type="password" name="password" class="form-control" placeholder="Deixe em branco para manter a senha atual">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Usuário</label>
                        <select name="role" id="editUserRole" class="form-select" required>
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Empresa</label>
                        <select name="company_id" id="editUserCompany" class="form-select">
                            <option value="">Nenhuma</option>
                            <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Company edit button handler
    document.querySelectorAll('.edit-company-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editCompanyId').value = this.dataset.id;
            document.getElementById('editCompanyName').value = this.dataset.name;
            document.getElementById('editCompanyEmail').value = this.dataset.email;
            document.getElementById('editCompanyPhone').value = this.dataset.phone;
        });
    });

    // User edit button handler
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editUserId').value = this.dataset.id;
            document.getElementById('editUsername').value = this.dataset.username;
            document.getElementById('editUserRole').value = this.dataset.role;
            document.getElementById('editUserCompany').value = this.dataset.companyId;
        });
    });
});
</script>
