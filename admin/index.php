<?php
// admin/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/currency.php';

Auth::requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Get statistics
$stats = [];

// Users stats
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stats['active_users'] = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
$stats['new_users_today'] = $stmt->fetch()['total'];

// Listings stats
$stmt = $conn->query("SELECT COUNT(*) as total FROM listings");
$stats['total_listings'] = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM listings WHERE status = 'active'");
$stats['active_listings'] = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM listings WHERE DATE(created_at) = CURDATE()");
$stats['new_listings_today'] = $stmt->fetch()['total'];

// Featured stats
$stmt = $conn->query("SELECT COUNT(*) as total FROM featured_listings WHERE is_active = 1");
$stats['featured_listings'] = $stmt->fetch()['total'];

// Messages stats
$stmt = $conn->query("SELECT COUNT(*) as total FROM messages");
$stats['total_messages'] = $stmt->fetch()['total'];

// Reports stats
$stmt = $conn->query("SELECT COUNT(*) as total FROM reports WHERE status = 'pending'");
$stats['pending_reports'] = $stmt->fetch()['total'];

// Views stats
$stmt = $conn->query("SELECT SUM(views) as total FROM listings");
$stats['total_views'] = $stmt->fetch()['total'] ?? 0;

// Categories stats
$stmt = $conn->query("SELECT COUNT(*) as total FROM categories WHERE status = 'active'");
$stats['total_categories'] = $stmt->fetch()['total'];

// Recent activity
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

$stmt = $conn->query("
    SELECT l.*, u.username, c.name as category_name, cur.symbol as currency_symbol
    FROM listings l 
    JOIN users u ON l.user_id = u.id 
    JOIN categories c ON l.category_id = c.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    ORDER BY l.created_at DESC LIMIT 5
");
$recent_listings = $stmt->fetchAll();

$stmt = $conn->query("
    SELECT m.*, s.username as sender, r.username as receiver
    FROM messages m
    JOIN users s ON m.sender_id = s.id
    JOIN users r ON m.receiver_id = r.id
    ORDER BY m.created_at DESC LIMIT 5
");
$recent_messages = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2" 
                         style="width: 65px; height: 65px; background: var(--primary-gradient); color: white; font-size: 1.5rem; font-weight: 700;">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <h6 class="mb-0 text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></h6>
                    <small class="text-white-50">Administrator</small>
                </div>
                <hr class="border-secondary">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="/marketnearme/admin/">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/listings.php">
                            <i class="fas fa-list"></i> Listings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/categories.php">
                            <i class="fas fa-tags"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/featured.php">
                            <i class="fas fa-star"></i> Featured
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/reports.php">
                            <i class="fas fa-flag"></i> Reports
                            <?php if ($stats['pending_reports'] > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $stats['pending_reports']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/messages.php">
                            <i class="fas fa-envelope"></i> Messages
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <hr class="border-secondary">
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View Site
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="/marketnearme/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Dashboard</h1>
                    <p class="text-muted mb-0">Welcome back! Here's what's happening.</p>
                </div>
                <div>
                    <span class="text-muted"><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>
            
            <?php if ($stats['pending_reports'] > 0): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-flag"></i> 
                <strong><?php echo $stats['pending_reports']; ?> pending report(s)</strong> need your attention.
                <a href="/marketnearme/admin/reports.php" class="alert-link">Review now</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-2">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Users</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_users']); ?></h3>
                                </div>
                                <i class="fas fa-users fa-2x opacity-50"></i>
                            </div>
                            <small class="text-white-50"><?php echo $stats['active_users']; ?> active</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Listings</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_listings']); ?></h3>
                                </div>
                                <i class="fas fa-list fa-2x opacity-50"></i>
                            </div>
                            <small class="text-white-50"><?php echo $stats['active_listings']; ?> active</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Featured</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['featured_listings']); ?></h3>
                                </div>
                                <i class="fas fa-star fa-2x opacity-50"></i>
                            </div>
                            <small class="text-white-50">active</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Messages</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_messages']); ?></h3>
                                </div>
                                <i class="fas fa-envelope fa-2x opacity-50"></i>
                            </div>
                            <small class="text-white-50">total</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Reports</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['pending_reports']); ?></h3>
                                </div>
                                <i class="fas fa-flag fa-2x opacity-50"></i>
                            </div>
                            <small class="text-white-50">pending</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card bg-secondary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-white-50">Views</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_views']); ?></h3>
                                </div>
                                <i class="fas fa-eye fa-2x opacity-50"></i>
                            </div>
                            <small class="text-white-50">total</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent Listings -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between">
                            <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Recent Listings</h5>
                            <a href="/marketnearme/admin/listings.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Seller</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_listings as $listing): ?>
                                    <tr>
                                        <td><a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>"><?php echo htmlspecialchars(substr($listing['title'], 0, 30)); ?>...</a></td>
                                        <td><?php echo htmlspecialchars($listing['username']); ?></td>
                                        <td><?php echo htmlspecialchars($listing['currency_symbol'] ?? '$'); ?><?php echo number_format($listing['price'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo $listing['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo $listing['status']; ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between">
                            <h5 class="mb-0"><i class="fas fa-users me-2 text-success"></i>Recent Users</h5>
                            <a href="/marketnearme/admin/users.php" class="btn btn-sm btn-outline-success">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br><small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($user['email']); ?></small></td>
                                        <td><span class="badge bg-<?php echo $user['role'] === 'admin' ? 'warning' : 'info'; ?>"><?php echo $user['role']; ?></span></td>
                                        <td><small><?php echo date('M d', strtotime($user['created_at'])); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="/marketnearme/admin/featured.php" class="btn btn-warning w-100"><i class="fas fa-star"></i> Manage Featured</a>
                        </div>
                        <div class="col-md-3">
                            <a href="/marketnearme/admin/categories.php" class="btn btn-info text-white w-100"><i class="fas fa-tags"></i> Manage Categories</a>
                        </div>
                        <div class="col-md-3">
                            <a href="/marketnearme/admin/reports.php" class="btn btn-danger w-100"><i class="fas fa-flag"></i> Review Reports</a>
                        </div>
                        <div class="col-md-3">
                            <a href="/marketnearme/admin/users.php" class="btn btn-primary w-100"><i class="fas fa-users"></i> Manage Users</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    background: #1e293b !important;
    min-height: calc(100vh - 56px);
    padding-top: 0;
}
.sidebar .nav-link {
    color: #94a3b8;
    padding: 0.6rem 1rem;
    border-radius: 6px;
    margin-bottom: 2px;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}
.sidebar .nav-link.active {
    background: var(--primary-gradient);
    color: white !important;
}
.sidebar .nav-link i {
    width: 20px;
    text-align: center;
    margin-right: 8px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>