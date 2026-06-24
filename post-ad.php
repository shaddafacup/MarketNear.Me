<?php
// post-ad.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

// Require login before any output
Auth::requireLogin();

// Get user's preferred currency
$user_currency = Currency::getUserCurrency($_SESSION['user_id']);

// Process form submission BEFORE any HTML output
$errors = [];
$success = '';
$form_data = [
    'title' => '',
    'category_id' => '',
    'description' => '',
    'price' => '',
    'currency_id' => $user_currency['id'] ?? 1,
    'condition_status' => 'used',
    'location' => '',
    'contact_phone' => '',
    'contact_email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        $form_data['title'] = Security::sanitize($_POST['title'] ?? '');
        $form_data['category_id'] = (int)($_POST['category_id'] ?? 0);
        $form_data['description'] = $_POST['description'] ?? '';
        $form_data['price'] = (float)($_POST['price'] ?? 0);
        $form_data['currency_id'] = (int)($_POST['currency_id'] ?? 1);
        $form_data['condition_status'] = $_POST['condition_status'] ?? 'used';
        $form_data['location'] = Security::sanitize($_POST['location'] ?? '');
        $form_data['contact_phone'] = Security::sanitize($_POST['contact_phone'] ?? '');
        $form_data['contact_email'] = Security::sanitize($_POST['contact_email'] ?? '');
        
        // Validate required fields
        if (empty($form_data['title'])) $errors[] = 'Title is required.';
        if (empty($form_data['category_id'])) $errors[] = 'Category is required.';
        if (empty($form_data['description'])) $errors[] = 'Description is required.';
        if ($form_data['price'] <= 0) $errors[] = 'Valid price is required.';
        
        // Validate currency
        $selected_currency = Currency::getCurrencyById($form_data['currency_id']);
        if (!$selected_currency) {
            $errors[] = 'Invalid currency selected.';
        }
        
        // Validate images
        $uploaded_images = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['images']['error'][$key],
                        'size' => $_FILES['images']['size'][$key]
                    ];
                    
                    $image_errors = Security::validateFileUpload($file);
                    if (empty($image_errors)) {
                        $uploaded_images[] = $file;
                    } else {
                        $errors = array_merge($errors, $image_errors);
                    }
                }
            }
        } else {
            $errors[] = 'At least one image is required.';
        }
        
        if (count($uploaded_images) > 5) {
            $errors[] = 'Maximum 5 images allowed.';
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Generate unique slug
                $slug = Security::generateSlug($form_data['title']);
                $original_slug = $slug;
                $counter = 1;
                
                $stmt = $conn->prepare("SELECT id FROM listings WHERE slug = :slug");
                $stmt->execute([':slug' => $slug]);
                while ($stmt->fetch()) {
                    $slug = $original_slug . '-' . $counter;
                    $counter++;
                    $stmt->execute([':slug' => $slug]);
                }
                
                // Insert listing with currency
                $stmt = $conn->prepare("
                    INSERT INTO listings (user_id, category_id, title, slug, description, price, currency_id, condition_status, location, contact_phone, contact_email) 
                    VALUES (:user_id, :category_id, :title, :slug, :description, :price, :currency_id, :condition_status, :location, :contact_phone, :contact_email)
                ");
                
                $stmt->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':category_id' => $form_data['category_id'],
                    ':title' => $form_data['title'],
                    ':slug' => $slug,
                    ':description' => $form_data['description'],
                    ':price' => $form_data['price'],
                    ':currency_id' => $form_data['currency_id'],
                    ':condition_status' => $form_data['condition_status'],
                    ':location' => $form_data['location'],
                    ':contact_phone' => $form_data['contact_phone'],
                    ':contact_email' => $form_data['contact_email']
                ]);
                
                $listing_id = $conn->lastInsertId();
                
                // Upload images
                $upload_dir = __DIR__ . '/uploads/listings/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($uploaded_images as $key => $image) {
                    $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($image['tmp_name'], $upload_path)) {
                        $is_primary = ($key === 0) ? 1 : 0;
                        
                        $stmt = $conn->prepare("
                            INSERT INTO listing_images (listing_id, image_path, is_primary, sort_order) 
                            VALUES (:listing_id, :image_path, :is_primary, :sort_order)
                        ");
                        
                        $stmt->execute([
                            ':listing_id' => $listing_id,
                            ':image_path' => $new_filename,
                            ':is_primary' => $is_primary,
                            ':sort_order' => $key
                        ]);
                    }
                }
                
                $conn->commit();
                
                Security::logSecurityEvent('listing_created', "New listing created: " . $form_data['title'], $_SESSION['user_id']);
                
                $_SESSION['success_message'] = 'Listing posted successfully!';
                header("Location: /marketnearme/product.php?id=$listing_id");
                exit();
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Error creating listing: " . $e->getMessage());
                $errors[] = 'Failed to post listing. Please try again.';
            }
        }
    }
}

// Update user's preferred currency if changed
if ($form_data['currency_id'] != $user_currency['id']) {
    Currency::setUserCurrency($_SESSION['user_id'], $form_data['currency_id']);
}

// Now include header
require_once 'includes/header.php';

