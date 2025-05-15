<?php
/**
 * Get all orders from the database
 * 
 * @param PDO $pdo Database connection
 * @param array $filters Optional filters (start_date, end_date, model_id)
 * @return array Array of orders
 */
function getOrders(PDO $pdo, array $filters = [], int $page = 1, int $perPage = 20): array
{
    try {
        // Determine session values
        $role      = $_SESSION['role'] ?? null;
        $companyId = isset($_SESSION['company_id']) ? (int) $_SESSION['company_id'] : null;
        $isGlobal  = ($role === 'admin' && $companyId === 0);

        $where  = [];
        $params = [];

        // Apply company filter for non-global admins
        if (! $isGlobal) {
            $where[]  = "o.company_id = ?";
            $params[] = $companyId;
        }

        // Date filters
        if (!empty($filters['start_date'])) {
            $where[]  = "o.delivery_date >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[]  = "o.delivery_date <= ?";
            $params[] = $filters['end_date'];
        }

        // Model filter
        if (!empty($filters['model_id'])) {
            $where[]  = "o.model_id = ?";
            $params[] = $filters['model_id'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where[]  = "o.status = ?";
            $params[] = $filters['status'];
        }

        // Build base SQL
        $sql = <<<SQL
SELECT
    o.*,
    m.name       AS model,
    m.reference  AS reference,
    u.username   AS user,
    o.client_name AS client,
    c.name       AS company_name
FROM orders o
JOIN product_models m ON o.model_id = m.id
JOIN users u          ON o.user_id = u.id
JOIN companies c      ON o.company_id = c.id
SQL;
        // Append WHERE
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        // Order, pagination
        $sql .= ' ORDER BY o.created_at DESC';
        $offset = ($page - 1) * $perPage;
        $sql   .= " LIMIT {$perPage} OFFSET {$offset}";

        // Count total
        $countSql = 'SELECT COUNT(*) FROM orders o';
        if ($where) {
            $countSql .= ' WHERE ' . implode(' AND ', $where);
        }
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch data
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'         => $data,
            'total'        => $total,
            'pages'        => (int) ceil($total / $perPage),
            'current_page' => $page,
        ];
    } catch (PDOException $e) {
        error_log('Error in getOrders: ' . $e->getMessage());
        return [
            'data'         => [],
            'total'        => 0,
            'pages'        => 0,
            'current_page' => $page,
        ];
    }
}

/**
 * Get a single order by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $id Order ID
 * @return array|false Order data or false if not found
 */
function getOrderById(PDO $pdo, int $orderId)
{
    // Session and permission
    $role      = $_SESSION['role'] ?? null;
    $companyId = isset($_SESSION['company_id']) ? (int) $_SESSION['company_id'] : null;
    $isGlobal  = ($role === 'admin' && $companyId === 0);

    if ($isGlobal) {
        $sql  = 'SELECT * FROM orders WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
    } else {
        $sql  = 'SELECT * FROM orders WHERE id = ? AND company_id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $companyId]);
    }

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all product models
 * 
 * @param PDO $pdo Database connection
 * @return array Array of product models
 */
function getProductModels(PDO $pdo, string $search = '', int $page = 1, int $perPage = 20): array
{
    // Garante que o PDO lance exceções em erros de SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Normaliza valores de página e itens por página
    $page    = max(1, $page);
    $perPage = max(1, $perPage);
    $offset  = ($page - 1) * $perPage;

    // Monta cláusula WHERE e parâmetros
    $whereClauses = [];
    $params       = [];

    if ($search !== '') {
        $whereClauses[]     = '(name LIKE :search OR reference LIKE :search)';
        $params[':search'] = "%{$search}%";
    }

    $whereSql = $whereClauses
        ? 'WHERE ' . implode(' AND ', $whereClauses)
        : '';

    try {
        // 1) Total de registros
        $countSql  = "SELECT COUNT(*) FROM product_models {$whereSql}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // 2) Buscar dados paginados
        // Injecta diretamente LIMIT e OFFSET (já validados como inteiros)
        $dataSql = "
            SELECT *
              FROM product_models
            {$whereSql}
            ORDER BY name
            LIMIT {$perPage}
            OFFSET {$offset}
        ";
        $dataStmt = $pdo->prepare($dataSql);
        $dataStmt->execute($params);
        $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3) Monta resposta
        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'pages'        => (int) ceil($total / $perPage),
        ];
    } catch (PDOException $e) {
        // Em caso de erro, retorne estrutura vazia (ou lance exceção, se preferir)
        return [
            'data'         => [],
            'total'        => 0,
            'per_page'     => $perPage,
            'current_page' => $page,
            'pages'        => 0,
            'error'        => $e->getMessage(), // opcional, para debug
        ];
    }
}


/**
 * Add a new product model to the database
 * 
 * @param PDO $pdo Database connection
 * @param array $data Model data (name, image_url, description)
 * @return array Result with status and message
 */
function addProductModel($pdo, $data) {
    // Validate required fields
    if (empty($data['name']) || empty($data['image_url'])) {
        return [
            'status' => 'error',
            'message' => 'Nome e URL da imagem são obrigatórios.'
        ];
    }

    try {
        // Check if model with same name already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_models WHERE name = ?");
        $stmt->execute([$data['name']]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            return [
                'status' => 'error',
                'message' => 'Um modelo com este nome já existe.'
            ];
        }

        // Insert new model
        $stmt = $pdo->prepare("INSERT INTO product_models (name, image_url, description) VALUES (?, ?, ?)");
        $stmt->execute([$data['name'], $data['image_url'], $data['description'] ?? '']);

        return [
            'status' => 'success',
            'message' => 'Modelo adicionado com sucesso!',
            'id' => $pdo->lastInsertId()
        ];
    } catch (PDOException $e) {
        error_log('Error adding product model: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Erro ao adicionar modelo: ' . $e->getMessage()
        ];
    }
}


/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param bool $includeTime Whether to include time in the formatted date
 * @return string Formatted date
 */
function formatDate($date, $includeTime = true) {
    if ($includeTime) {
        return date("d/m/Y H:i", strtotime($date));
    } else {
        return date("d/m/Y", strtotime($date));
    }
}

/**
 * Sanitize input data
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate phone number format
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    // Allow empty phone
    if (empty($phone)) {
        return true;
    }

    // Basic phone validation - adjust regex as needed for your country format
    return preg_match('/^\+?[0-9\(\)\s\-]{8,20}$/', $phone);
}

/**
 * Validate URL format
 * 
 * @param string $url URL to validate
 * @return bool True if valid, false otherwise
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Get all sales representatives from the database
 * 
 * @param PDO $pdo Database connection
 * @return array Array of sales representatives
 */
function getSalesReps($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM sales_representatives ORDER BY name");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log('Error fetching sales representatives: ' . $e->getMessage());
        return [];
    }
}
?>