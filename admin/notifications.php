<?php
require_once '../includes/session_config.php';
require_once '../config.php';
require_once '../includes/NotificationSystem.php';

$page_title = 'Notifications Management';
$success = '';
$error = '';

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_notification':
                if (isset($_POST['user_id']) && isset($_POST['title']) && isset($_POST['message'])) {
                    $user_id = $_POST['user_id'];
                    $title = $_POST['title'];
                    $message = $_POST['message'];
                    $type = $_POST['type'] ?? 'info';
                    $send_email = isset($_POST['send_email']);
                    
                    if ($user_id === 'all') {
                        // Send to all users
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE status = 'active'");
                        $stmt->execute();
                        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $sent_count = 0;
                        foreach ($users as $uid) {
                            if ($notificationSystem->createNotification($uid, $title, $message, $type, $send_email)) {
                                $sent_count++;
                            }
                        }
                        
                        $success = "Notification sent to {$sent_count} users.";
                    } else {
                        // Send to specific user
                        if ($notificationSystem->createNotification($user_id, $title, $message, $type, $send_email)) {
                            $success = 'Notification sent successfully.';
                        } else {
                            $error = 'Failed to send notification.';
                        }
                    }
                }
                break;
                
            case 'delete_notification':
                if (isset($_POST['notification_id'])) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
                        if ($stmt->execute([$_POST['notification_id']])) {
                            $success = 'Notification deleted successfully.';
                        } else {
                            $error = 'Failed to delete notification.';
                        }
                    } catch (Exception $e) {
                        $error = 'Failed to delete notification: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get notifications with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
$total_notifications = $stmt->fetch()['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get notifications
$stmt = $pdo->prepare("
    SELECT n.*, u.username 
    FROM notifications n 
    JOIN users u ON n.user_id = u.id 
    ORDER BY n.created_at DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute();
$notifications = $stmt->fetchAll();

// Get users for dropdown
$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE status = 'active' ORDER BY username");
$stmt->execute();
$users = $stmt->fetchAll();

// Include admin layout
include '../includes/admin_layout.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Send Notification -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-paper-plane"></i>
            Send Notification
        </h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="send_notification">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="user_id">Send To</label>
                    <select name="user_id" id="user_id" required>
                        <option value="all">All Active Users</option>
                        <optgroup label="Individual Users">
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="type">Notification Type</label>
                    <select name="type" id="type" required>
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" required placeholder="Notification title">
            </div>
            
            <div class="form-group">
                <label for="message">Message</label>
                <textarea name="message" id="message" required placeholder="Notification message" style="min-height: 100px;"></textarea>
            </div>
            
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                <input type="checkbox" name="send_email" id="send_email">
                <label for="send_email">Also send as email</label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i>
                Send Notification
            </button>
        </form>
    </div>
</div>

<!-- All Notifications -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Notifications (<?php echo number_format($total_notifications); ?>)</h3>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: #666;">
                            No notifications found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($notification['username']); ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 600; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div style="font-size: 0.8rem; opacity: 0.7; max-width: 200px; word-wrap: break-word;">
                                    <?php echo htmlspecialchars(substr($notification['message'], 0, 100)); ?>...
                                </div>
                            </td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; 
                                    <?php 
                                    echo match($notification['type']) {
                                        'info' => 'background: #e3f2fd; color: #1976d2;',
                                        'success' => 'background: #e8f5e8; color: #2e7d32;',
                                        'warning' => 'background: #fff3e0; color: #f57c00;',
                                        'error' => 'background: #ffebee; color: #d32f2f;',
                                        default => 'background: #f8f9fa; color: #6c757d;'
                                    };
                                    ?>">
                                    <?php echo ucfirst($notification['type']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; 
                                    <?php echo $notification['is_read'] ? 'background: #d4edda; color: #155724;' : 'background: #fff3cd; color: #856404;'; ?>">
                                    <?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($notification['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_notification">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirm('Delete this notification?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; padding: 2rem;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>"
                   style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.3s ease;">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--primary-color); color: white;">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"
                       style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.3s ease;">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>"
                   style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.3s ease;">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/admin_layout_footer.php'; ?>