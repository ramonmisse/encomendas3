<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// No permission checks needed

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST data: ' . print_r($_POST, true));
    $pdo->beginTransaction();

    $userId = (int) $_SESSION['user_id'];

    // Sanitização
    $clientName       = sanitizeInput($_POST['client_name'] ?? '');
    $deliveryDate     = sanitizeInput($_POST['delivery_date'] ?? '');
    $deliveryTime     = sanitizeInput($_POST['delivery_time']  ?? '00:00');
    $deliveryDateTime = $deliveryDate . ' ' . $deliveryTime;
    $modelId          = (int) ($_POST['model_id'] ?? 0);
    $metalType        = sanitizeInput($_POST['metal_type'] ?? '');
    $status           = sanitizeInput($_POST['status'] ?? 'Em produção');
    $notes            = sanitizeInput($_POST['notes']  ?? '');

    // Validação básica (sem checar $companyId aqui)
    if (
        $userId <= 0             ||
        $modelId <= 0            ||
        empty($clientName)       ||
        empty($deliveryDate)     ||
        empty($metalType)
    ) {
        error_log('Validation failed: ' . json_encode([
            'userId'       => $userId,
            'clientName'   => $clientName,
            'deliveryDate' => $deliveryDate,
            'modelId'      => $modelId,
            'metalType'    => $metalType,
            'companyId'    => $companyId,
            'isGlobalAdmin'=> $isGlobalAdmin
        ]));
        $_SESSION['error'] = 'Todos os campos obrigatórios devem ser preenchidos.';
        header('Location: ../index.php?page=home&tab=new-order');
        exit;
    }

    // (upload de imagens idem...)

    try {
        // Se for UPDATE
        if (!empty($_POST['id'])) {
            $id = (int) $_POST['id'];

            // Busca dados atuais
            $stmt = $pdo->prepare("SELECT company_id, image_urls FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                throw new Exception('Pedido não encontrado.');
            }
            $orderCompanyId = (int) $row['company_id'];

            // Autorização: só bloqueia se NÃO for global e company diferente
            if (! $isGlobalAdmin && $orderCompanyId !== $companyId) {
                throw new Exception('Você não tem permissão para editar este pedido.');
            }

            // Monta UPDATE
            if ($isGlobalAdmin) {
                $sql = "UPDATE orders SET
                            client_name   = ?,
                            delivery_date = ?,
                            model_id      = ?,
                            metal_type    = ?,
                            status        = ?,
                            notes         = ?,
                            image_urls    = ?
                        WHERE id = ?";
                $params = [
                    $clientName,
                    $deliveryDateTime,
                    $modelId,
                    $metalType,
                    $status,
                    $notes,
                    $row['image_urls'] ?? null,
                    $id
                ];
            } else {
                $sql = "UPDATE orders SET
                            client_name   = ?,
                            delivery_date = ?,
                            model_id      = ?,
                            metal_type    = ?,
                            status        = ?,
                            notes         = ?,
                            image_urls    = ?
                        WHERE id = ? AND company_id = ?";
                $params = [
                    $clientName,
                    $deliveryDateTime,
                    $modelId,
                    $metalType,
                    $status,
                    $notes,
                    $row['image_urls'] ?? null,
                    $id,
                    $companyId
                ];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $pdo->commit();
            $_SESSION['success'] = 'Pedido atualizado com sucesso!';
            echo "<script>
                    alert('Pedido atualizado com sucesso!');
                    window.location.href = '../index.php?page=home&tab=orders';
                  </script>";
            exit;
        }

        // Se for INSERT (idem antes)
        $stmt = $pdo->prepare("
            INSERT INTO orders
              (user_id, company_id, client_name, delivery_date, model_id, metal_type, status, notes, image_urls, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $companyId,
            $clientName,
            $deliveryDateTime,
            $modelId,
            $metalType,
            $status,
            $notes,
            null  // ou suas URLs
        ]);

        $pdo->commit();
        $_SESSION['success'] = 'Pedido incluído com sucesso!';
        echo "<script>
                alert('Pedido incluído com sucesso!');
                window.location.href = '../index.php?page=home&tab=orders';
              </script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: ../index.php?page=home&tab=orders');
exit;
