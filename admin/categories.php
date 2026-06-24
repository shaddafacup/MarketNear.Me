<?php
// admin/categories.php
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

// Handle add/edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = Security::sanitize($_POST['name'] ?? '');
    $slug = Security::generateSlug($name);
    $description = Security::sanitize($_POST['description'] ?? '');
    $icon = Security::sanitize($_POST['icon'] ?? 'fa-tag');
    
    if (isset($_POST['edit_id']) && $_POST['edit_id'] > 0) {
        $stmt = $conn->prepare("UPDATE categories SET name = :name, slug = :slug, description = :desc, icon = :icon WHERE id = :id");
        $stmt->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':icon' => $icon, ':id' => $_POST['edit_id']]);
        $_SESSION['admin_success'] = 'Category updated.';
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (:name, :slug, :desc, :icon)");
        $stmt->execute([':name' => $name, ':slug' => $slug, ':desc' => $description, ':icon' => $icon]);
        $_SESSION['admin_success'] = 'Category added.';
    }
    header("Location: /marketnearme/admin/categories.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['delete']]);
    $_SESSION['admin_success'] = 'Category deleted.';
    header("Location: /marketnearme/admin/categories.php");
    exit();
}

$categories = DBHelper::getCategories();
$edit_category = null;

if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['edit']]);
    $edit_category = $stmt->fetch();
}

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
                    <li class="nav-item"><a class="nav-link active" href="/marketnearme/admin/categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/featured.php"><i class="fas fa-star"></i> Featured</a></li>
                    <li class="nav-item"><a class="nav-link" href="/marketnearme/admin/reports.php"><i class="fas fa-flag"></i> Reports</a></li>
                </ul>
            </div>
        </nav>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h1 class="h3 mb-4">Manage Categories</h1>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header"><h5 class="mb-0"><?php echo $edit_category ? 'Edit' : 'Add'; ?> Category</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="edit_id" value="<?php echo $edit_category['id'] ?? 0; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Icon (Font Awesome class)</label>
                                    <input type="text" name="icon" class="form-control" value="<?php echo htmlspecialchars($edit_category['icon'] ?? 'fa-tag'); ?>" placeholder="fa-laptop">
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><?php echo $edit_category ? 'Update' : 'Add'; ?> Category</button>
                                <?php if ($edit_category): ?>
                                <a href="/marketnearme/admin/categories.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Listings</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>#<?php echo $cat['id']; ?></td>
                                    <td><i class="fas <?php echo htmlspecialchars($cat['icon'] ?? 'fa-tag'); ?>"></i></td>
                                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                                    <td><?php echo $cat['listing_count']; ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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