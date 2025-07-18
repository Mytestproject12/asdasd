<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Investments Management';
$success = '';
$error = '';

// Handle investment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['investment_id'])) {
        $investment_id = $_POST['investment_id'];
        
        switch ($_POST['action']) {
            case 'activate':
                try {
                    $stmt = $pdo->prepare("UPDATE investments SET status = 'active', actual_start_date = CURDATE() WHERE id = ?");
                    if ($stmt->execute([$investment_id])) {
                        $success = 'Investment activated successfully.';
                    } else {
                        $error = 'Failed to activate investment.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to activate investment: ' . $e->getMessage();
                }
                break;
                
            case 'suspend':
                try {
                    $stmt = $pdo->prepare("UPDATE investments SET status = 'suspended' WHERE id = ?");
                    if ($stmt->execute([$investment_id])) {
                        $success = 'Investment suspended successfully.';
                    } else {
                        $error = 'Failed to suspend investment.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to suspend investment: ' . $e->getMessage();
                }
                break;
                
            case 'complete':
                try {
                    $stmt = $pdo->prepare("UPDATE investments SET status = 'completed', maturity_date = CURDATE() WHERE id = ?");
                    if ($stmt->execute([$investment_id])) {
                        $success = 'Investment completed successfully.';
                    } else {
                        $error = 'Failed to complete investment.';
                    }
                } catch (Exception $e) {
                    $error = 'Failed to complete investment: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get investments with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter functionality
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR i.plan_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if ($status_filter) {
    $where_conditions[] = "i.status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM investments i JOIN users u ON i.user_id = u.id $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_investments = $stmt->fetch()['total'];
$total_pages = ceil($total_investments / $per_page);

// Get investments
$sql = "
    SELECT i.*, u.username, u.email 
    FROM investments i 
    JOIN users u ON i.user_id = u.id 
    $where_clause 
    ORDER BY i.created_at DESC 
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$investments = $stmt->fetchAll();

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
        <h3 class="card-title">All Investments (<?php echo number_format($total_investments); ?>)</h3>
        
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-color); opacity: 0.5;"></i>
                    <input type="text" name="search" placeholder="Search investments..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); color: var(--text-color); width: 250px;">
                </div>
                
                <select name="status" style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); color: var(--text-color);">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="investments.php" class="btn btn-secondary">
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
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Daily Rate</th>
                    <th>Total Earned</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($investments)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 3rem; color: #666;">
                            No investments found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($investments as $investment): ?>
                        <tr>
                            <td>
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($investment['username']); ?></div>
                                    <div style="font-size: 0.8rem; opacity: 0.7;"><?php echo htmlspecialchars($investment['email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($investment['plan_name']); ?></div>
                                <div style="font-size: 0.8rem; opacity: 0.7;"><?php echo $investment['plan_duration']; ?> days</div>
                            </td>
                            <td>
                                <div style="font-weight: 600;">$<?php echo number_format($investment['investment_amount'], 2); ?></div>
                            </td>
                            <td><?php echo $investment['daily_rate']; ?>%</td>
                            <td>$<?php echo number_format($investment['total_earned'], 2); ?></td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; 
                                    <?php 
                                    echo match($investment['status']) {
                                        'pending' => 'background: #fff3cd; color: #856404;',
                                        'active' => 'background: #d4edda; color: #155724;',
                                        'completed' => 'background: #d1ecf1; color: #0c5460;',
                                        'suspended' => 'background: #f8d7da; color: #721c24;',
                                        default => 'background: #f8f9fa; color: #6c757d;'
                                    };
                                    ?>">
                                    <?php echo ucfirst($investment['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($investment['start_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($investment['end_date'])); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <?php if ($investment['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="investment_id" value="<?php echo $investment['id']; ?>">
                                            <button type="submit" class="btn btn-success" 
                                                    onclick="return confirm('Activate this investment?')">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($investment['status'] === 'active'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="suspend">
                                            <input type="hidden" name="investment_id" value="<?php echo $investment['id']; ?>">
                                            <button type="submit" class="btn btn-warning" 
                                                    onclick="return confirm('Suspend this investment?')">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="investment_id" value="<?php echo $investment['id']; ?>">
                                            <button type="submit" class="btn btn-primary" 
                                                    onclick="return confirm('Complete this investment?')">
                                                <i class="fas fa-check"></i>
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