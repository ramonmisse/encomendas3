<?php
// Get active tab from query string or set default to 'orders'
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';
?>

<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <!-- Tabs navigation -->
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <ul class="nav nav-tabs" id="orderTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $activeTab == 'orders' ? 'active' : ''; ?>" 
                       href="index.php?page=home&tab=orders" role="tab">Listagem de Pedidos</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $activeTab == 'new-order' ? 'active' : ''; ?>" 
                       href="index.php?page=home&tab=new-order" role="tab">Criar Pedido</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $activeTab == 'admin' ? 'active' : ''; ?>" 
                       href="index.php?page=home&tab=admin" role="tab">Painel de Administração</a>
                </li>
            </ul>

            <?php if ($activeTab == 'orders'): ?>
                <a href="index.php?page=home&tab=new-order" class="btn btn-primary">
                    Criar Novo Pedido
                </a>
            <?php endif; ?>
        </div>

        <!-- Tab content -->
        <div class="tab-content p-3" id="orderTabsContent">
            <?php 
            // Load the appropriate tab content based on the active tab
            switch ($activeTab) {
                case 'orders':
                    include 'order_listing.php';
                    break;
                case 'new-order':
                    include 'order_form.php';
                    break;
                case 'admin':
                    include 'admin_panel.php';
                    break;
                default:
                    include 'order_listing.php';
                    break;
            }
            ?>
        </div>
    </div>
</div>