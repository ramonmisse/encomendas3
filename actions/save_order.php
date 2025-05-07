<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();

    try {
        $userId = (int)$_SESSION['user_id'];
        $companyId = (int)$_SESSION['company_id'];

        $clientName = trim($_POST['client_name']);
        $deliveryDate = trim($_POST['delivery_date']);
        $deliveryTime = isset($_POST['delivery_time']) ? trim($_POST['delivery_time']) : '00:00';
        $deliveryDateTime = $deliveryDate . ' ' . $deliveryTime;

        $modelId = (int)$_POST['model_id'];
        $metalType = trim($_POST['metal_type']);
        $status = trim($_POST['status'] ?? 'Em produção');
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        if (empty($clientName) || empty($deliveryDate) || empty($modelId) || empty($metalType)) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
        }

        $imageUrls = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $uploadDir = '../uploads/';

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === 0) {
                    $fileName = time() . '_' . basename($_FILES['images']['name'][$key]);
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmp_name, $filePath)) {
                        $imageUrls[] = 'uploads/' . $fileName;
                    }
                }
            }
        }

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];

            if (empty($imageUrls)) {
                $stmt = $pdo->prepare("SELECT image_urls FROM orders WHERE id = ?");
                $stmt->execute([$id]);
                $imageUrlsJson = $stmt->fetchColumn();
            } else {
                $imageUrlsJson = json_encode($imageUrls);
            }

            $stmt = $pdo->prepare("
                UPDATE orders 
                SET client_name = ?, 
                    delivery_date = ?, 
                    model_id = ?, 
                    metal_type = ?,
                    status = ?,
                    notes = ?, 
                    image_urls = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $result = $stmt->execute([
                $clientName,
                $deliveryDateTime,
                $modelId,
                $metalType,
                $status,
                $notes,
                $imageUrlsJson,
                $id
            ]);

            if (!$result) {
                throw new Exception('Falha ao atualizar o pedido.');
            }

            $pdo->commit();
            $_SESSION['success'] = 'Pedido atualizado com sucesso!';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO orders 
                (user_id, company_id, client_name, delivery_date, model_id, metal_type, status, notes, image_urls, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $result = $stmt->execute([
                $userId,
                $companyId,
                $clientName,
                $deliveryDateTime,
                $modelId,
                $metalType,
                $status,
                $notes,
                !empty($imageUrls) ? json_encode($imageUrls) : null
            ]);

            if (!$result) {
                throw new Exception('Falha ao criar o pedido.');
            }

            $pdo->commit();
            $_SESSION['success'] = 'Pedido criado com sucesso!';
        }

        header('Location: ../index.php?page=home&tab=orders');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header('Location: ../index.php?page=home&tab=orders');
        exit;
    }
}

header('Location: ../index.php?page=home&tab=orders');
exit;
?>