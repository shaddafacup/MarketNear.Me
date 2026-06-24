<?php
// search.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$query = trim($_GET['q'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$location = trim($_GET['location'] ?? '');
$condition = $_GET['condition'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$featured = isset($_GET['featured']) ? 1 : 0;
$currency_id = (int)($_GET['currency_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

// Build the search query
$sql = "
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
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM listings l
    JOIN users u ON l.user_id = u.id
    JOIN categories c ON l.category_id = c.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.status = 'active'
";

$count_sql = "
    SELECT COUNT(*) as total
    FROM listings l
    JOIN users u ON l.user_id = u.id
    JOIN categories c ON l.category_id = c.id
    WHERE l.status = 'active'
";

$params = [];
$where_conditions = [];

// Search query
if (!empty($query)) {
    $where_conditions[] = "AND (l.title LIKE :query_title OR l.description LIKE :query_desc)";
    $params[':query_title'] = "%$query%";
    $params[':query_desc'] = "%$query%";
}

// Category filter
if ($category_id > 0) {
    $where_conditions[] = "AND l.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

// Price range filter
if ($min_price !== '' && is_numeric($min_price)) {
    $where_conditions[] = "AND l.price >= :min_price";
    $params[':min_price'] = (float)$min_price;
}

if ($max_price !== '' && is_numeric($max_price)) {
    $where_conditions[] = "AND l.price <= :max_price";
    $params[':max_price'] = (float)$max_price;
}

// Location filter
if (!empty($location)) {
    $where_conditions[] = "AND (l.location LIKE :location1 OR u.location LIKE :location2)";
    $params[':location1'] = "%$location%";
    $params[':location2'] = "%$location%";
}

// Condition filter
if (!empty($condition)) {
    $where_conditions[] = "AND l.condition_status = :condition_status";
    $params[':condition_status'] = $condition;
}

// Featured filter
if ($featured) {
    $where_conditions[] = "AND l.is_featured = 1";
}

// Currency filter
if ($currency_id > 0) {
    $where_conditions[] = "AND l.currency_id = :currency_id";
    $params[':currency_id'] = $currency_id;
}

// Add where conditions to queries
$where_clause = implode(' ', $where_conditions);
$sql .= $where_clause;
$count_sql .= $where_clause;

// Sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY l.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY l.price DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY l.created_at ASC";
        break;
    case 'popular':
        $sql .= " ORDER BY l.views DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY l.created_at DESC";
        break;
}

// Get total count for pagination
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_results = $count_stmt->fetch()['total'];
$total_pages = ceil($total_results / $per_page);

// Add pagination
$offset = ($page - 1) * $per_page;
$sql .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

// Execute search
$stmt = $conn->prepare($sql);

// Bind parameters
foreach ($params as $key => &$value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}

$stmt->execute();
$listings = $stmt->fetchAll();

// Get all categories for filter dropdown
$categories = DBHelper::getCategories();

// Get all currencies for filter
$all_currencies = Currency::getAllCurrencies();

// Format price helper
function formatSearchPrice($price, $listing) {
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

// Build query string for pagination links
function buildQueryString($exclude = []) {
    $params = $_GET;
    foreach ($exclude as $key) {
        unset($params[$key]);
    }
    return http_build_query($params);
}

// Now include header
require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <h1 class="mb-2">
            <?php if (!empty($query)): ?>
                Search Results for "<span style="color: var(--primary-color);"><?php echo htmlspecialchars($query); ?></span>"
            <?php elseif ($category_id > 0): ?>
                <?php 
                $cat_name = '';
                foreach ($categories as $cat) {
                    if ($cat['id'] == $category_id) {
                        $cat_name = $cat['name'];
                        break;
                    }
                }
                ?>
                Browse <?php echo htmlspecialchars($cat_name); ?>
            <?php else: ?>
                Browse All Listings
            <?php endif; ?>
        </h1>
        <p class="text-muted">
            <i class="fas fa-list"></i> 
            <?php echo number_format($total_results); ?> listing(s) found
            <?php if ($total_pages > 1): ?>
                | Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2" style="color: var(--primary-color);"></i>Filters
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="" id="filterForm">
                        <!-- Search Query -->
                        <div class="mb-3">
                            <label for="q" class="form-label">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="q" name="q" 
                                       value="<?php echo htmlspecialchars($query); ?>" 
                                       placeholder="What are you looking for?">
                            </div>
                        </div>
                        
                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?> 
                                    (<?php echo $cat['listing_count']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="min_price" 
                                           placeholder="Min" value="<?php echo htmlspecialchars($min_price); ?>" 
                                           min="0" step="0.01">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="max_price" 
                                           placeholder="Max" value="<?php echo htmlspecialchars($max_price); ?>" 
                                           min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Currency -->
                        <div class="mb-3">
                            <label for="currency_id" class="form-label">Currency</label>
                            <select class="form-select" id="currency_id" name="currency_id">
                                <option value="">All Currencies</option>
                                <?php foreach ($all_currencies as $cur): ?>
                                <option value="<?php echo $cur['id']; ?>" <?php echo $currency_id == $cur['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cur['name']); ?> 
                                    (<?php echo htmlspecialchars($cur['symbol']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Location -->
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($location); ?>" 
                                   placeholder="City, State">
                        </div>
                        
                        <!-- Condition -->
                        <div class="mb-3">
                            <label for="condition" class="form-label">Condition</label>
                            <select class="form-select" id="condition" name="condition">
                                <option value="">Any Condition</option>
                                <option value="new" <?php echo $condition === 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="like_new" <?php echo $condition === 'like_new' ? 'selected' : ''; ?>>Like New</option>
                                <option value="good" <?php echo $condition === 'good' ? 'selected' : ''; ?>>Good</option>
                                <option value="fair" <?php echo $condition === 'fair' ? 'selected' : ''; ?>>Fair</option>
                                <option value="used" <?php echo $condition === 'used' ? 'selected' : ''; ?>>Used</option>
                            </select>
                        </div>
                        
                        <!-- Featured Only -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="featured" name="featured" value="1"
                                   <?php echo $featured ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="featured">
                                <i class="fas fa-star" style="color: var(--warning-color);"></i> Featured listings only
                            </label>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="/marketnearme/search.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear All Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Active Filters -->
            <?php if (!empty($query) || $category_id > 0 || $min_price !== '' || $max_price !== '' || !empty($location) || !empty($condition) || $featured || $currency_id > 0): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-tags me-2" style="color: var(--secondary-color);"></i>Active Filters</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-1">
                        <?php if (!empty($query)): ?>
                        <span class="badge bg-primary">
                            Search: <?php echo htmlspecialchars($query); ?>
                            <a href="?<?php echo buildQueryString(['q', 'page']); ?>" class="text-white ms-1">&times;</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($category_id > 0): ?>
                        <span class="badge bg-info">
                            Category: <?php echo htmlspecialchars($cat_name); ?>
                            <a href="?<?php echo buildQueryString(['category', 'page']); ?>" class="text-white ms-1">&times;</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($min_price !== ''): ?>
                        <span class="badge bg-success">
                            Min: <?php echo htmlspecialchars($min_price); ?>
                            <a href="?<?php echo buildQueryString(['min_price', 'page']); ?>" class="text-white ms-1">&times;</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($max_price !== ''): ?>
                        <span class="badge bg-success">
                            Max: <?php echo htmlspecialchars($max_price); ?>
                            <a href="?<?php echo buildQueryString(['max_price', 'page']); ?>" class="text-white ms-1">&times;</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($location)): ?>
                        <span class="badge bg-warning">
                            Location: <?php echo htmlspecialchars($location); ?>
                            <a href="?<?php echo buildQueryString(['location', 'page']); ?>" class="text-white ms-1">&times;</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($condition)): ?>
                        <span class="badge bg-secondary">
                            Condition: <?php echo ucfirst(str_replace('_', ' ', $condition)); ?>
                            <a href="?<?php echo buildQueryString(['condition', 'page']); ?>" class="text-white ms-1">&times;</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($featured): ?>
                        <span class="badge bg-accent">
                            Featured Only
                            <a href="?<?php echo buildQueryString(['featured', 'page']); ?>" class="text-white ms-1">&times;</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($currency_id > 0): ?>
                        <span class="badge bg-dark">
                            Currency
                            <a href="?<?php echo buildQueryString(['currency_id', 'page']); ?>" class="text-white ms-1">&times;</a>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Results Area -->
        <div class="col-lg-9">
            <!-- Sort Options -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center">
                    <label for="sort" class="form-label mb-0 me-2">Sort by:</label>
                    <select class="form-select form-select-sm" id="sort" name="sort" onchange="updateSort(this.value)" style="width: auto;">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    </select>
                </div>
                
                <div class="d-flex align-items-center">
                    <span class="text-muted small me-3">
                        Showing <?php echo min(($page - 1) * $per_page + 1, $total_results); ?>-<?php echo min($page * $per_page, $total_results); ?> of <?php echo $total_results; ?>
                    </span>
                    
                    <!-- View Toggle -->
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active" onclick="setView('grid')" id="gridViewBtn">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="setView('list')" id="listViewBtn">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (empty($listings)): ?>
            <!-- No Results -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-search fa-5x" style="color: var(--gray-300);"></i>
                </div>
                <h3>No listings found</h3>
                <p class="text-muted mb-4">Try adjusting your search criteria or browse all listings</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="/marketnearme/search.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> Browse All Listings
                    </a>
                    <a href="/marketnearme/post-ad.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus"></i> Post an Ad
                    </a>
                </div>
            </div>
            <?php else: ?>
            
            <!-- Grid View -->
            <div id="gridView" class="row">
                <?php foreach ($listings as $listing): ?>
                <div class="col-sm-6 col-lg-4 mb-4">
                    <div class="listing-card h-100">
                        <div class="position-relative overflow-hidden" style="height: 200px;">
                            <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                                 class="w-100 h-100" style="object-fit: cover; transition: transform 0.3s;" 
                                 alt="<?php echo htmlspecialchars($listing['title']); ?>"
                                 onerror="this.src='/marketnearme/assets/images/default-listing.jpg'">
                            
                            <?php if ($listing['is_featured']): ?>
                            <span class="position-absolute top-0 start-0 m-2 badge" style="background: var(--accent-gradient);">
                                <i class="fas fa-star"></i> Featured
                            </span>
                            <?php endif; ?>
                            
                            <span class="position-absolute top-0 end-0 m-2 badge bg-dark bg-opacity-75">
                                <?php echo htmlspecialchars($listing['condition_status']); ?>
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title flex-grow-0" title="<?php echo htmlspecialchars($listing['title']); ?>">
                                <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($listing['title']); ?>
                                </a>
                            </h5>
                            <p class="price mb-2 flex-grow-0">
                                <?php echo formatSearchPrice($listing['price'], $listing); ?>
                            </p>
                            <p class="location mb-2 small text-muted flex-grow-0">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($listing['location'] ?? $listing['user_location'] ?? 'N/A'); ?>
                            </p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="far fa-clock"></i> 
                                    <?php 
                                    $time = strtotime($listing['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 3600) echo floor($diff / 60) . 'm ago';
                                    elseif ($diff < 86400) echo floor($diff / 3600) . 'h ago';
                                    elseif ($diff < 604800) echo floor($diff / 86400) . 'd ago';
                                    else echo date('M d', $time);
                                    ?>
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-eye"></i> <?php echo $listing['views']; ?>
                                </small>
                            </div>
                            <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                               class="btn btn-sm mt-2 w-100" style="background: var(--primary-gradient); color: white;">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- List View -->
            <div id="listView" style="display: none;">
                <?php foreach ($listings as $listing): ?>
                <div class="card shadow-sm mb-3">
                    <div class="row g-0">
                        <div class="col-md-3 position-relative">
                            <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                                 class="img-fluid rounded-start w-100" style="height: 200px; object-fit: cover;"
                                 alt="<?php echo htmlspecialchars($listing['title']); ?>"
                                 onerror="this.src='/marketnearme/assets/images/default-listing.jpg'">
                            <?php if ($listing['is_featured']): ?>
                            <span class="position-absolute top-0 start-0 m-2 badge" style="background: var(--accent-gradient);">
                                <i class="fas fa-star"></i> Featured
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($listing['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted small mb-2">
                                    <?php echo htmlspecialchars(substr($listing['description'], 0, 150)); ?>...
                                </p>
                                <p class="mb-1">
                                    <span class="badge bg-info"><?php echo htmlspecialchars($listing['category_name']); ?></span>
                                    <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $listing['condition_status'])); ?></span>
                                </p>
                                <p class="mb-0 small text-muted">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($listing['location'] ?? $listing['user_location'] ?? 'N/A'); ?>
                                    | <i class="fas fa-user"></i> <?php echo htmlspecialchars($listing['username']); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex flex-column justify-content-center align-items-center border-start">
                            <div class="text-center p-3">
                                <h4 class="mb-2" style="color: var(--secondary-color);">
                                    <?php echo formatSearchPrice($listing['price'], $listing); ?>
                                </h4>
                                <p class="small text-muted mb-2">
                                    <i class="far fa-clock"></i> <?php echo date('M d, Y', strtotime($listing['created_at'])); ?>
                                </p>
                                <p class="small text-muted mb-3">
                                    <i class="fas fa-eye"></i> <?php echo $listing['views']; ?> views
                                </p>
                                <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                                   class="btn btn-sm px-3" style="background: var(--primary-gradient); color: white;">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Search results pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <!-- Previous Page -->
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo buildQueryString(['page']); ?>&page=<?php echo $page - 1; ?>" 
                           aria-label="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo buildQueryString(['page']); ?>&page=1">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo buildQueryString(['page']); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo buildQueryString(['page']); ?>&page=<?php echo $total_pages; ?>">
                            <?php echo $total_pages; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Next Page -->
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo buildQueryString(['page']); ?>&page=<?php echo $page + 1; ?>" 
                           aria-label="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Update sort and reload
function updateSort(sortValue) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('sort', sortValue);
    urlParams.delete('page'); // Reset to first page
    window.location.search = urlParams.toString();
}

// Toggle view (grid/list)
function setView(view) {
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');
    
    if (view === 'grid') {
        gridView.style.display = 'flex';
        listView.style.display = 'none';
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
        localStorage.setItem('searchView', 'grid');
    } else {
        gridView.style.display = 'none';
        listView.style.display = 'block';
        gridBtn.classList.remove('active');
        listBtn.classList.add('active');
        localStorage.setItem('searchView', 'list');
    }
}

// Load saved view preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('searchView') || 'grid';
    setView(savedView);
});

// Auto-submit form when changing category, currency, or condition
document.getElementById('category')?.addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('currency_id')?.addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('condition')?.addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});

document.getElementById('featured')?.addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});
</script>

<?php require_once 'includes/footer.php'; ?>