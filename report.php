<?php
// report.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to report listings.']);
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit();
    }
    
    $listing_id = (int)($_POST['listing_id'] ?? 0);
    $reason = Security::sanitize($_POST['reason'] ?? '');
    $description = Security::sanitize($_POST['description'] ?? '');
    
    if ($listing_id <= 0 || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit();
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if user already reported this listing
        $stmt = $conn->prepare("
            SELECT id FROM reports 
            WHERE reporter_id = :reporter_id AND listing_id = :listing_id AND status = 'pending'
        ");
        $stmt->execute([
            ':reporter_id' => $_SESSION['user_id'],
            ':listing_id' => $listing_id
        ]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You have already reported this listing.']);
            exit();
        }
        
        // Insert report
        $stmt = $conn->prepare("
            INSERT INTO reports (reporter_id, listing_id, reason, description) 
            VALUES (:reporter_id, :listing_id, :reason, :description)
        ");
        
        $stmt->execute([
            ':reporter_id' => $_SESSION['user_id'],
            ':listing_id' => $listing_id,
            ':reason' => $reason,
            ':description' => $description
        ]);
        
        Security::logSecurityEvent('listing_reported', "Listing ID: $listing_id reported for: $reason", $_SESSION['user_id']);
        
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);
        
    } catch (Exception $e) {
        error_log("Error submitting report: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
} else {
    // If accessed via GET, show report page
    require_once 'includes/header.php';
    $listing_id = (int)($_GET['id'] ?? 0);
    ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-flag"></i> Report Listing</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/marketnearme/report.php" id="reportFormPage">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                            <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for reporting *</label>
                                <select class="form-select" id="reason" name="reason" required>
                                    <option value="">Select a reason</option>
                                    <option value="spam">Spam</option>
                                    <option value="inappropriate">Inappropriate Content</option>
                                    <option value="fraud">Fraud or Scam</option>
                                    <option value="misleading">Misleading Information</option>
                                    <option value="duplicate">Duplicate Listing</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Additional Details</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Please provide any additional information..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-flag"></i> Submit Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/footer.php';
}
?>