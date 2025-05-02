<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction for data integrity
    $pdo->beginTransaction();
    // Validate and sanitize input
    $id = (int)$_POST['id'];
    
    // Validate required field
    if (empty($id)) {
        // Set error message and redirect back
        $_SESSION['error'] = 'ID do modelo é obrigatório.';
        header('Location: ../index.php?page=home&tab=admin&admin_tab=models');
        exit;
    }
    
    try {
        // Check if model is used in any orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE model_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            // Model is in use, cannot delete
            $_SESSION['error'] = 'Este modelo não pode ser excluído porque está sendo usado em pedidos.';
        } else {
            // Delete model from database
            $stmt = $pdo->prepare("DELETE FROM product_models WHERE id = ?");
            $stmt->execute([$id]);
            
            // Commit transaction
            $pdo->commit();
            // Set success message
            $_SESSION['success'] = 'Modelo excluído com sucesso!';
        }
    } catch (PDOException $e) {
        // Rollback transaction
        $pdo->rollBack();
        // Set error message
        $_SESSION['error'] = 'Erro ao excluir modelo: ' . $e->getMessage();
    }
}

// Redirect back to admin panel
header('Location: ../index.php?page=home&tab=admin&admin_tab=models');
exit;
?>