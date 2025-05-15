
<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();

    try {
        // Dados do pedido
        $id = (int)$_POST['id'];
        $clientName = sanitizeInput($_POST['client_name'] ?? '');
        $modelId = (int)($_POST['model_id'] ?? 0);
        $deliveryDate = sanitizeInput($_POST['delivery_date'] ?? '');
        $deliveryTime = '00:00'; // Hora padrão
        $metalType = sanitizeInput($_POST['metal_type'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'Em produção');
        $notes = sanitizeInput($_POST['notes'] ?? '');

        // Validação básica
        if ($id <= 0 || empty($clientName) || $modelId <= 0 || empty($deliveryDate)) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
        }

        // Valida formato da data
        $date = DateTime::createFromFormat('Y-m-d', $deliveryDate);
        if (!$date || $date->format('Y-m-d') !== $deliveryDate) {
            throw new Exception('Data inválida');
        }

        // Busca o pedido atual
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }

        // Verifica permissão
        if ($_SESSION['role'] !== 'admin' && $order['company_id'] != $_SESSION['company_id']) {
            throw new Exception('Sem permissão para editar este pedido');
        }

        // Processa novas imagens
        $uploadedFiles = [];
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = '../uploads/';
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = time() . '_' . $_FILES['images']['name'][$key];
                    $diskPath = $uploadDir . $fileName;
                    $webPath = 'uploads/' . $fileName;
                    
                    if (move_uploaded_file($tmpName, $diskPath)) {
                        $uploadedFiles[] = $webPath;
                    }
                }
            }
            
            if (!empty($uploadedFiles)) {
                $currentImages = json_decode($order['image_urls'], true) ?: [];
                $allImages = array_merge($currentImages, $uploadedFiles);
                $imageUrls = json_encode($allImages);
            }
        }

        // Atualiza o pedido
        $sql = "UPDATE orders SET 
                client_name = ?,
                model_id = ?,
                delivery_date = ?,
                delivery_time = ?,
                metal_type = ?,
                status = ?,
                notes = ?" .
                (!empty($uploadedFiles) ? ", image_urls = ?" : "") .
                " WHERE id = ?";

        $params = [
            $clientName,
            $modelId,
            $deliveryDate,
            $deliveryTime,
            $metalType,
            $status,
            $notes
        ];

        if (!empty($uploadedFiles)) {
            $params[] = $imageUrls;
        }
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);

        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Erro SQL: {$errorInfo[0]} - {$errorInfo[2]}");
        }

        $pdo->commit();
        $_SESSION['success'] = 'Pedido atualizado com sucesso!';
        header('Location: ../index.php?page=orders');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Erro ao atualizar pedido: ' . $e->getMessage();
        header('Location: ../index.php?page=orders');
        exit;
    }
}

header('Location: ../index.php?page=orders');
exit;
