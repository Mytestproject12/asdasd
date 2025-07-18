<?php
require_once '../includes/session_config.php';
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get system settings
$site_name = getSetting('site_name', 'Star Router Rent');

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_investments,
        SUM(CASE WHEN status = 'active' THEN investment_amount ELSE 0 END) as total_invested,
        SUM(total_earned) as total_earned
    FROM investments 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$investment_stats = $stmt->fetch();

// Get recent transactions
$stmt = $pdo->prepare("
    SELECT * FROM payments 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll();

// Get active investments
$stmt = $pdo->prepare("
    SELECT * FROM investments 
    WHERE user_id = ? AND status = 'active' 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute([$user_id]);
$active_investments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($site_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .sidebar-header {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 1rem 2rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            border-right: 4px solid white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        
        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.balance { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .stat-icon.invested { background: linear-gradient(135deg, #51cf66, #40c057); color: white; }
        .stat-icon.earned { background: linear-gradient(135deg, #ffd43b, #fab005); color: white; }
        .stat-icon.investments { background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .card-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-info h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .transaction-info p {
            font-size: 0.8rem;
            color: #666;
        }
        
        .transaction-amount {
            font-weight: 600;
        }
        
        .transaction-amount.positive { color: #28a745; }
        .transaction-amount.negative { color: #dc3545; }
        
        .investment-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .investment-item:last-child {
            margin-bottom: 0;
        }
        
        .investment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .investment-name {
            font-weight: 600;
            color: #333;
        }
        
        .investment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #28a745;
            color: white;
        }
        
        .investment-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            font-size: 0.9rem;
        }
        
        .investment-details div {
            text-align: center;
        }
        
        .investment-details .label {
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .investment-details .value {
            font-weight: 600;
            color: #333;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .mobile-menu-btn {
            display: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1002;
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
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .mobile-overlay.active {
            display: block;
            opacity: 1;
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
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header {
                position: relative;
                padding: 1rem 1.5rem;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .user-info {
                display: none;
            }
            
            .header h1 {
                font-size: 1.5rem;
                text-align: center;
                margin-left: 2rem;
            }
            
            .quick-actions {
                flex-direction: column;
                gap: 0.75rem;
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
            
            .stats-grid {
                gap: 0.75rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            
            .stat-value {
                font-size: 1.25rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .btn {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-overlay" id="mobileOverlay"></div>
    
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>💫 <?php echo htmlspecialchars($site_name); ?></h2>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
            <li><a href="investments.php">💰 Investments</a></li>
            <li><a href="devices.php">🖥️ Devices</a></li>
            <li><a href="transactions.php">💳 Transactions</a></li>
            <li><a href="referrals.php">👥 Referrals</a></li>
            <li><a href="notifications.php">🔔 Notifications</a></li>
            <li><a href="profile.php">👤 Profile</a></li>
            <li><a href="support.php">🎧 Support</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
            <h1><?php echo htmlspecialchars(getSetting('dashboard_welcome_message', 'Welcome back, ' . $user['username'] . '!')); ?></h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div style="font-size: 0.9rem; color: #666;">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
        </div>
        
        <?php if (getSetting('dashboard_show_announcement', 'false') === 'true' && getSetting('dashboard_announcement')): ?>
            <div style="padding: 1rem; border-radius: 10px; margin-bottom: 2rem; font-weight: 500; 
                <?php 
                echo match(getSetting('dashboard_announcement_type', 'info')) {
                    'success' => 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;',
                    'warning' => 'background: #fff3cd; color: #856404; border: 1px solid #ffeaa7;',
                    'error' => 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;',
                    default => 'background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;'
                };
                ?>">
                <i class="fas fa-<?php 
                echo match(getSetting('dashboard_announcement_type', 'info')) {
                    'success' => 'check-circle',
                    'warning' => 'exclamation-triangle',
                    'error' => 'times-circle',
                    default => 'info-circle'
                };
                ?>"></i>
                <?php echo nl2br(htmlspecialchars(getSetting('dashboard_announcement'))); ?>
            </div>
        <?php endif; ?>
        
        <?php if (getSetting('dashboard_show_stats', 'true') === 'true'): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon balance">💰</div>
                <div class="stat-value">$<?php echo number_format($user['balance'], 2); ?></div>
                <div class="stat-label">Available Balance</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon invested">📈</div>
                <div class="stat-value">$<?php echo number_format($investment_stats['total_invested'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Invested</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon earned">🎯</div>
                <div class="stat-value">$<?php echo number_format($user['total_earnings'], 2); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon investments">📊</div>
                <div class="stat-value"><?php echo $investment_stats['active_investments'] ?? 0; ?></div>
                <div class="stat-label">Active Investments</div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="content-grid">
            <?php if (getSetting('dashboard_show_recent_transactions', 'true') === 'true'): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Recent Transactions</h3>
                    <a href="transactions.php" class="btn btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_transactions)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">No transactions yet</p>
                    <?php else: ?>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <h4><?php echo ucfirst($transaction['type']); ?></h4>
                                    <p><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></p>
                                </div>
                                <div class="transaction-amount <?php echo $transaction['type'] === 'deposit' ? 'positive' : 'negative'; ?>">
                                    <?php echo $transaction['type'] === 'deposit' ? '+' : '-'; ?>$<?php echo number_format($transaction['amount'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (getSetting('dashboard_show_active_investments', 'true') === 'true'): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Active Investments</h3>
                    <a href="investments.php" class="btn btn-primary">Invest Now</a>
                </div>
                <div class="card-body">
                    <?php if (empty($active_investments)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">No active investments</p>
                        <div style="text-align: center;">
                            <a href="investments.php" class="btn btn-primary">Start Investing</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($active_investments as $investment): ?>
                            <div class="investment-item">
                                <div class="investment-header">
                                    <div class="investment-name"><?php echo htmlspecialchars($investment['plan_name']); ?></div>
                                    <div class="investment-status"><?php echo ucfirst($investment['status']); ?></div>
                                </div>
                                <div class="investment-details">
                                    <div>
                                        <div class="label">Invested</div>
                                        <div class="value">$<?php echo number_format($investment['investment_amount'], 2); ?></div>
                                    </div>
                                    <div>
                                        <div class="label">Daily Rate</div>
                                        <div class="value"><?php echo $investment['daily_rate']; ?>%</div>
                                    </div>
                                    <div>
                                        <div class="label">Earned</div>
                                        <div class="value">$<?php echo number_format($investment['total_earned'], 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="quick-actions">
            <a href="deposit.php" class="btn btn-primary">💳 Make Deposit</a>
            <a href="withdraw.php" class="btn btn-primary">💰 Withdraw Funds</a>
            <a href="investments.php" class="btn btn-primary">📈 New Investment</a>
        </div>
    </div>
    
    <script>
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.querySelector('.sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                mobileOverlay.classList.toggle('active');
            });
            
            mobileOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                mobileOverlay.classList.remove('active');
            });
            
            // Close menu when clicking on a link
            const sidebarLinks = sidebar.querySelectorAll('a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    mobileOverlay.classList.remove('active');
                });
            });
        }
    </script>
</body>
</html>