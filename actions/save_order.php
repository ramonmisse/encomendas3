
<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Print POST data
    error_log('POST data: ' . print_r($_POST, true));
    
    // Start transaction for data integrity
    $pdo->beginTransaction();
    
    // Get user and company data from session
    $userId = (int)$_SESSION['user_id'];
    $companyId = (int)$_SESSION['company_id'];
    
    // Validate and sanitize inputs
    $clientName = sanitizeInput($_POST['client_name']);
    $deliveryDate = sanitizeInput($_POST['delivery_date']);
    $deliveryTime = isset($_POST['delivery_time']) ? sanitizeInput($_POST['delivery_time']) : '00:00';
    $deliveryDateTime = $deliveryDate . ' ' . $deliveryTime;
    
    $modelId = (int)$_POST['model_id'];
    $metalType = sanitizeInput($_POST['metal_type']);
    $status = sanitizeInput($_POST['status'] ?? 'Em produção');
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // Validate required fields
    if (empty($userId) || empty($clientName) || empty($deliveryDate) || empty($modelId) || empty($metalType) || empty($companyId)) {
        $_SESSION['error'] = 'Todos os campos obrigatórios devem ser preenchidos.';
        header('Location: ../index.php?page=home&tab=new-order');
        exit;
    }
    
    // Process uploaded images
    $imageUrls = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $uploadDir = '../uploads/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777);
        }
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === 0) {
                $fileName = time() . '_' . basename($_FILES['images']['name'][$key]);
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmp_name, $filePath)) {
                    $imageUrls[] = 'uploads/' . $fileName;
                    chmod($filePath, 0644);
                }
            }
        }
    }
    
    $imageUrlsJson = !empty($imageUrls) ? json_encode($imageUrls) : null;
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update existing order
            $id = (int)$_POST['id'];
            
            // Verify if the order exists
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('Pedido não encontrado.');
            }
            
            // Get existing image URLs if no new images uploaded
            if (empty($imageUrls)) {
                $stmt = $pdo->prepare("SELECT image_urls FROM orders WHERE id = ?");
                $stmt->execute([$id]);
                $imageUrlsJson = $stmt->fetchColumn();
            }
            
            // Set PDO to handle UTF-8 properly
            $pdo->exec("SET NAMES utf8mb4");
            
            $stmt = $pdo->prepare("UPDATE orders SET 
                client_name = :client_name, 
                delivery_date = :delivery_date, 
                model_id = :model_id, 
                metal_type = :metal_type,
                status = :status,
                notes = :notes, 
                image_urls = :image_urls,
                updated_at = NOW()
                WHERE id = :id");
            
            $params = [
                ':client_name' => $clientName,
                ':delivery_date' => $deliveryDateTime,
                ':model_id' => $modelId,
                ':metal_type' => $metalType,
                ':status' => $status,
                ':notes' => $notes,
                ':image_urls' => $imageUrlsJson,
                ':id' => $id
            ];
            
            $stmt->execute($params);
            
            // Check if update was successful
            if ($stmt->rowCount() === 0) {
                throw new Exception('Não foi possível atualizar o pedido. Verifique os dados e tente novamente.');
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Pedido atualizado com sucesso!';
            header('Location: ../index.php?page=home&tab=orders');
            exit;
        } else {
            // Insert new order
            $stmt = $pdo->prepare("INSERT INTO orders 
                (user_id, company_id, client_name, delivery_date, model_id, metal_type, status, notes, image_urls, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $companyId, $clientName, $deliveryDateTime, $modelId, $metalType, $status, $notes, $imageUrlsJson]);
            
            $pdo->commit();
            $_SESSION['success'] = 'Pedido incluído com sucesso!';
            header('Location: ../index.php?page=home&tab=orders');
            exit;
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Erro ao salvar pedido: ' . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: ../index.php?page=home&tab=orders');
exit;
?>
