<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Referrals Management';
$success = '';
$error = '';

// Handle referral settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $settings = [
            'referral_level_1_rate' => $_POST['referral_level_1_rate'],
            'referral_level_2_rate' => $_POST['referral_level_2_rate'],
            'referral_level_3_rate' => $_POST['referral_level_3_rate'],
            'referral_max_deposits' => $_POST['referral_max_deposits']
        ];
        
        $pdo->beginTransaction();
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) 
                VALUES (?, ?, 'number', 'referrals', ?, 1) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            $stmt->execute([$key, $value, ucfirst(str_replace('_', ' ', $key))]);
        }
        
        $pdo->commit();
        $success = 'Referral settings updated successfully!';
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Get referral statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_referrals,
        SUM(total_commission_earned) as total_commissions,
        COUNT(DISTINCT referrer_id) as active_referrers
    FROM referrals
");
$referral_stats = $stmt->fetch();

// Get top referrers
$stmt = $pdo->prepare("
    SELECT u.username, u.email, 
           COUNT(r.id) as total_referrals,
           SUM(r.total_commission_earned) as total_earned
    FROM users u
    JOIN referrals r ON u.id = r.referrer_id
    GROUP BY u.id
    ORDER BY total_earned DESC
    LIMIT 10
");
$stmt->execute();
$top_referrers = $stmt->fetchAll();

// Get recent referrals
$stmt = $pdo->prepare("
    SELECT r.*, 
           u1.username as referrer_username,
           u2.username as referred_username,
           u2.email as referred_email
    FROM referrals r
    JOIN users u1 ON r.referrer_id = u1.id
    JOIN users u2 ON r.referred_id = u2.id
    ORDER BY r.created_at DESC
    LIMIT 20
");
$stmt->execute();
$recent_referrals = $stmt->fetchAll();

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

<!-- Referral Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div style="background: var(--bg-color); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); text-align: center;">
        <div style="width: 60px; height: 60px; border-radius: 15px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
            <i class="fas fa-users"></i>
        </div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.5rem;">
            <?php echo number_format($referral_stats['total_referrals'] ?? 0); ?>
        </div>
        <div style="color: var(--text-color); opacity: 0.7; font-size: 0.9rem;">Total Referrals</div>
    </div>
    
    <div style="background: var(--bg-color); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); text-align: center;">
        <div style="width: 60px; height: 60px; border-radius: 15px; background: linear-gradient(135deg, #51cf66, #40c057); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.5rem;">
            $<?php echo number_format($referral_stats['total_commissions'] ?? 0, 2); ?>
        </div>
        <div style="color: var(--text-color); opacity: 0.7; font-size: 0.9rem;">Total Commissions</div>
    </div>
    
    <div style="background: var(--bg-color); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); text-align: center;">
        <div style="width: 60px; height: 60px; border-radius: 15px; background: linear-gradient(135deg, #ffd43b, #fab005); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 1rem;">
            <i class="fas fa-crown"></i>
        </div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.5rem;">
            <?php echo number_format($referral_stats['active_referrers'] ?? 0); ?>
        </div>
        <div style="color: var(--text-color); opacity: 0.7; font-size: 0.9rem;">Active Referrers</div>
    </div>
</div>

<!-- Referral Settings -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-cog"></i>
            Referral Settings
        </h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
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
                    <div style="font-size: 0.85rem; color: var(--text-color); opacity: 0.7; margin-top: 0.25rem;">
                        Only first X deposits earn commission
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" name="update_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Settings
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Top Referrers and Recent Referrals -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Top Referrers -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Top Referrers</h3>
        </div>
        <div class="card-body">
            <?php if (empty($top_referrers)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">No referrers yet</p>
            <?php else: ?>
                <?php foreach ($top_referrers as $index => $referrer): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.8rem;">
                                #<?php echo $index + 1; ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($referrer['username']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-color); opacity: 0.7;"><?php echo $referrer['total_referrals']; ?> referrals</div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 600; color: var(--success-color);">$<?php echo number_format($referrer['total_earned'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Referrals -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Referrals</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_referrals)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">No recent referrals</p>
            <?php else: ?>
                <?php foreach ($recent_referrals as $referral): ?>
                    <div style="padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; color: var(--text-color); margin-bottom: 0.25rem;">
                                    <?php echo htmlspecialchars($referral['referrer_username']); ?> → <?php echo htmlspecialchars($referral['referred_username']); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-color); opacity: 0.7;">
                                    Level <?php echo $referral['level']; ?> • <?php echo $referral['commission_rate']; ?>% commission
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.8rem; color: var(--text-color); opacity: 0.7;">
                                    <?php echo date('M j, Y', strtotime($referral['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/admin_layout_footer.php'; ?>