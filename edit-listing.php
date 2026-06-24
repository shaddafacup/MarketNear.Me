<?php
// edit-listing.php
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

$current_user_id = $_SESSION['user_id'];
$listing_id = (int)($_GET['id'] ?? 0);
$errors = [];
$success = '';

// Verify listing exists and belongs to user
$stmt = $conn->prepare("
    SELECT l.*, 
           (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM listings l 
    WHERE l.id = :id AND l.user_id = :user_id
");
$stmt->execute([
    ':id' => $listing_id,
    ':user_id' => $current_user_id
]);
$listing = $stmt->fetch();

if (!$listing) {
    $_SESSION['error_message'] = 'Listing not found or you do not have permission to edit it.';
    header("Location: /marketnearme/my-listings.php");
    exit();
}

// Get existing images
$stmt = $conn->prepare("SELECT * FROM listing_images WHERE listing_id = :listing_id ORDER BY sort_order ASC");
$stmt->execute([':listing_id' => $listing_id]);
$existing_images = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $title = Security::sanitize($_POST['title'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = $_POST['description'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $currency_id = (int)($_POST['currency_id'] ?? 1);
        $condition_status = $_POST['condition_status'] ?? 'used';
        $location = Security::sanitize($_POST['location'] ?? '');
        $contact_phone = Security::sanitize($_POST['contact_phone'] ?? '');
        $contact_email = Security::sanitize($_POST['contact_email'] ?? '');
        $status = $_POST['status'] ?? $listing['status'];
        
        // Validate required fields
        if (empty($title)) $errors[] = 'Title is required.';
        if (empty($category_id)) $errors[] = 'Category is required.';
        if (empty($description)) $errors[] = 'Description is required.';
        if ($price <= 0) $errors[] = 'Valid price is required.';
        
        // Handle image deletions
        $delete_images = $_POST['delete_images'] ?? [];
        
        // Handle new image uploads
        $new_images = [];
        $has_new_images = false;
        
        if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
            $has_new_images = true;
            foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['new_images']['name'][$key],
                        'type' => $_FILES['new_images']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['new_images']['error'][$key],
                        'size' => $_FILES['new_images']['size'][$key]
                    ];
                    
                    $image_errors = Security::validateFileUpload($file);
                    if (empty($image_errors)) {
                        $new_images[] = $file;
                    } else {
                        $errors = array_merge($errors, $image_errors);
                    }
                }
            }
        }
        
        // Check total images count
        $remaining_images = count($existing_images) - count($delete_images);
        $total_after_update = $remaining_images + count($new_images);
        
        if ($total_after_update < 1) {
            $errors[] = 'At least one image is required.';
        } elseif ($total_after_update > 5) {
            $errors[] = 'Maximum 5 images allowed. You currently have ' . $remaining_images . ' image(s) and trying to add ' . count($new_images) . ' more.';
        }
        
        // Set primary image
        $primary_image_id = (int)($_POST['primary_image'] ?? 0);
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Update listing
                $stmt = $conn->prepare("
                    UPDATE listings SET 
                    title = :title,
                    category_id = :category_id,
                    description = :description,
                    price = :price,
                    currency_id = :currency_id,
                    condition_status = :condition_status,
                    location = :location,
                    contact_phone = :contact_phone,
                    contact_email = :contact_email,
                    status = :status
                    WHERE id = :id AND user_id = :user_id
                ");
                
                $stmt->execute([
                    ':title' => $title,
                    ':category_id' => $category_id,
                    ':description' => $description,
                    ':price' => $price,
                    ':currency_id' => $currency_id,
                    ':condition_status' => $condition_status,
                    ':location' => $location,
                    ':contact_phone' => $contact_phone,
                    ':contact_email' => $contact_email,
                    ':status' => $status,
                    ':id' => $listing_id,
                    ':user_id' => $current_user_id
                ]);
                
                // Delete selected images
                if (!empty($delete_images)) {
                    foreach ($delete_images as $image_id) {
                        // Get image path before deleting
                        $stmt = $conn->prepare("SELECT image_path FROM listing_images WHERE id = :id AND listing_id = :listing_id");
                        $stmt->execute([
                            ':id' => $image_id,
                            ':listing_id' => $listing_id
                        ]);
                        $image = $stmt->fetch();
                        
                        if ($image) {
                            // Delete file
                            $filepath = __DIR__ . '/uploads/listings/' . $image['image_path'];
                            if (file_exists($filepath)) {
                                unlink($filepath);
                            }
                            
                            // Delete database record
                            $stmt = $conn->prepare("DELETE FROM listing_images WHERE id = :id");
                            $stmt->execute([':id' => $image_id]);
                        }
                    }
                }
                
                // Upload new images
                if (!empty($new_images)) {
                    $upload_dir = __DIR__ . '/uploads/listings/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Get max sort order
                    $stmt = $conn->prepare("SELECT MAX(sort_order) as max_order FROM listing_images WHERE listing_id = :listing_id");
                    $stmt->execute([':listing_id' => $listing_id]);
                    $max_order = $stmt->fetch()['max_order'] ?? 0;
                    
                    foreach ($new_images as $key => $image) {
                        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($image['tmp_name'], $upload_path)) {
                            $sort_order = $max_order + $key + 1;
                            
                            $stmt = $conn->prepare("
                                INSERT INTO listing_images (listing_id, image_path, is_primary, sort_order) 
                                VALUES (:listing_id, :image_path, 0, :sort_order)
                            ");
                            
                            $stmt->execute([
                                ':listing_id' => $listing_id,
                                ':image_path' => $new_filename,
                                ':sort_order' => $sort_order
                            ]);
                        }
                    }
                }
                
                // Update primary image
                if ($primary_image_id > 0) {
                    // Reset all to non-primary
                    $stmt = $conn->prepare("UPDATE listing_images SET is_primary = 0 WHERE listing_id = :listing_id");
                    $stmt->execute([':listing_id' => $listing_id]);
                    
                    // Set selected as primary
                    $stmt = $conn->prepare("UPDATE listing_images SET is_primary = 1 WHERE id = :id AND listing_id = :listing_id");
                    $stmt->execute([
                        ':id' => $primary_image_id,
                        ':listing_id' => $listing_id
                    ]);
                } else {
                    // If no primary set, make first image primary
                    $stmt = $conn->prepare("
                        UPDATE listing_images SET is_primary = 1 
                        WHERE listing_id = :listing_id 
                        ORDER BY sort_order ASC 
                        LIMIT 1
                    ");
                    $stmt->execute([':listing_id' => $listing_id]);
                }
                
                $conn->commit();
                
                Security::logSecurityEvent('listing_updated', "Listing ID: $listing_id updated", $current_user_id);
                
                $_SESSION['success_message'] = 'Listing updated successfully!';
                header("Location: /marketnearme/product.php?id=$listing_id");
                exit();
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Error updating listing: " . $e->getMessage());
                $errors[] = 'Failed to update listing. Please try again.';
            }
        }
        
        // Update listing variable with new values for form
        $listing['title'] = $title;
        $listing['category_id'] = $category_id;
        $listing['description'] = $description;
        $listing['price'] = $price;
        $listing['currency_id'] = $currency_id;
        $listing['condition_status'] = $condition_status;
        $listing['location'] = $location;
        $listing['contact_phone'] = $contact_phone;
        $listing['contact_email'] = $contact_email;
        $listing['status'] = $status;
    }
}

