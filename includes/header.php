<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gerenciamento de Pedidos de Joias</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="bg-white border-bottom shadow-sm">
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 fw-bold text-primary">SmartJoias - Encomendas de Personalizados DiLima Joias</h1>
                <div class="d-flex gap-3">
                    <button class="btn btn-outline-secondary btn-sm rounded-circle">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm rounded-circle">
                        <i class="fas fa-cog"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm rounded-circle">
                        <i class="fas fa-user"></i>
                    </button>
                </div>
            </div>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="d-flex align-items-center">
            <span class="me-3">Olá, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="actions/logout.php" class="btn btn-outline-light btn-sm">Sair</a>
        </div>
        <?php endif; ?>
    </div>
</header>

    <!-- Main Content -->
    <main class="container py-4">
    <!-- Bootstrap JS (optional, but often used for certain components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts -->
    <script src="/assets/js/scripts.js"></script>
</body>
</html>