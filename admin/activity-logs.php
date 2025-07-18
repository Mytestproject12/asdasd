<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Activity Logs';

// Get activity logs with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Search and filter functionality
$search = $_GET['search'] ?? '';
$action_filter = $_GET['action'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(al.details LIKE ? OR u.username LIKE ? OR al.ip_address LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if ($action_filter) {
    $where_conditions[] = "al.action = ?";
    $params[] = $action_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_logs = $stmt->fetch()['total'];
$total_pages = ceil($total_logs / $per_page);

// Get activity logs
$sql = "
    SELECT al.*, u.username 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    $where_clause 
    ORDER BY al.created_at DESC 
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique actions for filter
$stmt = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Include admin layout
include '../includes/admin_layout.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Activity Logs (<?php echo number_format($total_logs); ?>)</h3>
        
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-color); opacity: 0.5;"></i>
                    <input type="text" name="search" placeholder="Search logs..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="padding: 0.5rem 1rem 0.5rem 2.5rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); color: var(--text-color); width: 250px;">
                </div>
                
                <select name="action" style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); color: var(--text-color);">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $action)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="activity-logs.php" class="btn btn-secondary">
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
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 3rem; color: #666;">
                            No activity logs found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <?php if ($log['username']): ?>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($log['username']); ?></div>
                                <?php else: ?>
                                    <span style="opacity: 0.5;">System</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; 
                                    <?php 
                                    echo match(true) {
                                        str_contains($log['action'], 'login') => 'background: #e3f2fd; color: #1976d2;',
                                        str_contains($log['action'], 'deposit') => 'background: #e8f5e8; color: #2e7d32;',
                                        str_contains($log['action'], 'withdrawal') => 'background: #fff3e0; color: #f57c00;',
                                        str_contains($log['action'], 'investment') => 'background: #f3e5f5; color: #7b1fa2;',
                                        str_contains($log['action'], 'error') || str_contains($log['action'], 'failed') => 'background: #ffebee; color: #d32f2f;',
                                        default => 'background: #f8f9fa; color: #6c757d;'
                                    };
                                    ?>">
                                    <?php echo str_replace('_', ' ', $log['action']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="max-width: 300px; word-wrap: break-word;">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </div>
                            </td>
                            <td>
                                <code style="font-size: 0.8rem;"><?php echo htmlspecialchars($log['ip_address']); ?></code>
                            </td>
                            <td>
                                <div style="font-size: 0.9rem;"><?php echo date('M j, Y', strtotime($log['created_at'])); ?></div>
                                <div style="font-size: 0.8rem; opacity: 0.7;"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></div>
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
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>"
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
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>"
                       style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.3s ease;">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo urlencode($action_filter); ?>"
                   style="padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; text-decoration: none; color: var(--text-color); transition: all 0.3s ease;">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/admin_layout_footer.php'; ?>