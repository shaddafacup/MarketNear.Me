<?php
// profile.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

// Check authentication BEFORE any HTML output
Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            $password_errors = Security::validatePasswordStrength($new_password);
            if (!empty($password_errors)) {
                $error = implode('<br>', $password_errors);
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([
                    ':password' => $hashed_password,
                    ':id' => $_SESSION['user_id']
                ]);
                $success = 'Password changed successfully!';
                Security::logSecurityEvent('password_changed', 'User changed password', $_SESSION['user_id']);
            }
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $full_name = Security::sanitize($_POST['full_name'] ?? '');
        $phone = Security::sanitize($_POST['phone'] ?? '');
        $location = Security::sanitize($_POST['location'] ?? '');
        $preferred_currency_id = (int)($_POST['preferred_currency_id'] ?? 1);
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        
        if (empty($full_name)) {
            $error = 'Full name is required.';
        } else {
            $stmt = $conn->prepare("
                UPDATE users SET 
                full_name = :full_name, 
                phone = :phone, 
                location = :location,
                preferred_currency_id = :currency_id
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':location' => $location,
                ':currency_id' => $preferred_currency_id,
                ':id' => $_SESSION['user_id']
            ]);
            
            $success = 'Profile updated successfully!';
            Security::logSecurityEvent('profile_updated', 'User updated profile', $_SESSION['user_id']);
        }
    }
}

// Handle avatar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $image_errors = Security::validateFileUpload($_FILES['avatar'], ['jpg', 'jpeg', 'png', 'gif'], 2097152);
        
        if (!empty($image_errors)) {
            $error = implode('<br>', $image_errors);
        } else {
            $upload_dir = __DIR__ . '/uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
                $stmt->execute([
                    ':avatar' => $new_filename,
                    ':id' => $_SESSION['user_id']
                ]);
                $success = 'Avatar updated successfully!';
            } else {
                $error = 'Failed to upload avatar.';
            }
        }
    }
}

