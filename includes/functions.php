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
        if (!$isGlobal && !empty($filters['company_id'])) {
            $where[] = "o.company_id = ?";
            $params[] = $filters['company_id'];
        } elseif (!$isGlobal) {
            $where[] = "o.company_id = ?";
            $params[] = $companyId;
        } elseif ($isGlobal && !empty($filters['company_id'])) {
            $where[] = "o.company_id = ?";
            $params[] = $filters['company_id'];
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
function getProductModels(PDO $pdo, string $search = '', int $page = 1, int $perPage = 1000): array
{
    try {
        $sql = "SELECT * FROM product_models";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE (name LIKE :search OR reference LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql .= " ORDER BY name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => count($data)
        ];
    } catch (PDOException $e) {
        error_log('Error in getProductModels: ' . $e->getMessage());
        return [
            'data' => [],
            'total' => 0
        ];
    }
}

function getAdminProductModels(PDO $pdo, string $search = '', int $page = 1, int $perPage = 10): array
{
    try {
        $sql = "SELECT * FROM product_models";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE (name LIKE :search OR reference LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        // Get total before adding limit
        $countStmt = $pdo->prepare($sql);
        $countStmt->execute($params);
        $total = $countStmt->rowCount();

        // Add pagination
        $sql .= " ORDER BY name LIMIT :limit OFFSET :offset";
        $params[':limit'] = $perPage;
        $params[':offset'] = ($page - 1) * $perPage;

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'pages' => ceil($total / $perPage),
            'current_page' => $page
        ];
    } catch (PDOException $e) {
        error_log('Error in getProductModels: ' . $e->getMessage());
        return [
            'data' => [],
            'total' => 0
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