<?php
// admin/listings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$success = $_SESSION['admin_success'] ?? '';
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM listings WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['admin_success'] = 'Listing deleted.';
    } elseif ($_GET['action'] === 'toggle') {
        $stmt = $conn->prepare("UPDATE listings SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['admin_success'] = 'Listing status toggled.';
    }
    header("Location: /marketnearme/admin/listings.php");
    exit();
}

// Search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';

$sql = "SELECT l.*, u.username, c.name as category_name, cur.symbol as currency_symbol FROM listings l JOIN users u ON l.user_id = u.id JOIN categories c ON l.category_id = c.id LEFT JOIN currencies cur ON l.currency_id = cur.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (l.title LIKE :search OR u.username LIKE :search2)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
}
if ($status_filter) {
    $sql .= " AND l.status = :status";
    $params[':status'] = $status_filter;
}
if ($category_filter) {
    $sql .= " AND l.category_id = :cat";
    $params[':cat'] = $category_filter;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 50";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$categories = DBHelper::getCategories();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/marketnearme/admin/listings.php"><i class="fas fa-list"></i> Listings</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/featured.php"><i class="fas fa-star"></i> Featured</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/reports.php"><i class="fas fa-flag"></i> Reports</a></li>
                </ul>
            </div>
        </nav>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h1 class="h3 mb-4">Manage Listings</h1>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Search -->
            <form class="row g-2 mb-4">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="flagged" <?php echo $status_filter === 'flagged' ? 'selected' : ''; ?>>Flagged</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $l): ?>
                        <tr>
                            <td>#<?php echo $l['id']; ?></td>
                            <td><a href="/marketnearme/product.php?id=<?php echo $l['id']; ?>" target="_blank"><?php echo htmlspecialchars(substr($l['title'], 0, 40)); ?></a></td>
                            <td><?php echo htmlspecialchars($l['username']); ?></td>
                            <td><?php echo htmlspecialchars($l['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($l['currency_symbol'] ?? '$'); ?><?php echo number_format($l['price'], 2); ?></td>
                            <td><span class="badge bg-<?php echo $l['status'] === 'active' ? 'success' : ($l['status'] === 'flagged' ? 'danger' : 'secondary'); ?>"><?php echo $l['status']; ?></span></td>
                            <td><?php echo $l['views']; ?></td>
                            <td><?php echo date('M d', strtotime($l['created_at'])); ?></td>
                            <td>
                                <a href="?action=toggle&id=<?php echo $l['id']; ?>" class="btn btn-sm btn-outline-info">Toggle</a>
                                <a href="?action=delete&id=<?php echo $l['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar { background: #1e293b !important; min-height: calc(100vh - 56px); }
.sidebar .nav-link { color: #94a3b8; padding: 0.6rem 1rem; border-radius: 6px; margin-bottom: 2px; font-size: 0.9rem; }
.sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
.sidebar .nav-link.active { background: var(--primary-gradient); color: white !important; }
.sidebar .nav-link i { width: 20px; text-align: center; margin-right: 8px; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>