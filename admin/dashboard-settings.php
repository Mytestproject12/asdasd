<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Dashboard Settings';
$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = [
            'dashboard_welcome_message' => $_POST['dashboard_welcome_message'],
            'dashboard_show_stats' => isset($_POST['dashboard_show_stats']) ? 'true' : 'false',
            'dashboard_show_recent_transactions' => isset($_POST['dashboard_show_recent_transactions']) ? 'true' : 'false',
            'dashboard_show_active_investments' => isset($_POST['dashboard_show_active_investments']) ? 'true' : 'false',
            'dashboard_announcement' => $_POST['dashboard_announcement'],
            'dashboard_announcement_type' => $_POST['dashboard_announcement_type'],
            'dashboard_show_announcement' => isset($_POST['dashboard_show_announcement']) ? 'true' : 'false'
        ];
        
        $pdo->beginTransaction();
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) 
                VALUES (?, ?, 'string', 'dashboard', ?, 1)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, $value, ucfirst(str_replace('_', ' ', $key))]);
        }
        
        $pdo->commit();
        $success = 'Dashboard settings updated successfully!';
        
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

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

<form method="POST">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard Customization
            </h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="dashboard_welcome_message">Welcome Message</label>
                <textarea id="dashboard_welcome_message" name="dashboard_welcome_message" style="min-height: 100px;"><?php echo htmlspecialchars(getSetting('dashboard_welcome_message', 'Welcome to your investment dashboard!')); ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="dashboard_show_stats" name="dashboard_show_stats" 
                               <?php echo getSetting('dashboard_show_stats', 'true') === 'true' ? 'checked' : ''; ?>>
                        <label for="dashboard_show_stats">Show Statistics Cards</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="dashboard_show_recent_transactions" name="dashboard_show_recent_transactions" 
                               <?php echo getSetting('dashboard_show_recent_transactions', 'true') === 'true' ? 'checked' : ''; ?>>
                        <label for="dashboard_show_recent_transactions">Show Recent Transactions</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="dashboard_show_active_investments" name="dashboard_show_active_investments" 
                               <?php echo getSetting('dashboard_show_active_investments', 'true') === 'true' ? 'checked' : ''; ?>>
                        <label for="dashboard_show_active_investments">Show Active Investments</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-bullhorn"></i>
                Dashboard Announcement
            </h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                    <input type="checkbox" id="dashboard_show_announcement" name="dashboard_show_announcement" 
                           <?php echo getSetting('dashboard_show_announcement', 'false') === 'true' ? 'checked' : ''; ?>>
                    <label for="dashboard_show_announcement">Show Announcement</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="dashboard_announcement_type">Announcement Type</label>
                <select id="dashboard_announcement_type" name="dashboard_announcement_type">
                    <option value="info" <?php echo getSetting('dashboard_announcement_type', 'info') === 'info' ? 'selected' : ''; ?>>Info</option>
                    <option value="success" <?php echo getSetting('dashboard_announcement_type', 'info') === 'success' ? 'selected' : ''; ?>>Success</option>
                    <option value="warning" <?php echo getSetting('dashboard_announcement_type', 'info') === 'warning' ? 'selected' : ''; ?>>Warning</option>
                    <option value="error" <?php echo getSetting('dashboard_announcement_type', 'info') === 'error' ? 'selected' : ''; ?>>Error</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="dashboard_announcement">Announcement Message</label>
                <textarea id="dashboard_announcement" name="dashboard_announcement" style="min-height: 100px;" placeholder="Enter announcement message..."><?php echo htmlspecialchars(getSetting('dashboard_announcement', '')); ?></textarea>
            </div>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Save Dashboard Settings
        </button>
    </div>
</form>

<?php include '../includes/admin_layout_footer.php'; ?>