
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    
    // Get user and company info
    $userId = (int) $_SESSION['user_id'];
    $companyId = isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null;

    // Sanitize inputs
    $clientName = sanitizeInput($_POST['client_name'] ?? '');
    $deliveryDate = sanitizeInput($_POST['delivery_date'] ?? '');
    $deliveryTime = sanitizeInput($_POST['delivery_time'] ?? '00:00');
    $deliveryDateTime = $deliveryDate . ' ' . $deliveryTime;
    $modelId = (int) ($_POST['model_id'] ?? 0);
    $metalType = sanitizeInput($_POST['metal_type'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'Em produção');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    // Handle image uploads
    $imageUrls = [];
    $uploadErrors = [];
    
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = '../uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: " . $uploadDir);
                $uploadErrors[] = "Failed to create upload directory";
            }
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = time() . '_' . basename($_FILES['images']['name'][$key]);
                $targetFile = $uploadDir . $fileName;
                
                // Validate file type
                $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    error_log("Invalid file type: " . $imageFileType);
                    $uploadErrors[] = "Invalid file type for " . $_FILES['images']['name'][$key];
                    continue;
                }
                
                // Attempt to move uploaded file
                if (move_uploaded_file($tmp_name, $targetFile)) {
                    $imageUrls[] = 'uploads/' . $fileName;
                } else {
                    error_log("Failed to move uploaded file: " . $_FILES['images']['name'][$key]);
                    error_log("Upload error code: " . $_FILES['images']['error'][$key]);
                    error_log("Temporary file: " . $tmp_name);
                    error_log("Target file: " . $targetFile);
                    $uploadErrors[] = "Failed to upload " . $_FILES['images']['name'][$key];
                }
            } else {
                error_log("Upload error for file " . $_FILES['images']['name'][$key] . ": " . $_FILES['images']['error'][$key]);
                $uploadErrors[] = "Error uploading " . $_FILES['images']['name'][$key];
            }
        }
    }

    // Basic validation
    $errors = [];
    if ($userId <= 0) $errors[] = 'Usuário inválido';
    if ($modelId <= 0) $errors[] = 'Selecione um modelo';
    if (empty($clientName)) $errors[] = 'Nome do cliente é obrigatório';
    if (empty($deliveryDate)) $errors[] = 'Data de entrega é obrigatória';
    if (empty($metalType)) $errors[] = 'Tipo de metal é obrigatório';

    $errors = array_merge($errors, $uploadErrors);

    if (!empty($errors)) {
        error_log("Order validation errors: " . implode(", ", $errors));
        $_SESSION['error'] = 'Erros: ' . implode(', ', $errors);
        header('Location: ../index.php?page=home&tab=new-order');
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO orders
                (user_id, company_id, client_name, delivery_date, model_id, metal_type, status, notes, image_urls, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $imageUrlsJson = !empty($imageUrls) ? json_encode($imageUrls) : null;

        $stmt->execute([
            $userId,
            $companyId,
            $clientName,
            $deliveryDateTime,
            $modelId,
            $metalType,
            $status,
            $notes,
            $imageUrlsJson
        ]);

        $pdo->commit();
        $_SESSION['success'] = 'Pedido incluído com sucesso!';
        header('Location: ../index.php?page=home&tab=orders');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Database error in save_order.php: " . $e->getMessage());
        $_SESSION['error'] = "Erro ao salvar pedido: " . $e->getMessage();
        header('Location: ../index.php?page=home&tab=new-order');
        exit;
    }
}

header('Location: ../index.php?page=home&tab=orders');
exit;
