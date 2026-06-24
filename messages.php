<?php
// messages.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check authentication BEFORE any HTML output
Auth::requireLogin();

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $receiver_id = (int)($_POST['receiver_id'] ?? 0);
        $listing_id = (int)($_POST['listing_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        
        if (empty($message)) {
            $error = 'Message cannot be empty.';
        } elseif ($receiver_id <= 0) {
            $error = 'Invalid recipient.';
        } elseif ($receiver_id == $_SESSION['user_id']) {
            $error = 'You cannot message yourself.';
        } else {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO messages (sender_id, receiver_id, listing_id, message) 
                    VALUES (:sender_id, :receiver_id, :listing_id, :message)
                ");
                
                $stmt->execute([
                    ':sender_id' => $_SESSION['user_id'],
                    ':receiver_id' => $receiver_id,
                    ':listing_id' => $listing_id ?: null,
                    ':message' => Security::sanitize($message)
                ]);
                
                Security::logSecurityEvent('message_sent', "Message sent to user ID: $receiver_id", $_SESSION['user_id']);
                $success = 'Message sent successfully!';
                
                // Redirect to prevent form resubmission
                $redirect_url = "/marketnearme/messages.php?user_id=$receiver_id";
                if ($listing_id) {
                    $redirect_url .= "&listing_id=$listing_id";
                }
                header("Location: $redirect_url");
                exit();
                
            } catch (Exception $e) {
                error_log("Error sending message: " . $e->getMessage());
                $error = 'Failed to send message. Please try again.';
            }
        }
    }
}

