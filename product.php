<?php
// product.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files in the correct order
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

$listing_id = (int)($_GET['id'] ?? 0);

if ($listing_id <= 0) {
    header("Location: /marketnearme/index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get listing details with currency information
$stmt = $conn->prepare("
    SELECT l.*, 
           u.username, 
           u.full_name,
           u.location as user_location,
           u.phone as user_phone,
           u.email as user_email,
           u.created_at as member_since,
           c.name as category_name,
           c.slug as category_slug,
           cur.code as currency_code,
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           cur.decimal_separator as currency_decimal_separator,
           cur.thousands_separator as currency_thousands_separator
    FROM listings l
    JOIN users u ON l.user_id = u.id
    JOIN categories c ON l.category_id = c.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.id = :id AND l.status = 'active'
");

$stmt->execute([':id' => $listing_id]);
$listing = $stmt->fetch();

if (!$listing) {
    header("Location: /marketnearme/index.php");
    exit();
}

// Get listing images
$stmt = $conn->prepare("SELECT * FROM listing_images WHERE listing_id = :listing_id ORDER BY sort_order ASC");
$stmt->execute([':listing_id' => $listing_id]);
$images = $stmt->fetchAll();

// Increment view count
$stmt = $conn->prepare("UPDATE listings SET views = views + 1 WHERE id = :id");
$stmt->execute([':id' => $listing_id]);

// Get related listings
$stmt = $conn->prepare("
    SELECT l.*, 
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           cur.decimal_separator as currency_decimal_separator,
           cur.thousands_separator as currency_thousands_separator,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM listings l
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.category_id = :category_id 
    AND l.id != :listing_id 
    AND l.status = 'active'
    ORDER BY l.created_at DESC
    LIMIT 4
");

$stmt->execute([
    ':category_id' => $listing['category_id'],
    ':listing_id' => $listing_id
]);
$related_listings = $stmt->fetchAll();

// Get seller's other listings
$stmt = $conn->prepare("
    SELECT l.*, 
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           cur.decimal_separator as currency_decimal_separator,
           cur.thousands_separator as currency_thousands_separator,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM listings l
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.user_id = :user_id 
    AND l.id != :listing_id 
    AND l.status = 'active'
    ORDER BY l.created_at DESC
    LIMIT 4
");

$stmt->execute([
    ':user_id' => $listing['user_id'],
    ':listing_id' => $listing_id
]);
$seller_listings = $stmt->fetchAll();

/**
 * Helper function to format price with currency
 */
function formatListingPrice($listing) {
    if (isset($listing['currency_symbol']) && $listing['currency_symbol']) {
        $formatted_price = number_format(
            $listing['price'],
            $listing['currency_decimal_places'] ?? 2,
            $listing['currency_decimal_separator'] ?? '.',
            $listing['currency_thousands_separator'] ?? ','
        );
        
        if (($listing['currency_symbol_position'] ?? 'before') === 'before') {
            return $listing['currency_symbol'] . ' ' . $formatted_price;
        } else {
            return $formatted_price . ' ' . $listing['currency_symbol'];
        }
    }
    
    // Fallback to default formatting
    return '$ ' . number_format($listing['price'], 2);
}

// Format the main listing price
$price_display = formatListingPrice($listing);

// Now include header AFTER all database operations
require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/marketnearme/" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item">
                <a href="/marketnearme/search.php?category=<?php echo $listing['category_id']; ?>" class="text-decoration-none">
                    <?php echo htmlspecialchars($listing['category_name']); ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars(substr($listing['title'], 0, 50)) . (strlen($listing['title']) > 50 ? '...' : ''); ?>
            </li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Images Section -->
        <div class="col-lg-7 mb-4">
            <div class="product-images">
                <?php if (!empty($images)): ?>
                <div class="position-relative mb-3">
                    <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($images[0]['image_path']); ?>" 
                         class="main-image w-100 rounded-3 shadow" id="main-product-image" 
                         alt="<?php echo htmlspecialchars($listing['title']); ?>"
                         style="height: 450px; object-fit: cover; transition: opacity 0.3s ease;">
                    
                    <?php if ($listing['is_featured']): ?>
                    <span class="position-absolute top-0 start-0 m-3 badge" style="background: var(--accent-gradient);">
                        <i class="fas fa-star"></i> Featured
                    </span>
                    <?php endif; ?>
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="thumbnail-images d-flex gap-2 overflow-auto pb-2">
                    <?php foreach ($images as $index => $image): ?>
                    <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($image['image_path']); ?>" 
                         class="thumbnail rounded-3 <?php echo $index === 0 ? 'active' : ''; ?>"
                         onclick="changeMainImage(this)"
                         alt="Thumbnail <?php echo $index + 1; ?>"
                         style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; flex-shrink: 0;">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="height: 450px;">
                    <div class="text-center text-muted">
                        <i class="fas fa-image fa-4x mb-3"></i>
                        <p>No image available</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Details Section -->
        <div class="col-lg-5">
            <div class="product-details">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h1 class="h3 mb-0" style="flex: 1;"><?php echo htmlspecialchars($listing['title']); ?></h1>
                    <?php if (Auth::isLoggedIn() && $listing['user_id'] != $_SESSION['user_id']): ?>
                    <button class="btn btn-outline-danger btn-sm rounded-circle ms-2" 
                            onclick="toggleFavorite(<?php echo $listing_id; ?>)"
                            title="Add to favorites">
                        <i class="far fa-heart"></i>
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- ============ PRICE DISPLAY WITH CURRENCY ============ -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="h2 mb-0" style="color: var(--secondary-color); font-weight: 800;">
                        <?php echo htmlspecialchars($price_display); ?>
                    </span>
                    <?php if ($listing['currency_code']): ?>
                    <span class="badge bg-light text-dark">
                        <?php echo htmlspecialchars($listing['currency_code']); ?>
                    </span>
                    <?php endif; ?>
                    <span class="badge" style="background: var(--primary-gradient);">
                        <?php echo ucfirst(str_replace('_', ' ', $listing['condition_status'])); ?>
                    </span>
                </div>
                <!-- ============ END PRICE DISPLAY ============ -->
                
                <div class="d-flex gap-3 mb-4 text-muted small">
                    <span><i class="fas fa-eye"></i> <?php echo $listing['views']; ?> views</span>
                    <span><i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($listing['created_at'])); ?></span>
                </div>
                
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-map-marker-alt me-2" style="color: var(--primary-color);"></i>
                        <span><?php echo htmlspecialchars($listing['location'] ?? $listing['user_location'] ?? 'Location not specified'); ?></span>
                    </div>
                </div>
                
                <hr>
                
                <h5 class="mb-3">Description</h5>
                <div class="mb-4" style="line-height: 1.8; white-space: pre-wrap;">
                    <?php echo nl2br(htmlspecialchars($listing['description'])); ?>
                </div>
                
                <hr>
                
                <h5 class="mb-3">Contact Seller</h5>
                <?php if (Auth::isLoggedIn()): ?>
                    <?php if ($listing['user_id'] != $_SESSION['user_id']): ?>
                    <div class="d-grid gap-2">
                        <a href="/marketnearme/messages.php?listing_id=<?php echo $listing_id; ?>" 
                           class="btn btn-lg" style="background: var(--primary-gradient); color: white;">
                            <i class="fas fa-comment-dots"></i> Send Message
                        </a>
                        
                        <?php if ($listing['contact_phone']): ?>
                        <a href="tel:<?php echo htmlspecialchars($listing['contact_phone']); ?>" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($listing['contact_phone']); ?>
                        </a>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-danger btn-sm" onclick="reportListing(<?php echo $listing_id; ?>)">
                            <i class="fas fa-flag"></i> Report This Listing
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> This is your listing. 
                        <a href="/marketnearme/edit-listing.php?id=<?php echo $listing_id; ?>" class="alert-link">Edit Listing</a>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-lock"></i> 
                    <a href="/marketnearme/login.php" class="alert-link">Login</a> to contact the seller.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Seller Information -->
    <div class="card mt-4 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-user me-2" style="color: var(--primary-color);"></i>Seller Information</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2 text-center mb-3 mb-md-0">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px; background: var(--primary-gradient); color: white; font-size: 2rem; font-weight: 600;">
                        <?php echo strtoupper(substr($listing['full_name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="col-md-10">
                    <h5><?php echo htmlspecialchars($listing['full_name']); ?></h5>
                    <p class="text-muted mb-1">@<?php echo htmlspecialchars($listing['username']); ?></p>
                    <p class="text-muted mb-2">
                        <i class="far fa-calendar-alt me-1"></i> Member since <?php echo date('F Y', strtotime($listing['member_since'])); ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-map-marker-alt me-1" style="color: var(--primary-color);"></i> 
                        <?php echo htmlspecialchars($listing['user_location'] ?? 'Location not specified'); ?>
                    </p>
                    
                    <?php if (Auth::isLoggedIn() && $listing['user_id'] != $_SESSION['user_id']): ?>
                    <a href="/marketnearme/messages.php?listing_id=<?php echo $listing_id; ?>" 
                       class="btn btn-sm mt-2" style="background: var(--primary-gradient); color: white;">
                        <i class="fas fa-comment"></i> Contact Seller
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Seller's Other Listings -->
    <?php if (!empty($seller_listings)): ?>
    <section class="mt-5">
        <h3 class="mb-4">More from this seller</h3>
        <div class="row">
            <?php foreach ($seller_listings as $listing): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="listing-card">
                    <div class="position-relative">
                        <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>"
                             style="height: 180px;">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($listing['title']); ?></h5>
                        <p class="price mb-2"><?php echo htmlspecialchars(formatListingPrice($listing)); ?></p>
                        <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                           class="btn btn-outline-primary btn-sm w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Related Listings -->
    <?php if (!empty($related_listings)): ?>
    <section class="mt-4 mb-5">
        <h3 class="mb-4">Related Listings</h3>
        <div class="row">
            <?php foreach ($related_listings as $listing): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="listing-card">
                    <div class="position-relative">
                        <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>"
                             style="height: 180px;">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($listing['title']); ?></h5>
                        <p class="price mb-2"><?php echo htmlspecialchars(formatListingPrice($listing)); ?></p>
                        <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                           class="btn btn-outline-primary btn-sm w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
function changeMainImage(thumbnail) {
    const mainImage = document.getElementById('main-product-image');
    if (mainImage) {
        mainImage.style.opacity = '0';
        
        setTimeout(() => {
            mainImage.src = thumbnail.src;
            mainImage.style.opacity = '1';
        }, 150);
        
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
            thumb.style.border = '2px solid transparent';
        });
        thumbnail.classList.add('active');
        thumbnail.style.border = '2px solid var(--primary-color)';
    }
}

function reportListing(listingId) {
    if (confirm('Are you sure you want to report this listing?')) {
        window.location.href = `/marketnearme/report.php?id=${listingId}`;
    }
}

function toggleFavorite(listingId) {
    fetch('/marketnearme/favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `listing_id=${listingId}&csrf_token=<?php echo Security::generateCSRFToken(); ?>`
    })
    .then(response => response.json())
    .then(data => {
        const heartIcon = document.querySelector('.fa-heart');
        if (data.favorited) {
            heartIcon.classList.remove('far');
            heartIcon.classList.add('fas');
            heartIcon.style.color = '#ef4444';
        } else {
            heartIcon.classList.remove('fas');
            heartIcon.classList.add('far');
            heartIcon.style.color = '';
        }
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    const activeThumb = document.querySelector('.thumbnail.active');
    if (activeThumb) {
        activeThumb.style.border = '2px solid var(--primary-color)';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>