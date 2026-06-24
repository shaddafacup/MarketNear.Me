<?php
// my-listings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$current_user_id = $_SESSION['user_id'];

$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error = '';

// Handle listing deletion
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $delete_id = (int)$_GET['delete'];
    
    // Verify ownership
    $stmt = $conn->prepare("SELECT id FROM listings WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $delete_id,
        ':user_id' => $current_user_id
    ]);
    
    if ($listing = $stmt->fetch()) {
        // Delete listing images
        $stmt = $conn->prepare("SELECT image_path FROM listing_images WHERE listing_id = :listing_id");
        $stmt->execute([':listing_id' => $delete_id]);
        $images = $stmt->fetchAll();
        
        foreach ($images as $image) {
            $filepath = __DIR__ . '/uploads/listings/' . $image['image_path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        // Delete the listing (cascade will handle related records)
        $stmt = $conn->prepare("DELETE FROM listings WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
        
        $_SESSION['success_message'] = 'Listing deleted successfully!';
        header("Location: /marketnearme/my-listings.php");
        exit();
    }
}

// Handle status toggle
if (isset($_GET['toggle_status']) && (int)$_GET['toggle_status'] > 0) {
    $listing_id = (int)$_GET['toggle_status'];
    
    $stmt = $conn->prepare("SELECT id, status FROM listings WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $listing_id,
        ':user_id' => $current_user_id
    ]);
    
    if ($listing = $stmt->fetch()) {
        $new_status = $listing['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE listings SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $new_status,
            ':id' => $listing_id
        ]);
        
        $_SESSION['success_message'] = 'Listing status updated!';
        header("Location: /marketnearme/my-listings.php");
        exit();
    }
}