// Get categories for dropdown
$categories = DBHelper::getCategories();

// Get all currencies
$all_currencies = Currency::getAllCurrencies();

// Now include header
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/marketnearme/">Home</a></li>
                    <li class="breadcrumb-item"><a href="/marketnearme/my-listings.php">My Listings</a></li>
                    <li class="breadcrumb-item active">Edit Listing</li>
                </ol>
            </nav>
            
            <h1 class="mb-4">
                <i class="fas fa-edit me-2" style="color: var(--primary-color);"></i>Edit Listing
            </h1>
            
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
            
            <form method="POST" action="" enctype="multipart/form-data" id="editListingForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                
                <!-- Listing Details Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background: var(--primary-gradient); color: white;">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Listing Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   required maxlength="200" 
                                   value="<?php echo htmlspecialchars($listing['title']); ?>">
                            <div class="invalid-feedback">Please enter a title.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $listing['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="6" required><?php echo htmlspecialchars($listing['description']); ?></textarea>
                            <div class="invalid-feedback">Please enter a description.</div>
                            <div class="form-text">
                                <span id="charCount">0</span>/5000 characters
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Price *</label>
                                <input type="number" class="form-control" id="price" name="price" 
                                       required min="0" step="0.01" 
                                       value="<?php echo htmlspecialchars($listing['price']); ?>">
                                <div class="invalid-feedback">Please enter a valid price.</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="currency_id" class="form-label">Currency *</label>
                                <select class="form-select" id="currency_id" name="currency_id" required>
                                    <?php foreach ($all_currencies as $currency): ?>
                                    <option value="<?php echo $currency['id']; ?>" 
                                        <?php echo ($listing['currency_id'] ?? 1) == $currency['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($currency['name']); ?> 
                                        (<?php echo $currency['code']; ?> - <?php echo htmlspecialchars($currency['symbol']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="condition_status" class="form-label">Condition</label>
                                <select class="form-select" id="condition_status" name="condition_status">
                                    <option value="new" <?php echo $listing['condition_status'] == 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="like_new" <?php echo $listing['condition_status'] == 'like_new' ? 'selected' : ''; ?>>Like New</option>
                                    <option value="good" <?php echo $listing['condition_status'] == 'good' ? 'selected' : ''; ?>>Good</option>
                                    <option value="fair" <?php echo $listing['condition_status'] == 'fair' ? 'selected' : ''; ?>>Fair</option>
                                    <option value="used" <?php echo $listing['condition_status'] == 'used' ? 'selected' : ''; ?>>Used</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Listing Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo $listing['status'] == 'active' ? 'selected' : ''; ?>>Active (Visible to everyone)</option>
                                <option value="inactive" <?php echo $listing['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden from public)</option>
                                <option value="sold" <?php echo $listing['status'] == 'sold' ? 'selected' : ''; ?>>Sold (Mark as sold)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Images Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background: var(--accent-gradient); color: white;">
                        <h5 class="mb-0"><i class="fas fa-images"></i> Images</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Manage your listing images. Select which image should be the primary/cover image.</p>
                        
                        <!-- Existing Images -->
                        <?php if (!empty($existing_images)): ?>
                        <h6>Current Images</h6>
                        <div class="row mb-3" id="existingImages">
                            <?php foreach ($existing_images as $image): ?>
                            <div class="col-6 col-md-4 col-lg-3 mb-3">
                                <div class="position-relative">
                                    <img src="/marketnearme/uploads/listings/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         class="img-thumbnail w-100" style="height: 150px; object-fit: cover;"
                                         alt="Listing image">
                                    
                                    <!-- Primary image radio -->
                                    <div class="position-absolute top-0 start-0 m-1">
                                        <input type="radio" name="primary_image" value="<?php echo $image['id']; ?>" 
                                               <?php echo $image['is_primary'] ? 'checked' : ''; ?>
                                               class="form-check-input" title="Set as primary image">
                                    </div>
                                    
                                    <!-- Delete checkbox -->
                                    <div class="position-absolute top-0 end-0 m-1">
                                        <div class="form-check">
                                            <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>" 
                                                   class="form-check-input delete-image-check" 
                                                   id="delete_<?php echo $image['id']; ?>">
                                            <label class="form-check-label" for="delete_<?php echo $image['id']; ?>">
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <?php if ($image['is_primary']): ?>
                                    <span class="position-absolute bottom-0 start-0 m-1 badge" style="background: var(--primary-gradient);">
                                        <i class="fas fa-star"></i> Primary
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No images found. Please upload new images.
                        </div>
                        <?php endif; ?>
                        
                        <!-- Upload New Images -->
                        <h6>Add New Images</h6>
                        <div class="mb-3">
                            <input type="file" class="form-control" id="new_images" name="new_images[]" 
                                   accept="image/jpeg,image/png,image/gif" multiple>
                            <div class="form-text">
                                Supported formats: JPG, PNG, GIF. Max size: 5MB each. 
                                You can upload up to <?php echo 5 - count($existing_images); ?> more image(s).
                            </div>
                        </div>
                        <div id="newImagePreview" class="row mt-3"></div>
                    </div>
                </div>
                
                <!-- Contact Information Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header" style="background: var(--secondary-gradient); color: white;">
                        <h5 class="mb-0"><i class="fas fa-address-book"></i> Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($listing['location'] ?? ''); ?>"
                                   placeholder="City, State/Country">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contact_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone"
                                       value="<?php echo htmlspecialchars($listing['contact_phone'] ?? ''); ?>"
                                       placeholder="+1234567890">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email"
                                       value="<?php echo htmlspecialchars($listing['contact_email'] ?? ''); ?>"
                                       placeholder="your@email.com">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-grid gap-2 mb-5">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="/marketnearme/product.php?id=<?php echo $listing_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-eye"></i> View Listing
                    </a>
                    <a href="/marketnearme/my-listings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to My Listings
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Character counter for description
const descriptionTextarea = document.getElementById('description');
const charCount = document.getElementById('charCount');

function updateCharCount() {
    const count = descriptionTextarea.value.length;
    charCount.textContent = count;
    
    if (count > 4500) {
        charCount.style.color = 'var(--danger-color)';
    } else if (count > 4000) {
        charCount.style.color = 'var(--warning-color)';
    } else {
        charCount.style.color = '';
    }
}

descriptionTextarea.addEventListener('input', updateCharCount);
updateCharCount(); // Initial count

// New image preview
document.getElementById('new_images').addEventListener('change', function(e) {
    const preview = document.getElementById('newImagePreview');
    preview.innerHTML = '';
    
    const files = Array.from(e.target.files).slice(0, 5);
    
    files.forEach((file, index) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 col-lg-3 mb-2';
            col.innerHTML = `
                <div class="position-relative">
                    <img src="${e.target.result}" class="img-thumbnail w-100" style="height: 120px; object-fit: cover;">
                    <span class="badge bg-info position-absolute top-0 end-0 m-1">
                        New #${index + 1}
                    </span>
                </div>
            `;
            preview.appendChild(col);
        }
        
        reader.readAsDataURL(file);
    });
});

// Warn when deleting images
document.querySelectorAll('.delete-image-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            const existingChecked = document.querySelectorAll('.delete-image-check:checked').length;
            const totalImages = document.querySelectorAll('.delete-image-check').length;
            const newImages = document.getElementById('new_images').files.length;
            
            if ((totalImages - existingChecked + newImages) < 1) {
                alert('You must keep at least one image for your listing.');
                this.checked = false;
            }
        }
    });
});

// Form validation
document.getElementById('editListingForm').addEventListener('submit', function(e) {
    let isValid = true;
    const requiredFields = this.querySelectorAll('[required]:not([type="file"])');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Check images
    const deleteCount = document.querySelectorAll('.delete-image-check:checked').length;
    const totalExisting = document.querySelectorAll('.delete-image-check').length;
    const newImageCount = document.getElementById('new_images').files.length;
    const remainingImages = totalExisting - deleteCount + newImageCount;
    
    if (remainingImages < 1) {
        alert('Your listing must have at least one image.');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        const firstError = this.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

// Clear validation styling on input
document.querySelectorAll('input, select, textarea').forEach(element => {
    element.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
        }
    });
});

// Confirm before leaving if changes made
let formChanged = false;
const formElements = document.querySelectorAll('#editListingForm input, #editListingForm select, #editListingForm textarea');
formElements.forEach(element => {
    element.addEventListener('change', () => formChanged = true);
    element.addEventListener('input', () => formChanged = true);
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
    }
});

// Remove beforeunload warning on form submit
document.getElementById('editListingForm').addEventListener('submit', function() {
    formChanged = false;
});
</script>

<?php require_once 'includes/footer.php'; ?>