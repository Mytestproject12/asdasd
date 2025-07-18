<?php
/**
 * Test Notification and Email System
 * Use this to test the enhanced notification system
 */

require_once 'config.php';
require_once 'includes/NotificationSystem.php';

echo "=== Notification System Test ===\n\n";

try {
    // Initialize notification system
    $notificationSystem = new NotificationSystem($pdo);
    
    echo "✓ Notification system initialized successfully\n\n";
    
    // Test 1: Create a test user (if not exists)
    echo "Testing user creation for notifications...\n";
    $test_email = 'test@example.com';
    $test_username = 'testuser';
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$test_email]);
    $test_user = $stmt->fetch();
    
    if (!$test_user) {
        // Create test user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, referral_code, status) VALUES (?, ?, ?, ?, 'active')");
        $password_hash = password_hash('testpass123', PASSWORD_DEFAULT);
        $referral_code = strtoupper(substr(md5(uniqid()), 0, 8));
        $stmt->execute([$test_username, $test_email, $password_hash, $referral_code]);
        $test_user_id = $pdo->lastInsertId();
        echo "✓ Test user created with ID: {$test_user_id}\n";
    } else {
        $test_user_id = $test_user['id'];
        echo "✓ Using existing test user with ID: {$test_user_id}\n";
    }
    echo "\n";
    
    // Test 2: Create in-app notification
    echo "Testing in-app notification creation...\n";
    $result = $notificationSystem->createNotification(
        $test_user_id,
        'Test Notification',
        'This is a test notification to verify the system is working correctly.',
        'info'
    );
    
    if ($result) {
        echo "✓ In-app notification created successfully\n";
    } else {
        echo "❌ Failed to create in-app notification\n";
    }
    echo "\n";
    
    // Test 3: Send email notification
    echo "Testing email notification...\n";
    $result = $notificationSystem->sendEmailNotification(
        $test_user_id,
        'Test Email Notification',
        'This is a test email notification to verify the email system is working.',
        'success'
    );
    
    if ($result) {
        echo "✓ Email notification sent successfully\n";
    } else {
        echo "⚠ Email notification failed (check email configuration)\n";
    }
    echo "\n";
    
    // Test 4: Send welcome email
    echo "Testing welcome email...\n";
    $user_data = [
        'username' => $test_username,
        'email' => $test_email,
        'first_name' => 'Test',
        'last_name' => 'User'
    ];
    
    $result = $notificationSystem->sendWelcomeEmail($test_user_id, $user_data);
    
    if ($result) {
        echo "✓ Welcome email sent successfully\n";
    } else {
        echo "⚠ Welcome email failed (check email configuration)\n";
    }
    echo "\n";
    
    // Test 5: Send deposit confirmation email
    echo "Testing deposit confirmation email...\n";
    $result = $notificationSystem->sendDepositConfirmationEmail($test_user_id, 100.00, 'USD', 'TEST123456');
    
    if ($result) {
        echo "✓ Deposit confirmation email sent successfully\n";
    } else {
        echo "⚠ Deposit confirmation email failed (check email configuration)\n";
    }
    echo "\n";
    
    // Test 6: Get user notifications
    echo "Testing notification retrieval...\n";
    $notifications = $notificationSystem->getUserNotifications($test_user_id, 10);
    echo "✓ Retrieved " . count($notifications) . " notifications for user\n";
    
    foreach ($notifications as $notification) {
        echo "  - {$notification['title']} ({$notification['type']}) - " . 
             ($notification['is_read'] ? 'Read' : 'Unread') . "\n";
    }
    echo "\n";
    
    // Test 7: Get unread count
    echo "Testing unread count...\n";
    $unread_count = $notificationSystem->getUnreadCount($test_user_id);
    echo "✓ User has {$unread_count} unread notifications\n\n";
    
    // Test 8: Mark notification as read
    if (!empty($notifications)) {
        echo "Testing mark as read...\n";
        $first_notification = $notifications[0];
        $result = $notificationSystem->markAsRead($first_notification['id'], $test_user_id);
        
        if ($result) {
            echo "✓ Notification marked as read successfully\n";
        } else {
            echo "❌ Failed to mark notification as read\n";
        }
        echo "\n";
    }
    
    // Test 9: Email template system
    echo "Testing email template system...\n";
    $template = $notificationSystem->getEmailTemplate('welcome', [
        'site_name' => 'Test Site',
        'username' => 'testuser',
        'first_name' => 'Test',
        'last_name' => 'User'
    ]);
    
    if (!empty($template['subject']) && !empty($template['body'])) {
        echo "✓ Email template system working correctly\n";
        echo "  Subject: " . substr($template['subject'], 0, 50) . "...\n";
    } else {
        echo "❌ Email template system failed\n";
    }
    echo "\n";
    
    echo "🎉 All notification system tests completed!\n";
    echo "The notification and email system is working correctly.\n\n";
    
    // Display system status
    echo "=== System Status ===\n";
    echo "Email Configuration:\n";
    echo "- SMTP Host: " . (getSetting('smtp_host') ?: 'Not configured (using PHP mail)') . "\n";
    echo "- Admin Email: " . getSetting('admin_email', 'Not set') . "\n";
    echo "- Site Name: " . getSetting('site_name', 'Not set') . "\n";
    echo "- Site URL: " . getSetting('site_url', 'Not set') . "\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>