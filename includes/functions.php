<?php
/**
 * Get all orders from the database
 * 
 * @param PDO $pdo Database connection
 * @param array $filters Optional filters (start_date, end_date, model_id)
 * @return array Array of orders
 */
function getOrders($pdo, $filters = []) {
    try {
        $where = [];
        $params = [];

        // Add company filter if user is not admin
        if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin' && isset($_SESSION['company_id'])) {
            $where[] = "o.company_id = ?";
            $params[] = $_SESSION['company_id'];
        }

        // Base query
        $sql = "SELECT o.*, m.name as model, u.username, o.client_name as client, c.name as company_name 
               FROM orders o 
               JOIN product_models m ON o.model_id = m.id 
               JOIN users u ON o.user_id = u.id
               JOIN companies c ON o.company_id = c.id";

        // Add date filters
        if (!empty($filters['start_date'])) {
            $where[] = "o.delivery_date >= ?"; 
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "o.delivery_date <= ?"; 
            $params[] = $filters['end_date'];
        }

        // Add model filter
        if (!empty($filters['model_id'])) {
            $where[] = "o.model_id = ?"; 
            $params[] = $filters['model_id'];
        }

        // Add status filter
        if (!empty($filters['status'])) {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }

        // Add WHERE clause if we have filters
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // Add order by
        $sql .= " ORDER BY o.created_at DESC";

        // Prepare and execute the statement
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch(PDOException $e) {
        // For development, show error. For production, log error and show generic message
        error_log('Error fetching orders: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single order by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $id Order ID
 * @return array|false Order data or false if not found
 */
function getOrderById($pdo, $id) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   m.name as model_name
            FROM orders o
            JOIN product_models m ON o.model_id = m.id
            WHERE o.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}


/**
 * Get all product models
 * 
 * @param PDO $pdo Database connection
 * @return array Array of product models
 */
function getProductModels($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM product_models ORDER BY name");
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        return [];
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