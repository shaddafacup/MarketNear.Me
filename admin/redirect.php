<?php
// admin/redirect.php
// This file redirects /admin to /admin/index.php properly

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    $_SESSION['error_message'] = 'Please login to access the admin panel.';
    header("Location: /marketnearme/login.php?redirect=admin");
    exit();
}

// Check if user is admin
if (!Auth::isAdmin()) {
    Security::logSecurityEvent('admin_access_denied', 'Non-admin user attempted to access admin area');
    $_SESSION['error_message'] = 'Access denied. You do not have admin privileges.';
    header("Location: /marketnearme/index.php");
    exit();
}

// If admin, redirect to dashboard
header("Location: /marketnearme/admin/index.php");
exit();
?>