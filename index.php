<?php
// index.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

// Get welcome message if exists
$welcome_message = '';
if (isset($_SESSION['welcome_message'])) {
    $welcome_message = $_SESSION['welcome_message'];
    unset($_SESSION['welcome_message']);
}

// Get featured and recent listings with currency info
$db = new Database();
$conn = $db->getConnection();

// Get featured listings
$stmt = $conn->prepare("
    SELECT l.*, 
           u.username, 
           u.location as user_location,
           c.name as category_name,
           c.slug as category_slug,
           cur.code as currency_code,
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           cur.decimal_separator as currency_decimal_separator,
           cur.thousands_separator as currency_thousands_separator,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM listings l
    JOIN users u ON l.user_id = u.id
    JOIN categories c ON l.category_id = c.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.status = 'active' AND l.is_featured = 1
    ORDER BY l.created_at DESC
    LIMIT 8
");
$stmt->execute();
$featured_listings = $stmt->fetchAll();

// Get recent listings
$stmt = $conn->prepare("
    SELECT l.*, 
           u.username, 
           u.location as user_location,
           c.name as category_name,
           c.slug as category_slug,
           cur.code as currency_code,
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           cur.decimal_separator as currency_decimal_separator,
           cur.thousands_separator as currency_thousands_separator,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM listings l
    JOIN users u ON l.user_id = u.id
    JOIN categories c ON l.category_id = c.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.status = 'active'
    ORDER BY l.created_at DESC
    LIMIT 12
");
$stmt->execute();
$recent_listings = $stmt->fetchAll();

// Now include header
require_once 'includes/header.php';

// Helper function to format price in listings
function formatListingPrice($listing) {
    if (isset($listing['currency_symbol']) && $listing['currency_symbol']) {
        $formatted = number_format(
            $listing['price'],
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
    return '$' . number_format($listing['price'], 2);
}
?>

<!-- Welcome Message -->
<?php if ($welcome_message): ?>
<div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-smile"></i> <?php echo htmlspecialchars($welcome_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1>Find Everything Near You</h1>
                <p class="lead">Discover thousands of listings from your local community. Buy, sell, and trade with confidence.</p>
                <div class="d-flex gap-2 mt-4">
                    <?php if (!Auth::isLoggedIn()): ?>
                    <a href="/marketnearme/register.php" class="btn btn-light btn-lg">
                        <i class="fas fa-user-plus"></i> Get Started Free
                    </a>
                    <?php endif; ?>
                    <a href="/marketnearme/search.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-compass"></i> Explore Listings
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="search-box">
                    <form action="/marketnearme/search.php" method="GET">
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg border-start-0" 
                                   name="q" placeholder="What are you looking for?">
                            <button class="btn btn-lg px-4" type="submit" 
                                    style="background: var(--primary-gradient); color: white; border: none;">
                                Search
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="location" placeholder="Location (City, State)">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Stats Section -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-2x mb-2" style="color: var(--primary-color);"></i>
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
                    $total_users = $stmt->fetch()['total'];
                    ?>
                    <h3 class="mb-0"><?php echo number_format($total_users); ?>+</h3>
                    <small class="text-muted">Active Users</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-tags fa-2x mb-2" style="color: var(--secondary-color);"></i>
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) as total FROM listings WHERE status = 'active'");
                    $total_listings = $stmt->fetch()['total'];
                    ?>
                    <h3 class="mb-0"><?php echo number_format($total_listings); ?>+</h3>
                    <small class="text-muted">Active Listings</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-folder-open fa-2x mb-2" style="color: var(--accent-color);"></i>
                    <h3 class="mb-0"><?php echo count($categories); ?>+</h3>
                    <small class="text-muted">Categories</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-smile fa-2x mb-2" style="color: var(--success-color);"></i>
                    <?php
                    $stmt = $conn->query("SELECT COUNT(*) as total FROM messages");
                    $total_messages = $stmt->fetch()['total'];
                    ?>
                    <h3 class="mb-0"><?php echo number_format($total_messages); ?>+</h3>
                    <small class="text-muted">Messages Sent</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Categories Section -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Browse Categories</h2>
            <a href="/marketnearme/search.php" class="btn btn-outline-primary btn-sm">View All</a>
        </div>
        <div class="row g-3">
            <?php foreach (array_slice($categories, 0, 8) as $category): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="/marketnearme/search.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                    <div class="card text-center h-100 shadow-sm hover-lift border-0">
                        <div class="card-body py-4">
                            <i class="fas <?php echo htmlspecialchars($category['icon'] ?? 'fa-tag'); ?> fa-3x mb-3" 
                               style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="text-muted small mb-0"><?php echo $category['listing_count']; ?> listings</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Featured Listings -->
    <?php if (!empty($featured_listings)): ?>
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-star" style="color: var(--warning-color);"></i> Featured Listings
            </h2>
            <a href="/marketnearme/search.php?featured=1" class="btn btn-outline-primary btn-sm">View All</a>
        </div>
        <div class="row">
            <?php foreach ($featured_listings as $listing): ?>
            <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                <div class="listing-card">
                    <div class="position-relative overflow-hidden" style="height: 200px;">
                        <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                             class="w-100 h-100" style="object-fit: cover;" 
                             alt="<?php echo htmlspecialchars($listing['title']); ?>">
                        <span class="position-absolute top-0 end-0 m-2 badge" style="background: var(--accent-gradient);">
                            <i class="fas fa-star"></i> Featured
                        </span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" title="<?php echo htmlspecialchars($listing['title']); ?>">
                            <?php echo htmlspecialchars($listing['title']); ?>
                        </h5>
                        <p class="price mb-2">
                            <?php echo formatListingPrice($listing); ?>
                        </p>
                        <p class="location mb-2 small text-muted">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($listing['location'] ?? $listing['user_location'] ?? 'N/A'); ?>
                        </p>
                        <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                           class="btn btn-sm w-100 mt-2" style="background: var(--primary-gradient); color: white;">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Recent Listings -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Recent Listings</h2>
            <a href="/marketnearme/search.php?sort=newest" class="btn btn-outline-primary btn-sm">View All</a>
        </div>
        <div class="row">
            <?php foreach ($recent_listings as $listing): ?>
            <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                <div class="listing-card">
                    <div class="position-relative overflow-hidden" style="height: 200px;">
                        <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                             class="w-100 h-100" style="object-fit: cover;" 
                             alt="<?php echo htmlspecialchars($listing['title']); ?>">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title" title="<?php echo htmlspecialchars($listing['title']); ?>">
                            <?php echo htmlspecialchars($listing['title']); ?>
                        </h5>
                        <p class="price mb-2">
                            <?php echo formatListingPrice($listing); ?>
                        </p>
                        <p class="location mb-2 small text-muted">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($listing['location'] ?? $listing['user_location'] ?? 'N/A'); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="far fa-clock"></i> 
                                <?php 
                                $time = strtotime($listing['created_at']);
                                $diff = time() - $time;
                                if ($diff < 3600) {
                                    echo floor($diff / 60) . 'm ago';
                                } elseif ($diff < 86400) {
                                    echo floor($diff / 3600) . 'h ago';
                                } elseif ($diff < 604800) {
                                    echo floor($diff / 86400) . 'd ago';
                                } else {
                                    echo date('M d', $time);
                                }
                                ?>
                            </small>
                            <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                               class="btn btn-outline-primary btn-sm">
                                View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- How It Works -->
    <section class="mb-5 py-5" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%); border-radius: var(--radius-2xl);">
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                             style="width: 80px; height: 80px; background: var(--primary-gradient); color: white;">
                            <i class="fas fa-user-plus fa-2x"></i>
                        </div>
                    </div>
                    <h5>1. Create Account</h5>
                    <p class="text-muted">Sign up for free in seconds and set up your profile</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                             style="width: 80px; height: 80px; background: var(--secondary-gradient); color: white;">
                            <i class="fas fa-camera fa-2x"></i>
                        </div>
                    </div>
                    <h5>2. Post Your Ad</h5>
                    <p class="text-muted">Upload photos, set your price and currency, and publish your listing</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                             style="width: 80px; height: 80px; background: var(--accent-gradient); color: white;">
                            <i class="fas fa-comments fa-2x"></i>
                        </div>
                    </div>
                    <h5>3. Connect</h5>
                    <p class="text-muted">Message buyers and sellers directly to arrange deals</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action -->
    <?php if (!Auth::isLoggedIn()): ?>
    <section class="text-center py-5 mb-5" style="background: var(--primary-gradient); border-radius: var(--radius-2xl); color: white;">
        <h2 class="mb-3">Ready to Start Selling?</h2>
        <p class="lead mb-4">Join thousands of sellers in your community. It's free and easy!</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="/marketnearme/register.php" class="btn btn-light btn-lg px-4">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <a href="/marketnearme/post-ad.php" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-plus-circle"></i> Post Free Ad
            </a>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>