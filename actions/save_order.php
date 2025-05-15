<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Log incoming data
error_log('POST data: ' . print_r($_POST, true));
error_log('SESSION data: ' . print_r($_SESSION, true));

// Pega company_id como inteiro
$companyId = isset($_SESSION['company_id']) ? (int)$_SESSION['company_id'] : null;

// Detecta se é admin global (company_id = 0)
$isGlobalAdmin = (
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'admin' &&
    $companyId === 0
);

// Debug
error_log('SESSION: ' . print_r($_SESSION, true));

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
    $errors = [];
    if ($userId <= 0) $errors[] = 'Usuário inválido';
    if ($modelId <= 0) $errors[] = 'Selecione um modelo';
    if (empty($clientName)) $errors[] = 'Nome do cliente é obrigatório';
    if (empty($deliveryDate)) $errors[] = 'Data de entrega é obrigatória';
    if (empty($metalType)) $errors[] = 'Tipo de metal é obrigatório';

    if (!empty($errors)) {
        error_log('Validation failed: ' . json_encode([
            'userId'       => $userId,
            'clientName'   => $clientName,
            'deliveryDate' => $deliveryDate,
            'modelId'      => $modelId,
            'metalType'    => $metalType,
            'companyId'    => $companyId,
            'isGlobalAdmin'=> $isGlobalAdmin
        ]));
        $_SESSION['error'] = 'Campos obrigatórios: ' . implode(', ', $errors);
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

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                if ($stmt->rowCount() > 0) {
                    $pdo->commit();
                    $_SESSION['success'] = 'Pedido atualizado com sucesso!';
                    echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso!']);
                    exit;
                } else {
                    throw new Exception('Nenhuma alteração foi feita no pedido.');
                }
            } catch (PDOException $e) {
                error_log('SQL Error: ' . $e->getMessage());
                throw new Exception('Erro ao atualizar pedido: ' . $e->getMessage());
            }
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