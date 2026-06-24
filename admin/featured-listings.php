<?php
// featured-listings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

$db = new Database();
$conn = $db->getConnection();

// Get all active featured listings
$stmt = $conn->prepare("
    SELECT l.*, 
           u.username, 
           u.full_name,
           u.location as user_location,
           c.name as category_name,
           c.slug as category_slug,
           cur.code as currency_code,
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           cur.decimal_separator as currency_decimal_separator,
           cur.thousands_separator as currency_thousands_separator,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           fl.featured_date,
           fl.expiry_date,
           DATEDIFF(fl.expiry_date, NOW()) as days_remaining
    FROM featured_listings fl
    JOIN listings l ON fl.listing_id = l.id
    JOIN users u ON l.user_id = u.id
    JOIN categories c ON l.category_id = c.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE fl.is_active = 1 
    AND l.status = 'active'
    AND (fl.expiry_date IS NULL OR fl.expiry_date > NOW())
    ORDER BY fl.featured_date DESC
");

$stmt->execute();
$featured_listings = $stmt->fetchAll();

function formatFeaturedPrice($price, $listing) {
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
    <!-- Hero Banner -->
    <div class="text-center mb-5 py-5" style="background: var(--accent-gradient); border-radius: var(--radius-2xl); color: white;">
        <h1 class="display-5 fw-bold mb-3">
            <i class="fas fa-star"></i> Featured Listings
        </h1>
        <p class="lead mb-0">Discover our handpicked premium listings from trusted sellers</p>
    </div>
    
    <?php if (empty($featured_listings)): ?>
    <div class="text-center py-5">
        <i class="fas fa-star fa-5x mb-3" style="color: var(--gray-300);"></i>
        <h3>No Featured Listings</h3>
        <p class="text-muted">Check back soon for featured listings!</p>
        <a href="/marketnearme/search.php" class="btn btn-primary mt-3">
            <i class="fas fa-search"></i> Browse All Listings
        </a>
    </div>
    <?php else: ?>
    
    <!-- Featured Listings Grid -->
    <div class="row">
        <?php foreach ($featured_listings as $listing): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="listing-card h-100 position-relative" style="border: 2px solid rgba(124, 58, 237, 0.3);">
                <!-- Featured Ribbon -->
                <div class="position-absolute" style="top: -2px; right: 20px; z-index: 10;">
                    <div style="background: var(--accent-gradient); color: white; padding: 8px 20px; border-radius: 0 0 8px 8px; font-weight: 600; font-size: 0.85rem;">
                        <i class="fas fa-star"></i> FEATURED
                    </div>
                </div>
                
                <!-- Days Remaining Badge -->
                <?php if (isset($listing['days_remaining']) && $listing['days_remaining'] <= 7): ?>
                <div class="position-absolute" style="top: 10px; left: 10px; z-index: 10;">
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-clock"></i> <?php echo $listing['days_remaining']; ?> days left
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="position-relative overflow-hidden" style="height: 220px;">
                    <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                         class="w-100 h-100" style="object-fit: cover; transition: transform 0.5s;" 
                         alt="<?php echo htmlspecialchars($listing['title']); ?>">
                    <div class="position-absolute bottom-0 start-0 end-0 p-2" 
                         style="background: linear-gradient(transparent, rgba(0,0,0,0.7));">
                        <span class="badge bg-light text-dark">
                            <?php echo htmlspecialchars($listing['category_name']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">
                        <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" class="text-decoration-none text-dark">
                            <?php echo htmlspecialchars($listing['title']); ?>
                        </a>
                    </h5>
                    
                    <p class="price mb-2" style="font-size: 1.4rem;">
                        <?php echo formatFeaturedPrice($listing['price'], $listing); ?>
                    </p>
                    
                    <p class="mb-2 small text-muted">
                        <i class="fas fa-map-marker-alt"></i> 
                        <?php echo htmlspecialchars($listing['location'] ?? $listing['user_location'] ?? 'N/A'); ?>
                    </p>
                    
                    <p class="mb-2 small text-muted">
                        <i class="fas fa-user"></i> 
                        <?php echo htmlspecialchars($listing['full_name'] ?? $listing['username']); ?>
                        <span class="ms-2 badge bg-success">
                            <i class="fas fa-check"></i> Verified Seller
                        </span>
                    </p>
                    
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">
                                <i class="far fa-clock"></i> 
                                <?php echo date('M d, Y', strtotime($listing['created_at'])); ?>
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-eye"></i> <?php echo $listing['views']; ?> views
                            </small>
                        </div>
                        
                        <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                           class="btn btn-sm w-100" style="background: var(--accent-gradient); color: white;">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>