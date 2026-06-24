<?php
// includes/header.php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$db = new Database();
$conn = $db->getConnection();
$categories = DBHelper::getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MarketNearMe - Your local marketplace for buying, selling, and trading">
    <meta name="csrf-token" content="<?php echo Security::generateCSRFToken(); ?>">
    <title>MarketNearMe - Local Marketplace</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/marketnearme/assets/css/style.css">
    
    <style>
        /* Critical CSS inline for faster loading */
        .navbar-brand { font-weight: bold; color: #28a745 !important; }
        .listing-card { transition: transform 0.3s, box-shadow 0.3s; }
        .listing-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/marketnearme/">
                <i class="fas fa-store"></i> MarketNearMe
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-th-list"></i> Categories
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $category): ?>
                            <li>
                                <a class="dropdown-item" href="/marketnearme/search.php?category=<?php echo $category['id']; ?>">
                                    <i class="fas <?php echo htmlspecialchars($category['icon'] ?? 'fa-tag'); ?>"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                    <span class="badge bg-secondary float-end"><?php echo $category['listing_count']; ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/search.php">
                            <i class="fas fa-search"></i> Browse All
                        </a>
                    </li>
                    <?php if (Auth::isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/post-ad.php">
                            <i class="fas fa-plus-circle"></i> Post Free Ad
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <form class="d-flex me-2" action="/marketnearme/search.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Search listings..." aria-label="Search">
                        <button class="btn btn-success" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <ul class="navbar-nav">
                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/marketnearme/profile.php"><i class="fas fa-id-card"></i> My Profile</a></li>
                                <li><a class="dropdown-item" href="/marketnearme/my-listings.php"><i class="fas fa-list"></i> My Listings</a></li>
                                <li><a class="dropdown-item" href="/marketnearme/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if (Auth::isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/marketnearme/admin/"><i class="fas fa-cog"></i> Admin Panel</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="/marketnearme/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/marketnearme/login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-success btn-sm ms-2" href="/marketnearme/register.php">
                                <i class="fas fa-user-plus"></i> Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <main class="py-4">