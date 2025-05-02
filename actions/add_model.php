<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction for data integrity
    $pdo->beginTransaction();
    
    try {
        // Validate and sanitize inputs
        $modelData = [
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'image_url' => sanitizeInput($_POST['image_url'] ?? ''),
            'description' => sanitizeInput($_POST['description'] ?? '')
        ];
        
        // Validate URL format if provided
        if (!empty($modelData['image_url']) && !validateUrl($modelData['image_url'])) {
            throw new Exception('URL da imagem inválida. Por favor, forneça uma URL válida.');
        }
        
        // Add model using the function
        $result = addProductModel($pdo, $modelData);
        
        if ($result['status'] === 'success') {
            // Commit transaction
            $pdo->commit();
            $_SESSION['success'] = $result['message'];
        } else {
            // Rollback transaction
            $pdo->rollBack();
            $_SESSION['error'] = $result['message'];
        }
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        // Set error message
        $_SESSION['error'] = 'Erro ao adicionar modelo: ' . $e->getMessage();
        error_log('Error in add_model.php: ' . $e->getMessage());
    }
}

// Redirect back to admin panel
header('Location: ../index.php?page=home&tab=admin&admin_tab=models');
exit;
?>