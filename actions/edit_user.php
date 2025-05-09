
<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] === 'admin') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $company_id = $_POST['company_id'] ?: null;
    
    // Start with base query
    $query = "UPDATE users SET username = ?, role = ?, company_id = ?";
    $params = [$username, $role, $company_id];
    
    // If password is provided, update it
    if (!empty($_POST['password'])) {
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $query .= ", password = ?";
        $params[] = $hashedPassword;
    }
    
    // Add WHERE clause and id parameter
    $query .= " WHERE id = ?";
    $params[] = $id;
    
    $stmt = $pdo->prepare($query);
    if ($stmt->execute($params)) {
        header('Location: ../index.php?page=admin&admin_tab=users&success=1');
    } else {
        header('Location: ../index.php?page=admin&admin_tab=users&error=1');
    }
} else {
    header('Location: ../index.php');
}
exit;
