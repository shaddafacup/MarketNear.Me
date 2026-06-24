<?php
// admin/reports.php
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
unset($_SESSION['admin_success']);

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'resolve') {
        $stmt = $conn->prepare("UPDATE reports SET status = 'resolved' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['admin_success'] = 'Report resolved.';
    } elseif ($_GET['action'] === 'dismiss') {
        $stmt = $conn->prepare("UPDATE reports SET status = 'dismissed' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['admin_success'] = 'Report dismissed.';
    }
    header("Location: /marketnearme/admin/reports.php");
    exit();
}

$stmt = $conn->query("
    SELECT r.*, u.username as reporter, l.title as listing_title, l.status as listing_status
    FROM reports r
    JOIN users u ON r.reporter_id = u.id
    JOIN listings l ON r.listing_id = l.id
    ORDER BY r.status = 'pending' DESC, r.created_at DESC
");
$reports = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/listings.php"><i class="fas fa-list"></i> Listings</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/featured.php"><i class="fas fa-star"></i> Featured</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/marketnearme/admin/reports.php"><i class="fas fa-flag"></i> Reports</a></li>
                </ul>
            </div>
        </nav>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h1 class="h3 mb-4">Reports</h1>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Listing</th>
                            <th>Reported By</th>
                            <th>Reason</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                        <tr class="<?php echo $r['status'] === 'pending' ? 'table-warning' : ''; ?>">
                            <td>#<?php echo $r['id']; ?></td>
                            <td><a href="/marketnearme/product.php?id=<?php echo $r['listing_id']; ?>" target="_blank"><?php echo htmlspecialchars(substr($r['listing_title'], 0, 30)); ?>...</a></td>
                            <td><?php echo htmlspecialchars($r['reporter']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $r['reason']; ?></span></td>
                            <td><small><?php echo htmlspecialchars(substr($r['description'], 0, 50)); ?>...</small></td>
                            <td><span class="badge bg-<?php echo $r['status'] === 'pending' ? 'warning' : ($r['status'] === 'resolved' ? 'success' : 'secondary'); ?>"><?php echo $r['status']; ?></span></td>
                            <td><?php echo date('M d', strtotime($r['created_at'])); ?></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                <a href="?action=resolve&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-success">Resolve</a>
                                <a href="?action=dismiss&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-secondary">Dismiss</a>
                                <?php endif; ?>
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