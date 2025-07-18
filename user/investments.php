<?php
require_once '../includes/session_config.php';
require_once '../config.php';

$page_title = 'Investment Plans';
$user_id = $_SESSION['user_id'];

// Get user data first
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle new investment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invest']) && isset($_POST['plan_id']) && isset($_POST['amount'])) {
    $plan_id = $_POST['plan_id'];
    $amount = floatval($_POST['amount']);
    
    // Get plan details
    $stmt = $pdo->prepare("SELECT * FROM investment_plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();
    
    if ($plan && $amount >= $plan['min_amount'] && $amount <= $plan['max_amount'] && $amount <= $user['balance']) {
        try {
            $pdo->beginTransaction();
            
            // Deduct from user balance
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ?, total_invested = total_invested + ? WHERE id = ?");
            $stmt->execute([$amount, $amount, $user_id]);
            
            // Create investment
            $investment_id = generateUUID();
            $expected_daily_profit = ($amount * $plan['daily_rate']) / 100;
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));
            
            $stmt = $pdo->prepare("
                INSERT INTO investments (id, user_id, plan_id, plan_name, plan_duration, investment_amount, daily_rate, expected_daily_profit, status, start_date, end_date, actual_start_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, NOW())
            ");
            $stmt->execute([$investment_id, $user_id, $plan_id, $plan['name'], $plan['duration_days'], $amount, $plan['daily_rate'], $expected_daily_profit, $start_date, $end_date, $start_date]);
            
            // Record transaction
            $payment_id = generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO payments (id, user_id, amount, payment_method, status, type, description, created_at) 
                VALUES (?, ?, ?, 'balance', 'completed', 'investment', ?, NOW())
            ");
            $stmt->execute([$payment_id, $user_id, $amount, "Investment in {$plan['name']}"]);
            
            // Log investment activity
            logActivity($user_id, 'investment_created', "New investment: {$plan['name']} - $" . number_format($amount, 2));
            
            // Send investment notification
            try {
                require_once '../includes/NotificationSystem.php';
                $notifications = new NotificationSystem($pdo);
                $notifications->createNotification(
                    $user_id,
                    'Investment Created',
                    "Your investment of $" . number_format($amount, 2) . " in {$plan['name']} has been created successfully!",
                    'success',
                    true // Send email
                );
            } catch (Exception $e) {
                error_log('Failed to send investment notification: ' . $e->getMessage());
            }
            
            $pdo->commit();
            $success = "Investment created successfully!";
            
            // Refresh user data after successful investment
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }
            $error = "Investment failed. Please try again.";
            error_log("Investment creation error: " . $e->getMessage());
        }
    } else {
        if (!$plan) {
            $error = "Invalid investment plan selected.";
        } elseif ($amount < $plan['min_amount']) {
            $error = "Minimum investment amount is $" . number_format($plan['min_amount'], 2);
        } elseif ($amount > $plan['max_amount']) {
            $error = "Maximum investment amount is $" . number_format($plan['max_amount'], 2);
        } elseif ($amount > $user['balance']) {
            $error = "Insufficient balance. Available: $" . number_format($user['balance'], 2);
        } else {
            $error = "Invalid investment parameters.";
        }
    }
}

// Get investment plans
$stmt = $pdo->prepare("SELECT * FROM investment_plans WHERE is_active = 1 ORDER BY sort_order");
$stmt->execute();
$plans = $stmt->fetchAll();

