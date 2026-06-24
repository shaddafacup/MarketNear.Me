<?php
// delete-listing.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$current_user_id = $_SESSION['user_id'];
$listing_id = (int)($_GET['id'] ?? 0);

// Validate CSRF token
if (!Security::validateCSRFToken($_GET['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = 'Invalid request. Please try again.';
    header("Location: /marketnearme/my-listings.php");
    exit();
}

if ($listing_id <= 0) {
    $_SESSION['error_message'] = 'Invalid listing ID.';
    header("Location: /marketnearme/my-listings.php");
    exit();
}

// Verify ownership
$stmt = $conn->prepare("SELECT id, title FROM listings WHERE id = :id AND user_id = :user_id");
$stmt->execute([
    ':id' => $listing_id,
    ':user_id' => $current_user_id
]);
$listing = $stmt->fetch();

if (!$listing) {
    $_SESSION['error_message'] = 'Listing not found or you do not have permission to delete it.';
    header("Location: /marketnearme/my-listings.php");
    exit();
}

try {
    $conn->beginTransaction();
    
    // Get all images to delete files
    $stmt = $conn->prepare("SELECT image_path FROM listing_images WHERE listing_id = :listing_id");
    $stmt->execute([':listing_id' => $listing_id]);
    $images = $stmt->fetchAll();
    
    // Delete image files
    foreach ($images as $image) {
        $filepath = __DIR__ . '/uploads/listings/' . $image['image_path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
    
    // Delete listing (cascade will handle related records)
    $stmt = $conn->prepare("DELETE FROM listings WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $listing_id,
        ':user_id' => $current_user_id
    ]);
    
    $conn->commit();
    
    Security::logSecurityEvent('listing_deleted', "Listing deleted: " . $listing['title'], $current_user_id);
    
    $_SESSION['success_message'] = 'Listing "' . htmlspecialchars($listing['title']) . '" deleted successfully!';
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error deleting listing: " . $e->getMessage());
    $_SESSION['error_message'] = 'Failed to delete listing. Please try again.';
}

header("Location: /marketnearme/my-listings.php");
exit();
?>