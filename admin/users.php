<?php
// admin/users.php
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
    
    if ($_GET['action'] === 'suspend') {
        $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE id = :id AND role != 'admin'");
        $stmt->execute([':id' => $id]);
        $_SESSION['admin_success'] = 'User suspended.';
    } elseif ($_GET['action'] === 'activate') {
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['admin_success'] = 'User activated.';
    } elseif ($_GET['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
        $stmt->execute([':id' => $id]);
        $_SESSION['admin_success'] = 'User deleted.';
    }
    header("Location: /marketnearme/admin/users.php");
    exit();
}

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (username LIKE :s OR email LIKE :s2 OR full_name LIKE :s3)";
    $params[':s'] = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/listings.php"><i class="fas fa-list"></i> Listings</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/marketnearme/admin/users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/featured.php"><i class="fas fa-star"></i> Featured</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/reports.php"><i class="fas fa-flag"></i> Reports</a></li>
                </ul>
            </div>
        </nav>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h1 class="h3 mb-4">Manage Users</h1>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form class="row g-2 mb-4">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Listings</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): 
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM listings WHERE user_id = :uid");
                            $stmt->execute([':uid' => $u['id']]);
                            $listing_count = $stmt->fetch()['total'];
                        ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong><br><small class="text-muted">@<?php echo htmlspecialchars($u['username']); ?></small></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span class="badge bg-<?php echo $u['role'] === 'admin' ? 'warning' : 'info'; ?>"><?php echo $u['role']; ?></span></td>
                            <td><span class="badge bg-<?php echo $u['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo $u['status']; ?></span></td>
                            <td><?php echo $listing_count; ?></td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <?php if ($u['status'] === 'active'): ?>
                                    <a href="?action=suspend&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-warning">Suspend</a>
                                    <?php else: ?>
                                    <a href="?action=activate&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-success">Activate</a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete user?')">Delete</a>
                                <?php else: ?>
                                    <span class="text-muted">Admin</span>
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