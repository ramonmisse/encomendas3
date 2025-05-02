<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $id = (int)$_POST['id'];
    
    // Validate required field
    if (empty($id)) {
        // Set error message and redirect back
        $_SESSION['error'] = 'ID do pedido é obrigatório.';
        header('Location: ../index.php?page=home&tab=orders');
        exit;
    }
    
    try {
        // Get image URLs before deleting the order
        $stmt = $pdo->prepare("SELECT image_urls FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $imageUrlsJson = $stmt->fetchColumn();
        
        // Delete order from database
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete associated image files if they exist
        if ($imageUrlsJson) {
            $imageUrls = json_decode($imageUrlsJson, true);
            if (is_array($imageUrls)) {
                foreach ($imageUrls as $imageUrl) {
                    $filePath = '../' . $imageUrl;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
        }
        
        // Set success message
        $_SESSION['success'] = 'Pedido excluído com sucesso!';
    } catch (PDOException $e) {
        // Set error message
        $_SESSION['error'] = 'Erro ao excluir pedido: ' . $e->getMessage();
    }
}

// Redirect back to order listing
header('Location: ../index.php?page=home&tab=orders');
exit;
?>