// Get user investments
$stmt = $pdo->prepare("SELECT * FROM investments WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$investments = $stmt->fetchAll();

// Include user layout
include '../includes/user_layout.php';
?>

<style>
        
        .balance-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .plan-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .plan-card.featured {
            border: 2px solid #667eea;
            transform: scale(1.02);
        }
        
        .plan-card.featured::before {
            content: 'POPULAR';
            position: absolute;
            top: -10px;
            right: 20px;
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .plan-rate {
            font-size: 2.5rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .plan-period {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        .plan-range {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .investment-form {
            margin-top: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .investments-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status.active { background: #d4edda; color: #155724; }
        .status.completed { background: #d1ecf1; color: #0c5460; }
        .status.pending { background: #fff3cd; color: #856404; }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                transform: translateX(-250px);
                transition: transform 0.3s ease;
                z-index: 1001;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .mobile-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1000;
                display: none;
            }
            
            .mobile-overlay.active {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header {
                position: relative;
                padding: 1rem 1.5rem;
            }
            
            .mobile-menu-btn {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
                padding: 0.5rem;
                border-radius: 8px;
                cursor: pointer;
                font-size: 1.2rem;
                z-index: 1002;
            }
            
            .header h1 {
                font-size: 1.5rem;
                text-align: center;
                margin-left: 2rem;
            }
            
            .balance-info {
                font-size: 0.9rem;
                padding: 0.75rem 1.5rem;
            }
            
            .plans-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .plan-card {
                padding: 1.5rem;
            }
            
            .plan-name {
                font-size: 1.25rem;
            }
            
            .plan-rate {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 0.5rem;
            }
            
            .header {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .header h1 {
                font-size: 1.25rem;
                margin-left: 1.5rem;
            }
            
            .balance-info {
                font-size: 0.85rem;
                padding: 0.5rem 1rem;
            }
            
            .plans-grid {
                gap: 1rem;
            }
            
            .plan-card {
                padding: 1.25rem;
            }
            
            .plan-rate {
                font-size: 1.75rem;
            }
            
            .form-group input {
                padding: 0.875rem;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .btn {
                padding: 0.875rem;
                font-size: 0.9rem;
            }
            
            .table {
                font-size: 0.85rem;
            }
            
            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>

<div class="balance-info">
    Available Balance: $<?php echo number_format($user['balance'], 2); ?>
</div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="plans-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card <?php echo $plan['is_featured'] ? 'featured' : ''; ?>">
                    <div class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></div>
                    <div class="plan-rate"><?php echo $plan['daily_rate']; ?>%</div>
                    <div class="plan-period">Daily for <?php echo $plan['duration_days']; ?> days</div>
                    
                    <div class="plan-range">
                        $<?php echo number_format($plan['min_amount']); ?> - $<?php echo number_format($plan['max_amount']); ?>
                    </div>
                    
                    <?php if ($plan['description']): ?>
                        <p style="color: #666; margin-bottom: 1rem;"><?php echo htmlspecialchars($plan['description']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($plan['features']): ?>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php 
                                $features = json_decode($plan['features'], true);
                                if ($features && is_array($features)) {
                                    foreach ($features as $feature) {
                                        echo '<li style="padding: 0.25rem 0; color: #666;"><span style="color: #28a745; margin-right: 0.5rem;">✓</span>' . htmlspecialchars($feature) . '</li>';
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="investment-form">
                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                        <div class="form-group">
                            <input type="number" name="amount" placeholder="Enter amount" 
                                   min="<?php echo $plan['min_amount']; ?>" 
                                   max="<?php echo min($plan['max_amount'], $user['balance']); ?>" 
                                   step="0.01" required>
                        </div>
                        <button type="submit" name="invest" class="btn" <?php echo $user['balance'] < $plan['min_amount'] ? 'disabled' : ''; ?>>
                            <?php echo $user['balance'] < $plan['min_amount'] ? 'Insufficient Balance' : 'Invest Now'; ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="investments-table">
            <div class="table-header">
                <h3>My Investments</h3>
            </div>
            <?php if (empty($investments)): ?>
                <div style="padding: 3rem; text-align: center; color: #666;">
                    <p>No investments yet. Start investing to earn daily profits!</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Daily Rate</th>
                            <th>Total Earned</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($investments as $investment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($investment['plan_name']); ?></td>
                                <td>$<?php echo number_format($investment['investment_amount'], 2); ?></td>
                                <td><?php echo $investment['daily_rate']; ?>%</td>
                                <td>$<?php echo number_format($investment['total_earned'], 2); ?></td>
                                <td><span class="status <?php echo $investment['status']; ?>"><?php echo ucfirst($investment['status']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($investment['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($investment['end_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

<?php include '../includes/user_layout_footer.php'; ?>