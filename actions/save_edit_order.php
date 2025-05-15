
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
        $metalType = sanitizeInput($_POST['metal_type'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');

        // Validação básica
        if ($id <= 0 || empty($clientName) || $modelId <= 0 || empty($deliveryDate) || empty($metalType)) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
        }

        // Busca o pedido atual
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception('Pedido não encontrado');
        }

        // Verifica permissão - admin pode editar qualquer pedido
        if ($_SESSION['role'] !== 'admin' && $order['company_id'] != $_SESSION['company_id']) {
            throw new Exception('Sem permissão para editar este pedido');
        }

        // Atualiza o pedido
        $sql = "UPDATE orders SET 
                client_name = ?,
                model_id = ?,
                delivery_date = ?,
                metal_type = ?,
                status = ?,
                notes = ?
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $clientName,
            $modelId,
            $deliveryDate,
            $metalType,
            $status,
            $notes,
            $id
        ]);

        if (!$success) {
            throw new Exception('Erro ao atualizar pedido no banco de dados');
        }

        // Gerencia upload de novas imagens
        if (!empty($_FILES['images']['name'][0])) {
            $uploadedFiles = [];
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = time() . '_' . $_FILES['images']['name'][$key];
                    $filepath = '../uploads/' . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $uploadedFiles[] = $filename;
                    }
                }
            }
            
            if (!empty($uploadedFiles)) {
                // Combina as novas imagens com as existentes
                $currentImages = json_decode($order['image_urls'], true) ?: [];
                $allImages = array_merge($currentImages, $uploadedFiles);
                
                $stmt = $pdo->prepare("UPDATE orders SET image_urls = ? WHERE id = ?");
                $stmt->execute([json_encode($allImages), $id]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso!']);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        exit;
    }
}

header('Location: ../index.php?page=orders');
exit;
