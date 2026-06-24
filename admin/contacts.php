<?php
// admin/contacts.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Handle message status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'read') {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read', is_read = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } elseif ($action === 'archive') {
        $stmt = $conn->prepare("UPDATE contact_messages SET status = 'archived' WHERE id = :id");
        $stmt->execute([':id' => $id]);
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
    
    header("Location: /marketnearme/admin/contacts.php");
    exit();
}

// Get contact messages
$stmt = $conn->query("
    SELECT cm.*, u.username 
    FROM contact_messages cm 
    LEFT JOIN users u ON cm.user_id = u.id 
    ORDER BY 
        CASE cm.status 
            WHEN 'new' THEN 0 
            WHEN 'read' THEN 1 
            WHEN 'replied' THEN 2 
            ELSE 3 
        END,
        cm.created_at DESC
");
$messages = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/listings.php"><i class="fas fa-list"></i> Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/marketnearme/admin/users.php"><i class="fas fa-users"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/marketnearme/admin/contacts.php"><i class="fas fa-envelope"></i> Contact Messages</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <h1 class="h2 pt-3 pb-2 mb-3 border-bottom">Contact Messages</h1>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr class="<?php echo $msg['status'] === 'new' ? 'table-warning' : ''; ?>">
                            <td>#<?php echo $msg['id']; ?></td>
                            <td><?php echo htmlspecialchars($msg['name']); ?></td>
                            <td><?php echo htmlspecialchars($msg['email']); ?></td>
                            <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                            <td><?php echo htmlspecialchars(substr($msg['message'], 0, 50)); ?>...</td>
                            <td><?php echo $msg['username'] ? htmlspecialchars($msg['username']) : 'Guest'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $msg['status'] === 'new' ? 'warning' : ($msg['status'] === 'read' ? 'info' : 'secondary'); ?>">
                                    <?php echo ucfirst($msg['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="?action=read&id=<?php echo $msg['id']; ?>" class="btn btn-outline-info">Read</a>
                                    <a href="?action=archive&id=<?php echo $msg['id']; ?>" class="btn btn-outline-secondary">Archive</a>
                                    <a href="?action=delete&id=<?php echo $msg['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this message?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>