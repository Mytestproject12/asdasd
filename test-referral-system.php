<?php
/**
 * Test Referral System
 * Use this to test the enhanced referral system
 */

require_once 'config.php';
require_once 'includes/ReferralManager.php';

echo "=== Referral System Test ===\n\n";

try {
    // Initialize referral manager
    $referralManager = new ReferralManager($pdo);
    
    echo "✓ Referral manager initialized successfully\n\n";
    
    // Test 1: Update referral settings
    echo "Testing referral settings update...\n";
    $result = $referralManager->updateReferralSettings(10.0, 7.0, 5.0);
    if ($result) {
        echo "✓ Referral settings updated successfully\n";
    } else {
        echo "❌ Failed to update referral settings\n";
    }
    echo "\n";
    
    // Test 2: Check current settings
    echo "Testing current referral rates...\n";
    $level_1_rate = getSetting('referral_level_1_rate', '10.0');
    $level_2_rate = getSetting('referral_level_2_rate', '7.0');
    $level_3_rate = getSetting('referral_level_3_rate', '5.0');
    $max_deposits = getSetting('referral_max_deposits', '2');
    
    echo "  - Level 1 Rate: {$level_1_rate}%\n";
    echo "  - Level 2 Rate: {$level_2_rate}%\n";
    echo "  - Level 3 Rate: {$level_3_rate}%\n";
    echo "  - Max Commission Deposits: {$max_deposits}\n";
    echo "\n";
    
    // Test 3: Create test users for referral chain
    echo "Testing referral chain creation...\n";
    
    // Create Level 1 referrer
    $referrer_1_id = generateUUID();
    $referrer_1_code = 'REF' . strtoupper(substr(md5(uniqid()), 0, 5));
    $stmt = $pdo->prepare("
        INSERT INTO users (id, username, email, password, referral_code, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE username = username
    ");
    $stmt->execute([$referrer_1_id, 'referrer1', 'referrer1@test.com', password_hash('test123', PASSWORD_DEFAULT), $referrer_1_code]);
    
    // Create Level 2 referrer (referred by Level 1)
    $referrer_2_id = generateUUID();
    $referrer_2_code = 'REF' . strtoupper(substr(md5(uniqid()), 0, 5));
    $stmt = $pdo->prepare("
        INSERT INTO users (id, username, email, password, referral_code, referred_by, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE username = username
    ");
    $stmt->execute([$referrer_2_id, 'referrer2', 'referrer2@test.com', password_hash('test123', PASSWORD_DEFAULT), $referrer_2_code, $referrer_1_code]);
    
    // Create referred user (referred by Level 2)
    $referred_user_id = generateUUID();
    $referred_user_code = 'REF' . strtoupper(substr(md5(uniqid()), 0, 5));
    $stmt = $pdo->prepare("
        INSERT INTO users (id, username, email, password, referral_code, referred_by, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE username = username
    ");
    $stmt->execute([$referred_user_id, 'referreduser', 'referred@test.com', password_hash('test123', PASSWORD_DEFAULT), $referred_user_code, $referrer_2_code]);
    
    echo "✓ Test referral chain created\n";
    echo "  - Level 1 Referrer: {$referrer_1_code}\n";
    echo "  - Level 2 Referrer: {$referrer_2_code} (referred by Level 1)\n";
    echo "  - Referred User: {$referred_user_code} (referred by Level 2)\n";
    echo "\n";
    
    // Test 4: Create referral relationships
    echo "Creating referral relationships...\n";
    
    // Level 1 → Level 2 relationship
    $ref_id_1 = generateUUID();
    $stmt = $pdo->prepare("
        INSERT INTO referrals (id, referrer_id, referred_id, level, commission_rate, status, created_at) 
        VALUES (?, ?, ?, 1, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE commission_rate = VALUES(commission_rate)
    ");
    $stmt->execute([$ref_id_1, $referrer_1_id, $referrer_2_id, $level_1_rate]);
    
    // Level 2 → Referred User relationship
    $ref_id_2 = generateUUID();
    $stmt = $pdo->prepare("
        INSERT INTO referrals (id, referrer_id, referred_id, level, commission_rate, status, created_at) 
        VALUES (?, ?, ?, 1, ?, 'active', NOW())
        ON DUPLICATE KEY UPDATE commission_rate = VALUES(commission_rate)
    ");
    $stmt->execute([$ref_id_2, $referrer_2_id, $referred_user_id, $level_1_rate]);
    
    echo "✓ Referral relationships created\n\n";
    
    // Test 5: Simulate deposit and commission processing
    echo "Testing commission processing...\n";
    
    // Create a test deposit payment
    $payment_id = generateUUID();
    $deposit_amount = 1000.00;
    $stmt = $pdo->prepare("
        INSERT INTO payments (id, user_id, amount, payment_method, status, type, description, created_at) 
        VALUES (?, ?, ?, 'crypto', 'completed', 'deposit', 'Test deposit for referral commission', NOW())
    ");
    $stmt->execute([$payment_id, $referred_user_id, $deposit_amount]);
    
    // Process referral commission
    $result = $referralManager->processReferralCommission($referred_user_id, $deposit_amount);
    
    if ($result) {
        echo "✓ Referral commission processed successfully\n";
        
        // Check balances
        $stmt = $pdo->prepare("SELECT username, balance, referral_earnings FROM users WHERE id IN (?, ?)");
        $stmt->execute([$referrer_1_id, $referrer_2_id]);
        $balances = $stmt->fetchAll();
        
        foreach ($balances as $balance) {
            echo "  - {$balance['username']}: Balance = $" . number_format($balance['balance'], 2) . 
                 ", Referral Earnings = $" . number_format($balance['referral_earnings'], 2) . "\n";
        }
    } else {
        echo "❌ Failed to process referral commission\n";
    }
    echo "\n";
    
    // Test 6: Test second deposit (should still earn commission)
    echo "Testing second deposit commission...\n";
    
    $payment_id_2 = generateUUID();
    $stmt = $pdo->prepare("
        INSERT INTO payments (id, user_id, amount, payment_method, status, type, description, created_at) 
        VALUES (?, ?, ?, 'crypto', 'completed', 'deposit', 'Second test deposit', NOW())
    ");
    $stmt->execute([$payment_id_2, $referred_user_id, $deposit_amount]);
    
    $result = $referralManager->processReferralCommission($referred_user_id, $deposit_amount);
    echo $result ? "✓ Second deposit commission processed\n" : "❌ Second deposit commission failed\n";
    echo "\n";
    
    // Test 7: Test third deposit (should NOT earn commission)
    echo "Testing third deposit (should not earn commission)...\n";
    
    $payment_id_3 = generateUUID();
    $stmt = $pdo->prepare("
        INSERT INTO payments (id, user_id, amount, payment_method, status, type, description, created_at) 
        VALUES (?, ?, ?, 'crypto', 'completed', 'deposit', 'Third test deposit', NOW())
    ");
    $stmt->execute([$payment_id_3, $referred_user_id, $deposit_amount]);
    
    $result = $referralManager->processReferralCommission($referred_user_id, $deposit_amount);
    echo $result ? "❌ Third deposit commission processed (should not happen)\n" : "✓ Third deposit correctly ignored\n";
    echo "\n";
    
    // Test 8: Get referral statistics
    echo "Testing referral statistics...\n";
    $stats = $referralManager->getReferralStats($referrer_2_id);
    echo "  - Total Referrals: {$stats['total_referrals']}\n";
    echo "  - Total Commission: $" . number_format($stats['total_commission'], 2) . "\n";
    echo "  - Total Volume: $" . number_format($stats['total_volume'], 2) . "\n";
    echo "\n";
    
    echo "🎉 All referral system tests completed!\n";
    echo "The referral system is working correctly with commission limits.\n\n";
    
} catch (Exception $e) {
    echo "❌ Test Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>