// Get user's listings with all related info
$stmt = $conn->prepare("
    SELECT l.*, 
           c.name as category_name,
           c.slug as category_slug,
           cur.code as currency_code,
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           cur.decimal_separator as currency_decimal_separator,
           cur.thousands_separator as currency_thousands_separator,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           (SELECT COUNT(*) FROM messages WHERE listing_id = l.id) as message_count,
           (SELECT COUNT(*) FROM favorites WHERE listing_id = l.id) as favorite_count
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.user_id = :user_id
    ORDER BY l.created_at DESC
");

$stmt->execute([':user_id' => $current_user_id]);
$listings = $stmt->fetchAll();

// Get listing statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_listings,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_listings,
        SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_listings,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_listings,
        SUM(views) as total_views
    FROM listings 
    WHERE user_id = :user_id
");
$stmt->execute([':user_id' => $current_user_id]);
$stats = $stmt->fetch();

// Format price helper
function formatListingPrice($price, $listing) {
    if (isset($listing['currency_symbol']) && $listing['currency_symbol']) {
        $formatted = number_format(
            $price,
            $listing['currency_decimal_places'] ?? 2,
            $listing['currency_decimal_separator'] ?? '.',
            $listing['currency_thousands_separator'] ?? ','
        );
        
        if (($listing['currency_symbol_position'] ?? 'before') === 'before') {
            return $listing['currency_symbol'] . ' ' . $formatted;
        } else {
            return $formatted . ' ' . $listing['currency_symbol'];
        }
    }
    return '$' . number_format($price, 2);
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">My Listings</h1>
            <p class="text-muted mb-0">Manage all your listings in one place</p>
        </div>
        <a href="/marketnearme/post-ad.php" class="btn btn-lg" style="background: var(--primary-gradient); color: white;">
            <i class="fas fa-plus-circle"></i> Post New Ad
        </a>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-list fa-2x mb-2" style="color: var(--primary-color);"></i>
                    <h3 class="mb-0"><?php echo $stats['total_listings'] ?? 0; ?></h3>
                    <small class="text-muted">Total Listings</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2" style="color: var(--success-color);"></i>
                    <h3 class="mb-0"><?php echo $stats['active_listings'] ?? 0; ?></h3>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-tag fa-2x mb-2" style="color: var(--warning-color);"></i>
                    <h3 class="mb-0"><?php echo $stats['sold_listings'] ?? 0; ?></h3>
                    <small class="text-muted">Sold</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-eye fa-2x mb-2" style="color: var(--accent-color);"></i>
                    <h3 class="mb-0"><?php echo number_format($stats['total_views'] ?? 0); ?></h3>
                    <small class="text-muted">Total Views</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-filter="all">All (<?php echo count($listings); ?>)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-filter="active">Active (<?php echo $stats['active_listings'] ?? 0; ?>)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-filter="sold">Sold (<?php echo $stats['sold_listings'] ?? 0; ?>)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-filter="inactive">Inactive (<?php echo $stats['inactive_listings'] ?? 0; ?>)</a>
        </li>
    </ul>
    
    <?php if (empty($listings)): ?>
    <!-- Empty State -->
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="fas fa-box-open fa-5x" style="color: var(--gray-300);"></i>
        </div>
        <h3>No listings yet</h3>
        <p class="text-muted mb-4">You haven't posted any listings. Start selling today!</p>
        <a href="/marketnearme/post-ad.php" class="btn btn-primary btn-lg">
            <i class="fas fa-plus"></i> Post Your First Ad
        </a>
    </div>
    <?php else: ?>
    
    <!-- Desktop Table View -->
    <div class="card shadow-sm d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Image</th>
                        <th>Listing Details</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Stats</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $listing): ?>
                    <tr class="listing-row" data-status="<?php echo $listing['status']; ?>">
                        <td>
                            <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                                 alt="" class="rounded" style="width: 70px; height: 70px; object-fit: cover;">
                        </td>
                        <td>
                            <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" class="text-decoration-none">
                                <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
                            </a>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($listing['category_name']); ?>
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="far fa-clock"></i> Posted <?php echo date('M d, Y', strtotime($listing['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <strong style="color: var(--secondary-color);">
                                <?php echo formatListingPrice($listing['price'], $listing); ?>
                            </strong>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $listing['status'] === 'active' ? 'success' : 
                                    ($listing['status'] === 'sold' ? 'warning' : 
                                    ($listing['status'] === 'flagged' ? 'danger' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst($listing['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div>
                                <i class="fas fa-eye text-muted"></i> <?php echo $listing['views']; ?> views
                            </div>
                            <?php if ($listing['favorite_count'] > 0): ?>
                            <div>
                                <i class="fas fa-heart text-danger"></i> <?php echo $listing['favorite_count']; ?> saves
                            </div>
                            <?php endif; ?>
                            <?php if ($listing['message_count'] > 0): ?>
                            <div>
                                <i class="fas fa-envelope text-primary"></i> <?php echo $listing['message_count']; ?> msgs
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="/marketnearme/edit-listing.php?id=<?php echo $listing['id']; ?>" 
                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?toggle_status=<?php echo $listing['id']; ?>" 
                                   class="btn btn-sm btn-outline-info" 
                                   title="<?php echo $listing['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-<?php echo $listing['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                </a>
                                <button onclick="deleteListing(<?php echo $listing['id']; ?>)" 
                                        class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Mobile Card View -->
    <div class="d-md-none">
        <?php foreach ($listings as $listing): ?>
        <div class="card shadow-sm mb-3 listing-row" data-status="<?php echo $listing['status']; ?>">
            <div class="row g-0">
                <div class="col-4">
                    <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                         class="img-fluid rounded-start h-100" style="object-fit: cover; min-height: 140px;" 
                         alt="<?php echo htmlspecialchars($listing['title']); ?>">
                </div>
                <div class="col-8">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="card-title mb-1"><?php echo htmlspecialchars($listing['title']); ?></h6>
                            <span class="badge bg-<?php 
                                echo $listing['status'] === 'active' ? 'success' : 
                                    ($listing['status'] === 'sold' ? 'warning' : 'secondary'); 
                            ?>">
                                <?php echo ucfirst($listing['status']); ?>
                            </span>
                        </div>
                        <p class="card-text mb-1">
                            <strong style="color: var(--secondary-color);">
                                <?php echo formatListingPrice($listing['price'], $listing); ?>
                            </strong>
                        </p>
                        <p class="card-text mb-1">
                            <small class="text-muted">
                                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($listing['category_name']); ?>
                            </small>
                        </p>
                        <p class="card-text mb-2">
                            <small class="text-muted">
                                <i class="fas fa-eye"></i> <?php echo $listing['views']; ?> views
                                <?php if ($listing['favorite_count'] > 0): ?>
                                | <i class="fas fa-heart text-danger"></i> <?php echo $listing['favorite_count']; ?>
                                <?php endif; ?>
                                <?php if ($listing['message_count'] > 0): ?>
                                | <i class="fas fa-envelope text-primary"></i> <?php echo $listing['message_count']; ?>
                                <?php endif; ?>
                            </small>
                        </p>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/marketnearme/edit-listing.php?id=<?php echo $listing['id']; ?>" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?toggle_status=<?php echo $listing['id']; ?>" 
                               class="btn btn-outline-info">
                                <i class="fas fa-<?php echo $listing['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                            </a>
                            <button onclick="deleteListing(<?php echo $listing['id']; ?>)" 
                                    class="btn btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this listing?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong> All images and data associated with this listing will be permanently removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Listing
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Delete listing with confirmation modal
function deleteListing(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('confirmDeleteBtn').href = `?delete=${id}`;
    modal.show();
}

// Filter functionality
document.querySelectorAll('[data-filter]').forEach(filterBtn => {
    filterBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Update active state
        document.querySelectorAll('[data-filter]').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.getAttribute('data-filter');
        const rows = document.querySelectorAll('.listing-row');
        
        rows.forEach(row => {
            if (filter === 'all' || row.getAttribute('data-status') === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// Mark as sold
function markAsSold(id) {
    if (confirm('Mark this listing as sold?')) {
        window.location.href = `?mark_sold=${id}`;
    }
}

// Tooltip initialization
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>