// Get user details
$stmt = $conn->prepare("
    SELECT u.*, c.code as preferred_currency_code, c.symbol as preferred_currency_symbol 
    FROM users u 
    LEFT JOIN currencies c ON u.preferred_currency_id = c.id 
    WHERE u.id = :id
");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's listings
$stmt = $conn->prepare("
    SELECT l.*, 
           c.name as category_name,
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           cur.decimal_separator as currency_decimal_separator,
           cur.thousands_separator as currency_thousands_separator,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           (SELECT COUNT(*) FROM messages WHERE listing_id = l.id AND receiver_id = :user_id1) as message_count,
           (SELECT COUNT(*) FROM favorites WHERE listing_id = l.id) as favorite_count
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE l.user_id = :user_id2
    ORDER BY l.created_at DESC
");

$stmt->execute([
    ':user_id1' => $_SESSION['user_id'],
    ':user_id2' => $_SESSION['user_id']
]);
$user_listings = $stmt->fetchAll();

// Get user's favorites
$stmt = $conn->prepare("
    SELECT l.*, 
           cur.symbol as currency_symbol,
           cur.symbol_position as currency_symbol_position,
           cur.decimal_places as currency_decimal_places,
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image,
           f.created_at as favorited_at
    FROM favorites f
    JOIN listings l ON f.listing_id = l.id
    LEFT JOIN currencies cur ON l.currency_id = cur.id
    WHERE f.user_id = :user_id AND l.status = 'active'
    ORDER BY f.created_at DESC
    LIMIT 6
");

$stmt->execute([':user_id' => $_SESSION['user_id']]);
$favorites = $stmt->fetchAll();

// Get user stats
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM listings WHERE user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$total_listings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM listings WHERE user_id = :user_id AND status = 'active'");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$active_listings = $stmt->fetch()['total'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM messages 
    WHERE sender_id = :user_id OR receiver_id = :user_id2
");
$stmt->execute([
    ':user_id' => $_SESSION['user_id'],
    ':user_id2' => $_SESSION['user_id']
]);
$total_messages = $stmt->fetch()['total'];

// Get all currencies for the form
$all_currencies = Currency::getAllCurrencies();

// Format price helper
function formatPriceDisplay($price, $listing) {
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

// Now include header AFTER all processing
require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Left Sidebar -->
        <div class="col-lg-4 mb-4">
            <!-- Profile Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <div class="position-relative d-inline-block mb-3">
                        <?php if ($user['avatar'] && file_exists('uploads/avatars/' . $user['avatar'])): ?>
                        <img src="/marketnearme/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                             class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid var(--primary-color);">
                        <?php else: ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 120px; height: 120px; background: var(--primary-gradient); color: white; font-size: 3rem; font-weight: 700; border: 3px solid var(--primary-color);">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-light rounded-circle position-absolute bottom-0 end-0" 
                                onclick="document.getElementById('avatarInput').click()"
                                title="Change avatar">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    
                    <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                        <?php if ($user['email_verified']): ?>
                        <span class="badge bg-info">Verified</span>
                        <?php endif; ?>
                        <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge bg-warning">Admin</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="bg-light rounded p-2">
                                <h6 class="mb-0"><?php echo $total_listings; ?></h6>
                                <small class="text-muted">Listings</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded p-2">
                                <h6 class="mb-0"><?php echo $active_listings; ?></h6>
                                <small class="text-muted">Active</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded p-2">
                                <h6 class="mb-0"><?php echo $total_messages; ?></h6>
                                <small class="text-muted">Messages</small>
                            </div>
                        </div>
                    </div>
                    
                    <p class="small text-muted mb-1">
                        <i class="far fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                    </p>
                    <?php if ($user['last_login']): ?>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-clock"></i> Last active: <?php echo date('M d, Y', strtotime($user['last_login'])); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-link me-2" style="color: var(--primary-color);"></i>Quick Links</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/marketnearme/post-ad.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2" style="color: var(--success-color);"></i> Post New Ad
                    </a>
                    <a href="/marketnearme/my-listings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list me-2" style="color: var(--primary-color);"></i> My Listings
                    </a>
                    <a href="/marketnearme/messages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope me-2" style="color: var(--accent-color);"></i> Messages
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM messages WHERE receiver_id = :uid AND is_read = 0");
                        $stmt->execute([':uid' => $_SESSION['user_id']]);
                        $unread = $stmt->fetch()['total'];
                        if ($unread > 0):
                        ?>
                        <span class="badge bg-danger float-end"><?php echo $unread; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/marketnearme/favorites.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-heart me-2" style="color: var(--danger-color);"></i> Favorites
                    </a>
                </div>
            </div>
            
            <!-- Hidden form for avatar upload -->
            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="upload_avatar">
                <input type="file" id="avatarInput" name="avatar" accept="image/*" 
                       onchange="document.getElementById('avatarForm').submit()">
            </form>
        </div>
        
        <!-- Right Content -->
        <div class="col-lg-8">
            <!-- Tabs -->
            <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="edit-tab" data-bs-toggle="pill" data-bs-target="#edit" type="button">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button">
                        <i class="fas fa-lock"></i> Password
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="listings-tab" data-bs-toggle="pill" data-bs-target="#listings" type="button">
                        <i class="fas fa-list"></i> My Listings
                        <span class="badge bg-primary ms-1"><?php echo count($user_listings); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="favorites-tab" data-bs-toggle="pill" data-bs-target="#favorites" type="button">
                        <i class="fas fa-heart"></i> Favorites
                        <span class="badge bg-danger ms-1"><?php echo count($favorites); ?></span>
                    </button>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="profileTabsContent">
                <!-- Edit Profile Tab -->
                <div class="tab-pane fade show active" id="edit">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2" style="color: var(--primary-color);"></i>Edit Profile</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                        <div class="form-text">Username cannot be changed</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <div class="form-text">
                                        Email cannot be changed
                                        <?php if (!$user['email_verified']): ?>
                                        <span class="text-warning"> - Not verified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                               placeholder="+1234567890">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location" 
                                               value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>"
                                               placeholder="City, State/Country">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="preferred_currency_id" class="form-label">Preferred Currency</label>
                                    <select class="form-select" id="preferred_currency_id" name="preferred_currency_id">
                                        <?php foreach ($all_currencies as $currency): ?>
                                        <option value="<?php echo $currency['id']; ?>" 
                                            <?php echo ($user['preferred_currency_id'] ?? 1) == $currency['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($currency['name']); ?> 
                                            (<?php echo $currency['code']; ?>) - 
                                            <?php echo htmlspecialchars($currency['symbol']); ?>
                                            <?php echo $currency['is_default'] ? ' (Default)' : ''; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i> 
                                        This will be the default currency when you create new listings.
                                        Current: <strong><?php echo htmlspecialchars($user['preferred_currency_symbol'] ?? '$'); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="email_notifications" 
                                           name="email_notifications" checked>
                                    <label class="form-check-label" for="email_notifications">
                                        Receive email notifications for messages and updates
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-lg px-4" style="background: var(--primary-gradient); color: white;">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Password Tab -->
                <div class="tab-pane fade" id="password">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-lock me-2" style="color: var(--secondary-color);"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" onsubmit="return validatePasswordForm()">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" 
                                               name="current_password" required>
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('current_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" 
                                               name="new_password" required
                                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{8,}$">
                                        <button class="btn btn-outline-secondary" type="button" 
                                                onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        Password must be at least 8 characters with uppercase, lowercase, number, and special character.
                                    </div>
                                    <div class="password-strength mt-2" id="passwordStrength" style="display: none;">
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar" id="strengthBar" style="width: 0%;"></div>
                                        </div>
                                        <small class="text-muted" id="strengthText"></small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                    <div class="invalid-feedback">Passwords do not match.</div>
                                </div>
                                
                                <button type="submit" class="btn btn-lg px-4" style="background: var(--secondary-gradient); color: white;">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- My Listings Tab -->
                <div class="tab-pane fade" id="listings">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2" style="color: var(--primary-color);"></i>My Listings</h5>
                            <a href="/marketnearme/post-ad.php" class="btn btn-sm" style="background: var(--primary-gradient); color: white;">
                                <i class="fas fa-plus"></i> Post New Ad
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($user_listings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-4x mb-3" style="color: var(--gray-300);"></i>
                                <h5>No listings yet</h5>
                                <p class="text-muted">Start selling by posting your first ad!</p>
                                <a href="/marketnearme/post-ad.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Post Your First Ad
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">Image</th>
                                            <th>Title</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Views</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_listings as $listing): ?>
                                        <tr>
                                            <td>
                                                <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($listing['primary_image'] ?? 'default-listing.jpg'); ?>" 
                                                     alt="" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" class="text-decoration-none">
                                                    <strong><?php echo htmlspecialchars($listing['title']); ?></strong>
                                                </a>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($listing['category_name']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo formatPriceDisplay($listing['price'], $listing); ?></strong>
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
                                                <i class="fas fa-eye text-muted"></i> <?php echo $listing['views']; ?>
                                                <?php if ($listing['favorite_count'] > 0): ?>
                                                <br><i class="fas fa-heart text-danger"></i> <?php echo $listing['favorite_count']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/marketnearme/product.php?id=<?php echo $listing['id']; ?>" 
                                                       class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/marketnearme/edit-listing.php?id=<?php echo $listing['id']; ?>" 
                                                       class="btn btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="deleteListing(<?php echo $listing['id']; ?>)" 
                                                            class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Favorites Tab -->
                <div class="tab-pane fade" id="favorites">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-heart me-2" style="color: var(--danger-color);"></i>My Favorites</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($favorites)): ?>
                            <div class="text-center py-5">
                                <i class="far fa-heart fa-4x mb-3" style="color: var(--gray-300);"></i>
                                <h5>No favorites yet</h5>
                                <p class="text-muted">Start browsing and save listings you like!</p>
                                <a href="/marketnearme/search.php" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Browse Listings
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="row">
                                <?php foreach ($favorites as $fav): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="position-relative" style="height: 150px;">
                                            <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($fav['primary_image'] ?? 'default-listing.jpg'); ?>" 
                                                 class="w-100 h-100" style="object-fit: cover;" 
                                                 alt="<?php echo htmlspecialchars($fav['title']); ?>">
                                            <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle"
                                                    onclick="removeFavorite(<?php echo $fav['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($fav['title']); ?></h6>
                                            <p class="card-text">
                                                <strong><?php echo formatPriceDisplay($fav['price'], $fav); ?></strong>
                                            </p>
                                            <a href="/marketnearme/product.php?id=<?php echo $fav['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary w-100">View Details</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength meter
document.getElementById('new_password')?.addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const strengthDiv = document.getElementById('passwordStrength');
    
    if (password.length === 0) {
        strengthDiv.style.display = 'none';
        return;
    }
    
    strengthDiv.style.display = 'block';
    
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[!@#$%^&*()\-_=+{};:,<.>]/)) strength++;
    
    const percentage = (strength / 5) * 100;
    strengthBar.style.width = percentage + '%';
    
    if (strength <= 2) {
        strengthBar.className = 'progress-bar bg-danger';
        strengthText.textContent = 'Weak password';
    } else if (strength <= 3) {
        strengthBar.className = 'progress-bar bg-warning';
        strengthText.textContent = 'Fair password';
    } else if (strength <= 4) {
        strengthBar.className = 'progress-bar bg-info';
        strengthText.textContent = 'Good password';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        strengthText.textContent = 'Strong password';
    }
});

// Validate password form
function validatePasswordForm() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        document.getElementById('confirm_password').classList.add('is-invalid');
        return false;
    }
    
    return true;
}

// Delete listing
function deleteListing(id) {
    if (confirm('Are you sure you want to delete this listing? This action cannot be undone.')) {
        window.location.href = `/marketnearme/delete-listing.php?id=${id}&csrf_token=<?php echo Security::generateCSRFToken(); ?>`;
    }
}

// Remove favorite
function removeFavorite(listingId) {
    if (confirm('Remove this listing from favorites?')) {
        fetch('/marketnearme/favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `listing_id=${listingId}&csrf_token=<?php echo Security::generateCSRFToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

// Activate tab from URL hash
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tab) {
            new bootstrap.Tab(tab).show();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>