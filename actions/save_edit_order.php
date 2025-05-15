
<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    
    try {
        // Validação do ID do pedido
        $id = (int)$_POST['id'];
        if (!$id) {
            throw new Exception('ID do pedido inválido');
        }

        // Busca pedido atual
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

        // Sanitização dos dados
        $clientName = sanitizeInput($_POST['client_name'] ?? '');
        $modelId = (int)($_POST['model_id'] ?? 0);
        $deliveryDate = sanitizeInput($_POST['delivery_date'] ?? '');
        $deliveryTime = sanitizeInput($_POST['delivery_time'] ?? '00:00');
        $deliveryDateTime = $deliveryDate . ' ' . $deliveryTime;
        $metalType = sanitizeInput($_POST['metal_type'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');

        // Atualiza o pedido
        $sql = "UPDATE orders SET 
                client_name = ?,
                model_id = ?,
                delivery_date = ?,
                metal_type = ?,
                status = ?,
                notes = ?,
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $clientName,
            $modelId,
            $deliveryDateTime,
            $metalType,
            $status,
            $notes,
            $id
        ]);

        // Gerencia upload de novas imagens
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = '../uploads/';
            $imageUrls = [];
            
            // Mantém imagens existentes
            if (!empty($order['image_urls'])) {
                $imageUrls = json_decode($order['image_urls'], true) ?: [];
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['images']['error'][$key] === 0) {
                    $fileName = time() . '_' . $_FILES['images']['name'][$key];
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($tmpName, $filePath)) {
                        $imageUrls[] = 'uploads/' . $fileName;
                    }
                }
            }

            // Atualiza URLs das imagens
            if (!empty($imageUrls)) {
                $stmt = $pdo->prepare("UPDATE orders SET image_urls = ? WHERE id = ?");
                $stmt->execute([json_encode($imageUrls), $id]);
            }
        }

        $pdo->commit();
        $_SESSION['success'] = 'Pedido atualizado com sucesso!';
        
        echo "<script>
                alert('Pedido atualizado com sucesso!');
                window.location.href = '../index.php?page=home&tab=orders';
              </script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Erro ao atualizar pedido: ' . $e->getMessage();
        header('Location: ../index.php?page=home&tab=orders');
        exit;
    }
}

header('Location: ../index.php?page=home&tab=orders');
exit;