// Get categories
$categories = DBHelper::getCategories();
$all_currencies = Currency::getAllCurrencies();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-4">Post a Free Ad</h1>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" id="postAdForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background: var(--primary-gradient); color: white;">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Listing Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   required maxlength="200" 
                                   value="<?php echo htmlspecialchars($form_data['title']); ?>"
                                   placeholder="e.g., iPhone 12 Pro Max 256GB - Excellent Condition">
                            <div class="invalid-feedback">Please enter a title for your listing.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $form_data['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="5" required 
                                      placeholder="Describe your item in detail..."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                            <div class="invalid-feedback">Please enter a description.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label for="price" class="form-label">Price *</label>
                                <input type="number" class="form-control" id="price" name="price" 
                                       required min="0" step="0.01" 
                                       value="<?php echo htmlspecialchars($form_data['price']); ?>"
                                       placeholder="0.00">
                                <div class="invalid-feedback">Please enter a valid price.</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="currency_id" class="form-label">Currency *</label>
                                <select class="form-select" id="currency_id" name="currency_id" required 
                                        onchange="updatePricePreview()">
                                    <?php echo Currency::getCurrencyOptions($form_data['currency_id']); ?>
                                </select>
                                <div class="invalid-feedback">Please select a currency.</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Preview</label>
                                <div class="form-control bg-light" id="pricePreview" style="font-weight: 600;">
                                    --
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="condition_status" class="form-label">Condition</label>
                                <select class="form-select" id="condition_status" name="condition_status">
                                    <option value="new" <?php echo $form_data['condition_status'] == 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="like_new" <?php echo $form_data['condition_status'] == 'like_new' ? 'selected' : ''; ?>>Like New</option>
                                    <option value="good" <?php echo $form_data['condition_status'] == 'good' ? 'selected' : ''; ?>>Good</option>
                                    <option value="fair" <?php echo $form_data['condition_status'] == 'fair' ? 'selected' : ''; ?>>Fair</option>
                                    <option value="used" <?php echo $form_data['condition_status'] == 'used' ? 'selected' : ''; ?>>Used</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background: var(--accent-gradient); color: white;">
                        <h5 class="mb-0"><i class="fas fa-images"></i> Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="images" class="form-label">Upload Images * (Max 5 images)</label>
                            <input type="file" class="form-control" id="images" name="images[]" 
                                   accept="image/jpeg,image/png,image/gif" multiple required>
                            <div class="form-text">Supported formats: JPG, PNG, GIF. Max size: 5MB each.</div>
                        </div>
                        <div id="imagePreview" class="row mt-3"></div>
                    </div>
                </div>
                
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background: var(--secondary-gradient); color: white;">
                        <h5 class="mb-0"><i class="fas fa-address-book"></i> Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($form_data['location']); ?>"
                                   placeholder="City, State">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone"
                                       value="<?php echo htmlspecialchars($form_data['contact_phone']); ?>"
                                       placeholder="+1234567890">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email"
                                       value="<?php echo htmlspecialchars($form_data['contact_email']); ?>"
                                       placeholder="your@email.com">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mb-5">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-check-circle"></i> Post Listing
                    </button>
                    <a href="/marketnearme/index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Currency data for preview
const currencyData = <?php echo json_encode($all_currencies); ?>;

// Update price preview
function updatePricePreview() {
    const price = document.getElementById('price').value;
    const currencyId = document.getElementById('currency_id').value;
    const preview = document.getElementById('pricePreview');
    
    if (!price || price <= 0) {
        preview.textContent = '--';
        return;
    }
    
    const currency = currencyData.find(c => c.id == currencyId);
    if (!currency) {
        preview.textContent = '--';
        return;
    }
    
    let formattedPrice = parseFloat(price).toLocaleString('en-US', {
        minimumFractionDigits: currency.decimal_places,
        maximumFractionDigits: currency.decimal_places
    });
    
    // Replace separators based on currency settings
    if (currency.decimal_separator !== '.' || currency.thousands_separator !== ',') {
        const parts = formattedPrice.split('.');
        const integerPart = parts[0].replace(/,/g, currency.thousands_separator);
        const decimalPart = parts[1] || '';
        formattedPrice = integerPart + (decimalPart ? currency.decimal_separator + decimalPart : '');
    }
    
    if (currency.symbol_position === 'before') {
        preview.textContent = currency.symbol + ' ' + formattedPrice;
    } else {
        preview.textContent = formattedPrice + ' ' + currency.symbol;
    }
}

// Event listeners
document.getElementById('price').addEventListener('input', updatePricePreview);
document.getElementById('currency_id').addEventListener('change', updatePricePreview);

// Initial preview
document.addEventListener('DOMContentLoaded', updatePricePreview);

// Image preview
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    const files = Array.from(e.target.files).slice(0, 5);
    
    files.forEach((file, index) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3 mb-2';
            col.innerHTML = `
                <div class="position-relative">
                    <img src="${e.target.result}" class="img-thumbnail" style="height: 120px; width: 100%; object-fit: cover;">
                    <span class="badge bg-primary position-absolute top-0 start-0 m-1">
                        ${index === 0 ? 'Main' : '#' + (index + 1)}
                    </span>
                </div>
            `;
            preview.appendChild(col);
        }
        
        reader.readAsDataURL(file);
    });
});

// Form validation
document.getElementById('postAdForm').addEventListener('submit', function(e) {
    let isValid = true;
    const requiredFields = this.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value || (field.type === 'file' && field.files.length === 0)) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        const firstError = this.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>