// Mark message as read
if (isset($_GET['mark_read']) && (int)$_GET['mark_read'] > 0) {
    $message_id = (int)$_GET['mark_read'];
    $stmt = $conn->prepare("
        UPDATE messages SET is_read = 1 
        WHERE id = :id AND receiver_id = :user_id
    ");
    $stmt->execute([
        ':id' => $message_id,
        ':user_id' => $_SESSION['user_id']
    ]);
}

// Determine which conversation to show
$active_conversation = null;
$listing_info = null;

if (isset($_GET['listing_id']) && (int)$_GET['listing_id'] > 0) {
    $listing_id = (int)$_GET['listing_id'];
    
    // Get listing info
    $stmt = $conn->prepare("
        SELECT l.*, u.id as seller_id, u.username as seller_name 
        FROM listings l 
        JOIN users u ON l.user_id = u.id 
        WHERE l.id = :listing_id
    ");
    $stmt->execute([':listing_id' => $listing_id]);
    $listing_info = $stmt->fetch();
    
    if ($listing_info && $listing_info['seller_id'] != $_SESSION['user_id']) {
        $active_conversation = $listing_info['seller_id'];
    }
}

if (isset($_GET['user_id']) && (int)$_GET['user_id'] > 0) {
    $active_conversation = (int)$_GET['user_id'];
}

// FIXED: Get all conversations with proper parameter naming
$current_user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        other_user_id,
        u.username,
        u.full_name,
        u.avatar,
        last_message_time,
        last_message,
        unread_count
    FROM (
        SELECT 
            CASE 
                WHEN m.sender_id = :uid1 THEN m.receiver_id 
                ELSE m.sender_id 
            END as other_user_id,
            MAX(m.created_at) as last_message_time,
            SUBSTRING_INDEX(GROUP_CONCAT(m.message ORDER BY m.created_at DESC), ',', 1) as last_message,
            SUM(CASE WHEN m.receiver_id = :uid2 AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
        FROM messages m
        WHERE m.sender_id = :uid3 OR m.receiver_id = :uid4
        GROUP BY other_user_id
    ) as conv
    JOIN users u ON conv.other_user_id = u.id
    ORDER BY last_message_time DESC
");

$stmt->execute([
    ':uid1' => $current_user_id,
    ':uid2' => $current_user_id,
    ':uid3' => $current_user_id,
    ':uid4' => $current_user_id
]);
$conversations = $stmt->fetchAll();

// Get messages for active conversation
$messages = [];
$other_user = null;

if ($active_conversation) {
    // Get other user info
    $stmt = $conn->prepare("SELECT id, username, full_name, avatar FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $active_conversation]);
    $other_user = $stmt->fetch();
    
    if ($other_user) {
        // Get messages between current user and other user
        $stmt = $conn->prepare("
            SELECT m.*, 
                   u.username as sender_name,
                   u.full_name as sender_full_name,
                   l.title as listing_title,
                   l.id as listing_id_ref
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN listings l ON m.listing_id = l.id
            WHERE (m.sender_id = :user_id1 AND m.receiver_id = :other_user_id1)
               OR (m.sender_id = :other_user_id2 AND m.receiver_id = :user_id2)
            ORDER BY m.created_at ASC
        ");
        
        $stmt->execute([
            ':user_id1' => $current_user_id,
            ':other_user_id1' => $active_conversation,
            ':other_user_id2' => $active_conversation,
            ':user_id2' => $current_user_id
        ]);
        $messages = $stmt->fetchAll();
        
        // Mark messages as read
        $stmt = $conn->prepare("
            UPDATE messages SET is_read = 1 
            WHERE receiver_id = :user_id AND sender_id = :other_user_id AND is_read = 0
        ");
        $stmt->execute([
            ':user_id' => $current_user_id,
            ':other_user_id' => $active_conversation
        ]);
    }
}

// Now include header AFTER all processing
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Conversations List -->
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0" style="color: var(--dark-color);">
                        <i class="fas fa-comments me-2" style="color: var(--primary-color);"></i>Messages
                    </h5>
                    <?php
                    $total_unread = array_sum(array_column($conversations, 'unread_count'));
                    if ($total_unread > 0):
                    ?>
                    <span class="badge rounded-pill" style="background: var(--accent-gradient);">
                        <?php echo $total_unread; ?> new
                    </span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($conversations)): ?>
                    <div class="text-center py-5 px-3">
                        <i class="fas fa-inbox fa-3x mb-3" style="color: var(--gray-300);"></i>
                        <p class="text-muted mb-2">No messages yet</p>
                        <p class="small text-muted mb-3">Contact sellers to start conversations</p>
                        <a href="/marketnearme/search.php" class="btn btn-sm" style="background: var(--primary-gradient); color: white;">
                            <i class="fas fa-search"></i> Browse Listings
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conv): ?>
                        <a href="?user_id=<?php echo $conv['other_user_id']; ?>" 
                           class="list-group-item list-group-item-action border-0 py-3 <?php echo $active_conversation == $conv['other_user_id'] ? 'active' : ''; ?>"
                           style="<?php echo $active_conversation == $conv['other_user_id'] ? 'background: var(--primary-gradient); border-radius: 0;' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 position-relative">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 48px; height: 48px; background: <?php echo $active_conversation == $conv['other_user_id'] ? 'rgba(255,255,255,0.2)' : 'var(--gray-200)'; ?>; color: <?php echo $active_conversation == $conv['other_user_id'] ? 'white' : 'var(--primary-color)'; ?>; font-weight: 600;">
                                        <?php echo strtoupper(substr($conv['full_name'] ?? $conv['username'], 0, 1)); ?>
                                    </div>
                                    <?php if ($conv['unread_count'] > 0 && $active_conversation != $conv['other_user_id']): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                                        <?php echo $conv['unread_count']; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3" style="min-width: 0;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 <?php echo $active_conversation == $conv['other_user_id'] ? 'text-white' : ''; ?>" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($conv['full_name'] ?? $conv['username']); ?>
                                        </h6>
                                        <small class="<?php echo $active_conversation == $conv['other_user_id'] ? 'text-white-50' : 'text-muted'; ?>" style="font-size: 0.75rem;">
                                            <?php 
                                            $time = strtotime($conv['last_message_time']);
                                            $now = time();
                                            $diff = $now - $time;
                                            
                                            if ($diff < 60) {
                                                echo 'Just now';
                                            } elseif ($diff < 3600) {
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
                                    </div>
                                    <p class="mb-0 small <?php echo $active_conversation == $conv['other_user_id'] ? 'text-white-50' : 'text-muted'; ?> text-truncate" style="font-size: 0.8rem;">
                                        <?php echo htmlspecialchars(substr($conv['last_message'], 0, 40)); ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Messages Area -->
        <div class="col-md-8 col-lg-9">
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($active_conversation && $other_user): ?>
            <div class="card shadow-sm h-100">
                <!-- Chat Header -->
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                             style="width: 42px; height: 42px; background: var(--primary-gradient); color: white; font-weight: 600;">
                            <?php echo strtoupper(substr($other_user['full_name'] ?? $other_user['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($other_user['full_name'] ?? $other_user['username']); ?></h6>
                            <small class="text-muted">@<?php echo htmlspecialchars($other_user['username']); ?></small>
                        </div>
                    </div>
                    <?php if ($listing_info): ?>
                    <a href="/marketnearme/product.php?id=<?php echo $listing_info['id']; ?>" 
                       class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="fas fa-tag"></i> View Listing
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Messages Container -->
                <div class="card-body bg-light" style="height: 450px; overflow-y: auto;" id="messageContainer">
                    <?php if (empty($messages)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comment-dots fa-3x mb-3" style="color: var(--gray-300);"></i>
                        <p class="text-muted">No messages yet</p>
                        <p class="small text-muted">Send a message to start the conversation</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($messages as $msg): 
                        $is_sender = $msg['sender_id'] == $current_user_id;
                    ?>
                    <div class="d-flex mb-3 <?php echo $is_sender ? 'justify-content-end' : 'justify-content-start'; ?>">
                        <?php if (!$is_sender): ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-2 align-self-end flex-shrink-0" 
                             style="width: 32px; height: 32px; background: var(--gray-200); color: var(--gray-600); font-size: 0.8rem; font-weight: 600;">
                            <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="<?php echo $is_sender ? 'text-end' : ''; ?>" style="max-width: 75%;">
                            <?php if ($msg['listing_title']): ?>
                            <a href="/marketnearme/product.php?id=<?php echo $msg['listing_id_ref']; ?>" 
                               class="text-decoration-none">
                                <div class="px-3 py-1 mb-1 rounded-pill <?php echo $is_sender ? 'ms-auto' : ''; ?>" 
                                     style="background: rgba(13, 148, 136, 0.1); color: var(--primary-color); font-size: 0.75rem; width: fit-content;">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($msg['listing_title']); ?>
                                </div>
                            </a>
                            <?php endif; ?>
                            
                            <div class="px-3 py-2 rounded-3 <?php echo $is_sender ? 'text-white' : ''; ?> shadow-sm" 
                                 style="<?php echo $is_sender ? 'background: var(--primary-gradient);' : 'background: white;'; ?>">
                                <p class="mb-0" style="line-height: 1.5;"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                            </div>
                            
                            <small class="text-muted mt-1 d-block" style="font-size: 0.7rem;">
                                <?php 
                                $msg_time = strtotime($msg['created_at']);
                                if (date('Y-m-d') == date('Y-m-d', $msg_time)) {
                                    echo 'Today ' . date('g:i A', $msg_time);
                                } elseif (date('Y-m-d', strtotime('-1 day')) == date('Y-m-d', $msg_time)) {
                                    echo 'Yesterday ' . date('g:i A', $msg_time);
                                } else {
                                    echo date('M d, g:i A', $msg_time);
                                }
                                ?>
                                <?php if ($is_sender): ?>
                                    <?php if ($msg['is_read']): ?>
                                        <i class="fas fa-check-double" style="color: var(--primary-color);"></i>
                                    <?php else: ?>
                                        <i class="fas fa-check"></i>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <?php if ($is_sender): ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center ms-2 align-self-end flex-shrink-0" 
                             style="width: 32px; height: 32px; background: var(--primary-gradient); color: white; font-size: 0.8rem; font-weight: 600;">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Message Input -->
                <div class="card-footer bg-white py-3">
                    <form method="POST" action="" id="messageForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="receiver_id" value="<?php echo $other_user['id']; ?>">
                        <?php if ($listing_info): ?>
                        <input type="hidden" name="listing_id" value="<?php echo $listing_info['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <textarea class="form-control" name="message" rows="1" 
                                      placeholder="Type your message... Press Enter to send" 
                                      id="messageInput"
                                      style="resize: none; border-radius: 25px 0 0 25px; border: 2px solid var(--gray-200); padding: 0.6rem 1rem;"
                                      required></textarea>
                            <button class="btn px-4" type="submit" 
                                    style="background: var(--primary-gradient); color: white; border-radius: 0 25px 25px 0; border: none;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php elseif ($active_conversation && !$other_user): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-slash fa-3x mb-3" style="color: var(--gray-300);"></i>
                    <h5>User Not Found</h5>
                    <p class="text-muted">This user may have deleted their account or is no longer available.</p>
                    <a href="/marketnearme/messages.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Messages
                    </a>
                </div>
            </div>
            
            <?php else: ?>
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 500px; background: linear-gradient(135deg, #f8fafc 0%, #f0f9ff 100%);">
                    <div class="text-center px-4">
                        <div class="mb-4">
                            <div class="d-inline-block p-4 rounded-circle" style="background: rgba(13, 148, 136, 0.1);">
                                <i class="fas fa-comments fa-3x" style="color: var(--primary-color);"></i>
                            </div>
                        </div>
                        <h4 style="color: var(--dark-color);">Your Messages</h4>
                        <p class="text-muted mb-4">Select a conversation from the left or start a new one</p>
                        <div class="card bg-white shadow-sm">
                            <div class="card-body text-start">
                                <h6 class="mb-3"><i class="fas fa-lightbulb me-2" style="color: var(--warning-color);"></i>How to start messaging</h6>
                                <ul class="small text-muted mb-0" style="line-height: 2;">
                                    <li><i class="fas fa-search me-2" style="color: var(--primary-color);"></i>Browse listings that interest you</li>
                                    <li><i class="fas fa-comment me-2" style="color: var(--secondary-color);"></i>Click "Send Message" on any listing</li>
                                    <li><i class="fas fa-comments me-2" style="color: var(--accent-color);"></i>Your conversations will appear here</li>
                                    <li><i class="fas fa-handshake me-2" style="color: var(--success-color);"></i>Discuss details with sellers directly</li>
                                </ul>
                            </div>
                        </div>
                        <a href="/marketnearme/search.php" class="btn mt-4 px-4" style="background: var(--primary-gradient); color: white; border-radius: 50px;">
                            <i class="fas fa-search"></i> Find Listings
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
    
    // Focus on message input if there's an active conversation
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.focus();
    }
});

// Auto-resize textarea
const messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    
    // Submit on Enter (without Shift)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (this.value.trim()) {
                document.getElementById('messageForm').submit();
            }
        }
    });
}

// Poll for new messages every 15 seconds
<?php if ($active_conversation): ?>
let lastCheck = Date.now();
setInterval(function() {
    fetch('/marketnearme/check_messages.php?user_id=<?php echo $active_conversation; ?>&t=' + Date.now())
        .then(response => response.json())
        .then(data => {
            if (data.new_messages) {
                location.reload();
            }
        })
        .catch(error => console.error('Error checking messages:', error));
}, 15000);
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>