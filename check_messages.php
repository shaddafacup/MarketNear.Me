<?php
// check_messages.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!Auth::isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated', 'new_messages' => false]);
    exit();
}

$other_user_id = (int)($_GET['user_id'] ?? 0);

if ($other_user_id <= 0) {
    echo json_encode(['error' => 'Invalid user', 'new_messages' => false]);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$current_user_id = $_SESSION['user_id'];

// Check for new unread messages from the other user
$stmt = $conn->prepare("
    SELECT COUNT(*) as new_count 
    FROM messages 
    WHERE receiver_id = :receiver_id 
    AND sender_id = :sender_id 
    AND is_read = 0
");

$stmt->execute([
    ':receiver_id' => $current_user_id,
    ':sender_id' => $other_user_id
]);

$result = $stmt->fetch();

echo json_encode([
    'new_messages' => $result['new_count'] > 0,
    'count' => (int)$result['new_count']
]);
?>