<?php
// Start session for state management
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && $_GET['page'] != 'login') {
    header('Location: index.php?page=login');
    exit;
}

// Check if the database needs to be installed
$dbConfigFile = 'includes/config.php';
$dbInstalled = file_exists($dbConfigFile);

if ($dbInstalled) {
    // Include necessary files
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    require_once 'includes/header.php';
    
    // Determine which page to display
    $page = isset($_GET['page']) ? $_GET['page'] : 'home';
    
    // Load the appropriate page content
    switch ($page) {
        case 'login':
            include 'pages/login.php';
            break;
        case 'orders':
            include 'pages/order_listing.php';
            break;
        case 'new-order':
            include 'pages/order_form.php';
            break;
        case 'admin':
            // Check if user is admin
            if ($_SESSION['role'] === 'admin') {
                include 'pages/admin_panel.php';
            } else {
                header('Location: index.php');
            }
            break;
        default:
            include 'pages/home.php';
            break;
    }
    
    // Include footer
    require_once 'includes/footer.php';
} else {
    // Redirect to installation page
    header('Location: install.php');
    exit;
}
?>