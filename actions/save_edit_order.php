<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $pdo->beginTransaction();

    // Get user and company info
    $userId = (int) $_SESSION['user_id'];
    $companyId = isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null;
    $isGlobalAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && $companyId === 0);

    // Sanitize inputs
    $id = (int) $_POST['id'];
    $clientName = sanitizeInput($_POST['client_name'] ?? '');
    $deliveryDate = sanitizeInput($_POST['delivery_date'] ?? '');
    $modelId = (int) ($_POST['model_id'] ?? 0);
    $metalType = sanitizeInput($_POST['metal_type'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'Em produção');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    try {
        // Get current order data
        $stmt = $pdo->prepare("SELECT company_id, image_urls FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception('Pedido não encontrado.');
        }

        $orderCompanyId = (int) $row['company_id'];
        $existingImages = json_decode($row['image_urls'] ?? '[]', true) ?: [];

        // Authorization check
        if (!$isGlobalAdmin && $orderCompanyId !== $companyId) {
            throw new Exception('Você não tem permissão para editar este pedido.');
        }

        // Handle image uploads
        $uploadErrors = [];
        $newImages = [];

        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = '../uploads/';

            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Falha ao criar diretório de upload");
                }
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = time() . '_' . basename($_FILES['images']['name'][$key]);
                    $targetFile = $uploadDir . $fileName;

                    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $uploadErrors[] = "Tipo de arquivo inválido para " . $_FILES['images']['name'][$key];
                        continue;
                    }

                    if (move_uploaded_file($tmp_name, $targetFile)) {
                        $newImages[] = 'uploads/' . $fileName;
                    } else {
                        $uploadErrors[] = "Falha ao fazer upload de " . $_FILES['images']['name'][$key];
                    }
                }
            }
        }

        // Manter todas as imagens existentes
        $allImages = array_merge($existingImages, $newImages);
        $imageUrlsJson = !empty($allImages) ? json_encode($allImages) : null;

        // Update order
        $sql = "UPDATE orders SET 
                client_name = ?,
                delivery_date = ?,
                model_id = ?,
                metal_type = ?,
                status = ?,
                notes = ?,
                image_urls = ?
                WHERE id = ?" . (!$isGlobalAdmin ? " AND company_id = ?" : "");

        $params = [
            $clientName,
            $deliveryDate,
            $modelId,
            $metalType,
            $status,
            $notes,
            $imageUrlsJson,
            $id
        ];

        if (!$isGlobalAdmin) {
            $params[] = $companyId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Verificar se houve alterações em qualquer aspecto do pedido
        $changes = $stmt->rowCount() > 0 || !empty($newImages) || count($existingImages) !== count($keptImages);
        
        if ($changes) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso!']);
        } else if (!empty($uploadErrors)) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Pedido salvo, mas com erros no upload: ' . implode(', ', $uploadErrors)]);
        } else {
            $pdo->commit(); // Commit mesmo sem alterações para garantir consistência
            echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso!']);
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error in save_edit_order.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
exit;