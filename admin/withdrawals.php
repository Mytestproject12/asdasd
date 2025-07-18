<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Withdrawals Management';
$success = '';
$error = '';

// Handle withdrawal actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['withdrawal_id'])) {
        $withdrawal_id = $_POST['withdrawal_id'];
        
        switch ($_POST['action']) {
            case 'approve':
                try {
                    $pdo->beginTransaction();
                    
                    // Get withdrawal details
                    $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE id = ?");
                    $stmt->execute([$withdrawal_id]);
                    $withdrawal = $stmt->fetch();
                    
                    if ($withdrawal && $withdrawal['status'] === 'pending') {
                        // Update withdrawal status
                        $stmt = $pdo->prepare("UPDATE withdrawal_requests SET status = 'approved', processed_at = NOW() WHERE id = ?");
                        $stmt->execute([$withdrawal_id]);
                        
                        // Log activity
                        logActivity($_SESSION['admin_id'], 'withdrawal_approved', "Approved withdrawal: $" . number_format($withdrawal['amount'], 2) . " for user ID: " . $withdrawal['user_id']);
                        
                        $pdo->commit();
                        $success = 'Withdrawal approved successfully.';
                    } else {
                        $pdo->rollback();
                        $error = 'Invalid withdrawal or already processed.';
                    }
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollback();
                    }
                    $error = 'Failed to approve withdrawal: ' . $e->getMessage();
                }
                break;
                
            case 'reject':
                try {
                    $pdo->beginTransaction();
                    
                    // Get withdrawal details
                    $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE id = ?");
                    $stmt->execute([$withdrawal_id]);
                    $withdrawal = $stmt->fetch();
                    
                    if ($withdrawal && $withdrawal['status'] === 'pending') {
                        // Update withdrawal status
                        $stmt = $pdo->prepare("UPDATE withdrawal_requests SET status = 'rejected', processed_at = NOW(), admin_notes = ? WHERE id = ?");
                        $stmt->execute([$_POST['admin_notes'] ?? 'Rejected by admin', $withdrawal_id]);
                        
                        // Refund user balance
                        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                        $stmt->execute([$withdrawal['amount'], $withdrawal['user_id']]);
                        
                        // Log activity
                        logActivity($_SESSION['admin_id'], 'withdrawal_rejected', "Rejected withdrawal: $" . number_format($withdrawal['amount'], 2) . " for user ID: " . $withdrawal['user_id']);
                        
                        $pdo->commit();
                        $success = 'Withdrawal rejected and balance refunded.';
                    } else {
                        $pdo->rollback();
                        $error = 'Invalid withdrawal or already processed.';
                    }
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollback();
                    }
                    $error = 'Failed to reject withdrawal: ' . $e->getMessage();
                }
                break;
                
            case 'complete':
                try {
                    $stmt = $pdo->prepare("UPDATE withdrawal_requests SET status = 'completed', completed_at = NOW() WHERE id = ?");
                    if ($stmt->execute([$withdrawal_id])) {
                        $success = 'Withdrawal marked as completed.';
                    } else {
                        $error = 'Failed to complete withdrawal.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to complete withdrawal: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get withdrawals with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter functionality
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR wr.withdrawal_address LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if ($status_filter) {
    $where_conditions[] = "wr.status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM withdrawal_requests wr JOIN users u ON wr.user_id = u.id $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_withdrawals = $stmt->fetch()['total'];
$total_pages = ceil($total_withdrawals / $per_page);

// Get withdrawals
$sql = "
    SELECT wr.*, u.username, u.email 
    FROM withdrawal_requests wr 
    JOIN users u ON wr.user_id = u.id 
    $where_clause 
    ORDER BY wr.requested_at DESC 
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$withdrawals = $stmt->fetchAll();

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

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Withdrawals (<?php echo number_format($total_withdrawals); ?>)</h3>
        
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-color); opacity: 0.5;"></i>
                    <input type="text" name="search" placeholder="Search withdrawals..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); color: var(--text-color); width: 250px;">
                </div>
                
                <select name="status" style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); color: var(--text-color);">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="withdrawals.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </form>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($withdrawals)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: #666;">
                            No withdrawals found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($withdrawals as $withdrawal): ?>
                        <tr>
                            <td>
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($withdrawal['username']); ?></div>
                                    <div style="font-size: 0.8rem; opacity: 0.7;"><?php echo htmlspecialchars($withdrawal['email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">$<?php echo number_format($withdrawal['amount'], 2); ?></div>
                                <div style="font-size: 0.8rem; opacity: 0.7;">
                                    Fee: $<?php echo number_format($withdrawal['fee_amount'], 2); ?><br>
                                    Net: $<?php echo number_format($withdrawal['net_amount'], 2); ?>
                                </div>
                            </td>
                            <td><?php echo ucfirst($withdrawal['withdrawal_method']); ?></td>
                            <td>
                                <code style="font-size: 0.8rem; word-break: break-all;">
                                    <?php echo htmlspecialchars(substr($withdrawal['withdrawal_address'], 0, 20)); ?>...
                                </code>
                            </td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; 
                                    <?php 
                                    echo match($withdrawal['status']) {
                                        'pending' => 'background: #fff3cd; color: #856404;',
                                        'approved' => 'background: #d1ecf1; color: #0c5460;',
                                        'completed' => 'background: #d4edda; color: #155724;',
                                        'rejected' => 'background: #f8d7da; color: #721c24;',
                                        default => 'background: #f8f9fa; color: #6c757d;'
                                    };
                                    ?>">
                                    <?php echo ucfirst($withdrawal['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($withdrawal['requested_at'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <?php if ($withdrawal['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                            <button type="submit" class="btn btn-success" 
                                                    onclick="return confirm('Approve this withdrawal?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                            <button type="submit" class="btn btn-danger" 
                                                    onclick="return confirm('Reject this withdrawal? Balance will be refunded.')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($withdrawal['status'] === 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                            <button type="submit" class="btn btn-primary" 
                                                    onclick="return confirm('Mark as completed?')">
                                                <i class="fas fa-check-double"></i> Complete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="opacity: 0.5; font-size: 0.8rem;">No actions</span>
                                    <?php endif; ?>
                                </div>
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
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
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
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
                       style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.3s ease;">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>"
                   style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.3s ease;">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/admin_layout_footer.php'; ?>