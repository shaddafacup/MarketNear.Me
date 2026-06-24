<?php
// admin/featured.php
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

$success = '';
$error = '';

// Handle adding featured listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        if ($_POST['action'] === 'add_featured') {
            $listing_id = (int)($_POST['listing_id'] ?? 0);
            $expiry_days = (int)($_POST['expiry_days'] ?? 30);
            $notes = Security::sanitize($_POST['notes'] ?? '');
            
            if ($listing_id <= 0) {
                $error = 'Please select a valid listing.';
            } else {
                // Check if listing exists and is active
                $stmt = $conn->prepare("SELECT id, title, user_id, status FROM listings WHERE id = :id AND status = 'active'");
                $stmt->execute([':id' => $listing_id]);
                $listing = $stmt->fetch();
                
                if (!$listing) {
                    $error = 'Listing not found or is not active.';
                } else {
                    // Check if already featured
                    $stmt = $conn->prepare("SELECT id FROM featured_listings WHERE listing_id = :listing_id AND is_active = 1");
                    $stmt->execute([':listing_id' => $listing_id]);
                    
                    if ($stmt->fetch()) {
                        $error = 'This listing is already featured.';
                    } else {
                        try {
                            $conn->beginTransaction();
                            
                            $expiry_date = date('Y-m-d H:i:s', strtotime("+$expiry_days days"));
                            
                            // Insert into featured_listings
                            $stmt = $conn->prepare("
                                INSERT INTO featured_listings (listing_id, expiry_date, featured_by, notes) 
                                VALUES (:listing_id, :expiry_date, :featured_by, :notes)
                            ");
                            $stmt->execute([
                                ':listing_id' => $listing_id,
                                ':expiry_date' => $expiry_date,
                                ':featured_by' => $_SESSION['user_id'],
                                ':notes' => $notes
                            ]);
                            
                            // Update listings table
                            $stmt = $conn->prepare("UPDATE listings SET is_featured = 1 WHERE id = :id");
                            $stmt->execute([':id' => $listing_id]);
                            
                            $conn->commit();
                            
                            Security::logSecurityEvent('listing_featured', "Listing ID: $listing_id marked as featured", $_SESSION['user_id']);
                            $success = "Listing \"{$listing['title']}\" has been featured successfully!";
                            
                        } catch (Exception $e) {
                            $conn->rollBack();
                            error_log("Error featuring listing: " . $e->getMessage());
                            $error = 'Failed to feature listing.';
                        }
                    }
                }
            }
        } elseif ($_POST['action'] === 'remove_featured') {
            $featured_id = (int)($_POST['featured_id'] ?? 0);
            
            if ($featured_id > 0) {
                try {
                    $conn->beginTransaction();
                    
                    // Get listing ID
                    $stmt = $conn->prepare("SELECT listing_id FROM featured_listings WHERE id = :id");
                    $stmt->execute([':id' => $featured_id]);
                    $featured = $stmt->fetch();
                    
                    if ($featured) {
                        // Deactivate featured listing
                        $stmt = $conn->prepare("UPDATE featured_listings SET is_active = 0 WHERE id = :id");
                        $stmt->execute([':id' => $featured_id]);
                        
                        // Check if listing has other active featured entries
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM featured_listings WHERE listing_id = :listing_id AND is_active = 1");
                        $stmt->execute([':listing_id' => $featured['listing_id']]);
                        $active_count = $stmt->fetch()['count'];
                        
                        // If no active featured entries, update listings table
                        if ($active_count == 0) {
                            $stmt = $conn->prepare("UPDATE listings SET is_featured = 0 WHERE id = :id");
                            $stmt->execute([':id' => $featured['listing_id']]);
                        }
                        
                        $conn->commit();
                        $success = 'Featured listing removed successfully!';
                    }
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("Error removing featured listing: " . $e->getMessage());
                    $error = 'Failed to remove featured listing.';
                }
            }
        } elseif ($_POST['action'] === 'extend_featured') {
            $featured_id = (int)($_POST['featured_id'] ?? 0);
            $extend_days = (int)($_POST['extend_days'] ?? 30);
            
            if ($featured_id > 0) {
                $stmt = $conn->prepare("
                    UPDATE featured_listings 
                    SET expiry_date = DATE_ADD(expiry_date, INTERVAL :days DAY) 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':days' => $extend_days,
                    ':id' => $featured_id
                ]);
                $success = "Featured listing extended by $extend_days days!";
            }
        }
    }
}

