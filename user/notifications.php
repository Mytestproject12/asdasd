<?php
require_once '../includes/session_config.php';
require_once '../config.php';
require_once '../includes/NotificationSystem.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get system settings
$site_name = getSetting('site_name', 'Star Router Rent');

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

// Handle mark as read
if (isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    $notificationSystem->markAsRead($notification_id, $user_id);
    header('Location: notifications.php');
    exit;
}

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $notificationSystem->markAllAsRead($user_id);
    header('Location: notifications.php');
    exit;
}

// Get notifications
$notifications = $notificationSystem->getUserNotifications($user_id, 50);
$unread_count = $notificationSystem->getUnreadCount($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo htmlspecialchars($site_name); ?></title>
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
        }
        
        .notifications-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .notifications-header {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-item {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .notification-message {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0.75rem;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #999;
        }
        
        .notification-type {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .notification-type.info { background: #e3f2fd; color: #1976d2; }
        .notification-type.success { background: #e8f5e8; color: #2e7d32; }
        .notification-type.warning { background: #fff3e0; color: #f57c00; }
        .notification-type.error { background: #ffebee; color: #d32f2f; }
        
        .btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: #666;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .unread-badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
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
            
            .notifications-header {
                padding: 1rem 1.5rem;
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .notification-item {
                padding: 1rem 1.5rem;
            }
            
            .notification-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .notification-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
            
            .notifications-header {
                padding: 1rem;
            }
            
            .notification-item {
                padding: 1rem;
            }
            
            .notification-title {
                font-size: 1rem;
            }
            
            .notification-message {
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
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
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="investments.php">💰 Investments</a></li>
            <li><a href="devices.php">🖥️ Devices</a></li>
            <li><a href="transactions.php">💳 Transactions</a></li>
            <li><a href="referrals.php">👥 Referrals</a></li>
            <li><a href="notifications.php" class="active">🔔 Notifications</a></li>
            <li><a href="profile.php">👤 Profile</a></li>
            <li><a href="support.php">🎧 Support</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
            <h1>Notifications</h1>
            <?php if ($unread_count > 0): ?>
                <div class="unread-badge"><?php echo $unread_count; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="notifications-container">
            <div class="notifications-header">
                <h3>Your Notifications</h3>
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-small">Mark All as Read</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <h3>No Notifications</h3>
                    <p>You don't have any notifications yet. We'll notify you about important updates!</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-header">
                            <div>
                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div class="notification-type <?php echo $notification['type']; ?>">
                                    <?php echo ucfirst($notification['type']); ?>
                                </div>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-small">Mark as Read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <div class="notification-message">
                            <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                        </div>
                        
                        <div class="notification-meta">
                            <span><?php echo date('M j, Y H:i', strtotime($notification['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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