<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'System Settings';
$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = [
            'site_name' => $_POST['site_name'],
            'site_url' => $_POST['site_url'],
            'site_description' => $_POST['site_description'],
            'admin_email' => $_POST['admin_email'],
            'min_deposit' => $_POST['min_deposit'],
            'max_deposit' => $_POST['max_deposit'],
            'min_withdrawal' => $_POST['min_withdrawal'],
            'withdrawal_fee' => $_POST['withdrawal_fee'],
            'referral_level_1_rate' => $_POST['referral_level_1_rate'],
            'referral_level_2_rate' => $_POST['referral_level_2_rate'],
            'referral_level_3_rate' => $_POST['referral_level_3_rate'],
            'referral_max_deposits' => $_POST['referral_max_deposits'],
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 'true' : 'false',
            'registration_enabled' => isset($_POST['registration_enabled']) ? 'true' : 'false',
            'email_notifications' => isset($_POST['email_notifications']) ? 'true' : 'false',
            'smtp_host' => $_POST['smtp_host'],
            'smtp_port' => $_POST['smtp_port'],
            'smtp_username' => $_POST['smtp_username'],
            'smtp_password' => $_POST['smtp_password']
        ];
        
        $pdo->beginTransaction();
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) 
                VALUES (?, ?, 'string', 'general', ?, 1) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, $value, ucfirst(str_replace('_', ' ', $key))]);
        }
        
        $pdo->commit();
        $success = 'Settings updated successfully!';
        
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
                    <div style="display: grid; gap: 2rem;">
                        <!-- General Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-globe"></i>
                                    General Settings
                                </h3>
                            </div>
                            <div class="card-body">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                    <div class="form-group">
                                        <label for="site_name">Site Name</label>
                                        <input type="text" id="site_name" name="site_name" 
                                               value="<?php echo htmlspecialchars(getSetting('site_name')); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="site_url">Site URL</label>
                                        <input type="url" id="site_url" name="site_url" 
                                               value="<?php echo htmlspecialchars(getSetting('site_url')); ?>" required>
                                        <div class="help-text">Full URL with https://</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="admin_email">Admin Email</label>
                                        <input type="email" id="admin_email" name="admin_email" 
                                               value="<?php echo htmlspecialchars(getSetting('admin_email')); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="site_description">Site Description</label>
                                    <textarea id="site_description" name="site_description"><?php echo htmlspecialchars(getSetting('site_description')); ?></textarea>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                               <?php echo getSetting('maintenance_mode') === 'true' ? 'checked' : ''; ?>>
                                        <label for="maintenance_mode">Maintenance Mode</label>
                                    </div>
                                    
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="registration_enabled" name="registration_enabled" 
                                               <?php echo getSetting('registration_enabled', 'true') === 'true' ? 'checked' : ''; ?>>
                                        <label for="registration_enabled">Allow Registration</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Financial Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-dollar-sign"></i>
                                    Financial Settings
                                </h3>
                            </div>
                            <div class="card-body">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                    <div class="form-group">
                                        <label for="min_deposit">Minimum Deposit ($)</label>
                                        <input type="number" id="min_deposit" name="min_deposit" step="0.01" 
                                               value="<?php echo getSetting('min_deposit', '100'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="max_deposit">Maximum Deposit ($)</label>
                                        <input type="number" id="max_deposit" name="max_deposit" step="0.01" 
                                               value="<?php echo getSetting('max_deposit', '50000'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="min_withdrawal">Minimum Withdrawal ($)</label>
                                        <input type="number" id="min_withdrawal" name="min_withdrawal" step="0.01" 
                                               value="<?php echo getSetting('min_withdrawal', '20'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="withdrawal_fee">Withdrawal Fee (%)</label>
                                        <input type="number" id="withdrawal_fee" name="withdrawal_fee" step="0.1" 
                                               value="<?php echo getSetting('withdrawal_fee', '2.5'); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Referral Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-share-alt"></i>
                                    Referral Settings
                                </h3>
                            </div>
                            <div class="card-body">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                    <div class="form-group">
                                        <label for="referral_level_1_rate">Level 1 Commission (%)</label>
                                        <input type="number" id="referral_level_1_rate" name="referral_level_1_rate" step="0.1" 
                                               value="<?php echo getSetting('referral_level_1_rate', '10.0'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="referral_level_2_rate">Level 2 Commission (%)</label>
                                        <input type="number" id="referral_level_2_rate" name="referral_level_2_rate" step="0.1" 
                                               value="<?php echo getSetting('referral_level_2_rate', '7.0'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="referral_level_3_rate">Level 3 Commission (%)</label>
                                        <input type="number" id="referral_level_3_rate" name="referral_level_3_rate" step="0.1" 
                                               value="<?php echo getSetting('referral_level_3_rate', '5.0'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="referral_max_deposits">Max Commission Deposits</label>
                                        <input type="number" id="referral_max_deposits" name="referral_max_deposits" 
                                               value="<?php echo getSetting('referral_max_deposits', '2'); ?>" required>
                                        <div class="help-text">Only first X deposits earn commission</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-envelope"></i>
                                    Email Settings
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="checkbox-group" style="margin-bottom: 1.5rem;">
                                    <input type="checkbox" id="email_notifications" name="email_notifications" 
                                           <?php echo getSetting('email_notifications', 'true') === 'true' ? 'checked' : ''; ?>>
                                    <label for="email_notifications">Enable Email Notifications</label>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                    <div class="form-group">
                                        <label for="smtp_host">SMTP Host</label>
                                        <input type="text" id="smtp_host" name="smtp_host" 
                                               value="<?php echo htmlspecialchars(getSetting('smtp_host')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_port">SMTP Port</label>
                                        <input type="number" id="smtp_port" name="smtp_port" 
                                               value="<?php echo getSetting('smtp_port', '587'); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_username">SMTP Username</label>
                                        <input type="text" id="smtp_username" name="smtp_username" 
                                               value="<?php echo htmlspecialchars(getSetting('smtp_username')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="smtp_password">SMTP Password</label>
                                        <input type="password" id="smtp_password" name="smtp_password" 
                                               value="<?php echo htmlspecialchars(getSetting('smtp_password')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Settings
                        </button>
                    </div>
                </form>

<?php include '../includes/admin_layout_footer.php'; ?>
    
    <script>
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = newTheme === 'dark' ? '☀️' : '🌓';
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = savedTheme === 'dark' ? '☀️' : '🌓';
        });
        
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        });
        
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            mobileOverlay.classList.remove('active');
        });
    </script>
</body>
</html>