// Auto-expire featured listings
$stmt = $conn->prepare("
    UPDATE featured_listings fl
    JOIN listings l ON fl.listing_id = l.id
    SET fl.is_active = 0, l.is_featured = 0
    WHERE fl.is_active = 1 AND fl.expiry_date IS NOT NULL AND fl.expiry_date < NOW()
");
$stmt->execute();

// Get featured listings with details
$stmt = $conn->prepare("
    SELECT fl.*, 
           l.title as listing_title,
           l.price,
           l.status as listing_status,
           l.user_id as seller_id,
           u.username as seller_name,
           u.email as seller_email,
           cur.symbol as currency_symbol,
           cur.code as currency_code,
           admin.username as featured_by_name,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM featured_listings fl
    JOIN listings l ON fl.listing_id = l.id
    JOIN users u ON l.user_id = u.id
    JOIN users admin ON fl.featured_by = admin.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    ORDER BY fl.is_active DESC, fl.created_at DESC
");
$stmt->execute();
$featured_listings = $stmt->fetchAll();

// Get available listings for featuring (active, not currently featured)
$stmt = $conn->prepare("
    SELECT l.id, l.title, u.username, l.price, cur.symbol as currency_symbol
    FROM listings l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN featured_listings fl ON l.id = fl.listing_id AND fl.is_active = 1
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.status = 'active' AND fl.id IS NULL
    ORDER BY l.created_at DESC
    LIMIT 50
");
$stmt->execute();
$available_listings = $stmt->fetchAll();

// Get featured stats
$stmt = $conn->query("
    SELECT 
        COUNT(*) as total_featured,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_featured,
        SUM(CASE WHEN expiry_date < NOW() AND is_active = 1 THEN 1 ELSE 0 END) as expired_featured
    FROM featured_listings
");
$featured_stats = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/">
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
                        <a class="nav-link active" href="/marketnearme/admin/featured.php">
                            <i class="fas fa-star"></i> Featured Listings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/reports.php">
                            <i class="fas fa-flag"></i> Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-star" style="color: var(--warning-color);"></i> Featured Listings Management
                </h1>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>Total Featured</h6>
                            <h3><?php echo $featured_stats['total_featured'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>Active Featured</h6>
                            <h3><?php echo $featured_stats['active_featured'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6>Expired</h6>
                            <h3><?php echo $featured_stats['expired_featured'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add Featured Listing -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header" style="background: var(--accent-gradient); color: white;">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Feature a Listing</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="add_featured">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="listing_id" class="form-label">Select Listing *</label>
                                <select class="form-select" id="listing_id" name="listing_id" required>
                                    <option value="">Choose a listing...</option>
                                    <?php foreach ($available_listings as $al): ?>
                                    <option value="<?php echo $al['id']; ?>">
                                        #<?php echo $al['id']; ?> - <?php echo htmlspecialchars($al['title']); ?> 
                                        (by <?php echo htmlspecialchars($al['username']); ?> - 
                                        <?php echo htmlspecialchars($al['currency_symbol'] ?? '$'); ?><?php echo number_format($al['price'], 2); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="expiry_days" class="form-label">Duration (Days)</label>
                                <select class="form-select" id="expiry_days" name="expiry_days">
                                    <option value="7">7 Days</option>
                                    <option value="14">14 Days</option>
                                    <option value="30" selected>30 Days</option>
                                    <option value="60">60 Days</option>
                                    <option value="90">90 Days</option>
                                    <option value="365">1 Year</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="notes" class="form-label">Notes</label>
                                <input type="text" class="form-control" id="notes" name="notes" 
                                       placeholder="Optional notes">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary mt-3">
                            <i class="fas fa-star"></i> Feature This Listing
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Featured Listings Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-star" style="color: var(--warning-color);"></i> Featured Listings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($featured_listings)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <p>No featured listings yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Listing</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Featured Date</th>
                                    <th>Expiry</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($featured_listings as $fl): ?>
                                <tr>
                                    <td>
                                        <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($fl['primary_image'] ?? 'default-listing.jpg'); ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover;" class="rounded">
                                    </td>
                                    <td>
                                        <a href="/marketnearme/product.php?id=<?php echo $fl['listing_id']; ?>" target="_blank">
                                            <strong><?php echo htmlspecialchars($fl['listing_title']); ?></strong>
                                        </a>
                                        <br>
                                        <small class="text-muted">ID: #<?php echo $fl['listing_id']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($fl['seller_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($fl['seller_email']); ?></small>
                                    </td>
                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($fl['currency_symbol'] ?? '$'); ?>
                                            <?php echo number_format($fl['price'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($fl['featured_date'])); ?>
                                        <br>
                                        <small class="text-muted">by <?php echo htmlspecialchars($fl['featured_by_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($fl['expiry_date']): ?>
                                            <?php 
                                            $expiry = strtotime($fl['expiry_date']);
                                            $now = time();
                                            $diff = $expiry - $now;
                                            $days_left = ceil($diff / 86400);
                                            ?>
                                            <span class="badge bg-<?php echo $days_left <= 3 ? 'danger' : ($days_left <= 7 ? 'warning' : 'success'); ?>">
                                                <?php echo date('M d, Y', $expiry); ?>
                                            </span>
                                            <?php if ($fl['is_active']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo $days_left > 0 ? "$days_left days left" : 'Expired'; ?>
                                            </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-info">No expiry</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $fl['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $fl['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($fl['is_active']): ?>
                                        <div class="btn-group btn-group-sm">
                                            <!-- Extend -->
                                            <button class="btn btn-outline-info" 
                                                    onclick="showExtendModal(<?php echo $fl['id']; ?>, '<?php echo htmlspecialchars($fl['listing_title']); ?>')"
                                                    title="Extend featured period">
                                                <i class="fas fa-clock"></i>
                                            </button>
                                            <!-- Remove -->
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Remove this listing from featured?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                                <input type="hidden" name="action" value="remove_featured">
                                                <input type="hidden" name="featured_id" value="<?php echo $fl['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Remove featured status">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted small">Removed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Extend Modal -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Extend Featured Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="extend_featured">
                    <input type="hidden" name="featured_id" id="extend_featured_id">
                    
                    <p>Extend featured period for: <strong id="extend_listing_title"></strong></p>
                    
                    <div class="mb-3">
                        <label for="extend_days" class="form-label">Extend by</label>
                        <select class="form-select" name="extend_days">
                            <option value="7">7 Days</option>
                            <option value="14">14 Days</option>
                            <option value="30" selected>30 Days</option>
                            <option value="60">60 Days</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-clock"></i> Extend Period
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showExtendModal(featuredId, listingTitle) {
    document.getElementById('extend_featured_id').value = featuredId;
    document.getElementById('extend_listing_title').textContent = listingTitle;
    new bootstrap.Modal(document.getElementById('extendModal')).show();
}
</script>

<style>
.sidebar {
    min-height: calc(100vh - 56px);
    padding-top: 20px;
}
.sidebar .nav-link {
    color: #333;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    margin-bottom: 2px;
}
.sidebar .nav-link:hover {
    background-color: #e9ecef;
}
.sidebar .nav-link.active {
    background: var(--primary-gradient);
    color: white;
    font-weight: bold;
}
.sidebar .nav-link i {
    width: 20px;
    text-align: center;
    margin-right: 5px;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>