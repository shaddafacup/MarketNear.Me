<?php
// favorite.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to favorite listings.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit();
    }
    
    $listing_id = (int)($_POST['listing_id'] ?? 0);
    
    if ($listing_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid listing.']);
        exit();
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if already favorited
    $stmt = $conn->prepare("
        SELECT id FROM favorites 
        WHERE user_id = :user_id AND listing_id = :listing_id
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':listing_id' => $listing_id
    ]);
    
    if ($favorite = $stmt->fetch()) {
        // Remove favorite
        $stmt = $conn->prepare("DELETE FROM favorites WHERE id = :id");
        $stmt->execute([':id' => $favorite['id']]);
        echo json_encode(['success' => true, 'favorited' => false]);
    } else {
        // Add favorite
        $stmt = $conn->prepare("
            INSERT INTO favorites (user_id, listing_id) 
            VALUES (:user_id, :listing_id)
        ");
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':listing_id' => $listing_id
        ]);
        echo json_encode(['success' => true, 'favorited' => true]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>