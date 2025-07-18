<?php
/**
 * Enhanced Referral Management System
 * Handles multi-level referrals and automatic commission processing
 */

class ReferralManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Process referral commission after deposit
     */
    public function processReferralCommission($user_id, $deposit_amount) {
        try {
            // Get user's referrer chain
            // Get user's referrer
            $stmt = $this->pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || !$user['referred_by']) {
                return false; // No referrer
            }
            
            // Get level 1 referrer details
            $stmt = $this->pdo->prepare("SELECT id, username FROM users WHERE referral_code = ? AND status = 'active'");
            $stmt->execute([$user['referred_by']]);
            $level_1_referrer = $stmt->fetch();
            
            if (!$level_1_referrer) {
                return false; // Referrer not found or inactive
            }
            
            // Check if this is one of the first 2 deposits
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as deposit_count 
                FROM payments 
                WHERE user_id = ? AND type = 'deposit' AND status = 'completed'
            ");
            $stmt->execute([$user_id]);
            $deposit_count = $stmt->fetch()['deposit_count'];
            
            $max_deposits = intval(getSetting('referral_max_deposits', '2'));
            if ($deposit_count > $max_deposits) {
                return false; // Only first 2 deposits earn commission
            }
            
            // Get commission rates
            $level_1_rate = floatval(getSetting('referral_level_1_rate', '10.0'));
            $level_2_rate = floatval(getSetting('referral_level_2_rate', '7.0'));
            $level_3_rate = floatval(getSetting('referral_level_3_rate', '5.0'));
            
            $this->pdo->beginTransaction();
            
            // Level 1 Commission (Direct referrer)
            $commission_amount = ($deposit_amount * $level_1_rate) / 100;
            $this->payCommission($level_1_referrer['id'], $user_id, $commission_amount, 1, $deposit_amount);
            
            // Level 2 Commission (Referrer's referrer)
            $level_2_referrer = $this->getReferrer($level_1_referrer['id']);
            if ($level_2_referrer && $level_2_rate > 0) {
                $commission_amount = ($deposit_amount * $level_2_rate) / 100;
                $this->payCommission($level_2_referrer['id'], $user_id, $commission_amount, 2, $deposit_amount);
            }
            
            // Level 3 Commission (Level 2's referrer)
            if ($level_2_referrer) {
                $level_3_referrer = $this->getReferrer($level_2_referrer['id']);
                if ($level_3_referrer && $level_3_rate > 0) {
                    $commission_amount = ($deposit_amount * $level_3_rate) / 100;
                    $this->payCommission($level_3_referrer['id'], $user_id, $commission_amount, 3, $deposit_amount);
                }
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log('Referral commission processing failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get referrer of a user
     */
    private function getReferrer($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.referral_code, u.username
            FROM users u 
            WHERE u.referral_code = (
                SELECT referred_by FROM users WHERE id = ?
            ) AND u.status = 'active'
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    /**
     * Pay commission to referrer
     */
    private function payCommission($referrer_id, $referred_user_id, $commission_amount, $level, $deposit_amount) {
        // Update referrer's balance and earnings
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET balance = balance + ?, 
                referral_earnings = referral_earnings + ?, 
                total_earnings = total_earnings + ? 
            WHERE id = ?
        ");
        $stmt->execute([$commission_amount, $commission_amount, $commission_amount, $referrer_id]);
        
        // Update or create referral record
        $stmt = $this->pdo->prepare("
            INSERT INTO referrals (id, referrer_id, referred_id, level, commission_rate, total_commission_earned, total_referral_volume, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ON DUPLICATE KEY UPDATE 
                total_commission_earned = total_commission_earned + VALUES(total_commission_earned),
                total_referral_volume = total_referral_volume + VALUES(total_referral_volume)
        ");
        $referral_record_id = generateUUID();
        $commission_rate = floatval(getSetting("referral_level_{$level}_rate", '10.0'));
        $stmt->execute([$referral_record_id, $referrer_id, $referred_user_id, $level, $commission_rate, $commission_amount, $deposit_amount]);
        
        // Create payment record for commission
        $payment_id = generateUUID();
        $stmt = $this->pdo->prepare("
            INSERT INTO payments (id, user_id, amount, payment_method, status, type, description, created_at) 
            VALUES (?, ?, ?, 'system', 'completed', 'referral_bonus', ?, NOW())
        ");
        $stmt->execute([
            $payment_id,
            $referrer_id,
            $commission_amount,
            "Level {$level} referral commission from deposit"
        ]);
        
        // Send notification
        try {
            require_once __DIR__ . '/NotificationSystem.php';
            $notifications = new NotificationSystem($this->pdo);
            $notifications->createNotification(
                $referrer_id,
                'Referral Commission Earned',
                "You earned $" . number_format($commission_amount, 2) . " commission from a level {$level} referral deposit!",
                'success',
                true
            );
        } catch (Exception $e) {
            error_log('Failed to send referral notification: ' . $e->getMessage());
        }
        
        // Log activity
        logActivity($referrer_id, 'referral_commission', "Earned $" . number_format($commission_amount, 2) . " level {$level} commission");
    }
    
    /**
     * Get referral statistics for a user
     */
    public function getReferralStats($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_referrals,
                COALESCE(SUM(total_commission_earned), 0) as total_commission,
                COALESCE(SUM(total_referral_volume), 0) as total_volume
            FROM referrals 
            WHERE referrer_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
    
    /**
     * Get referred users for a referrer
     */
    public function getReferredUsers($referrer_id, $limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT u.username, u.email, u.created_at, 
                   COALESCE(u.total_invested, 0) as total_invested, 
                   COALESCE(r.total_commission_earned, 0) as total_commission_earned,
                   r.level
            FROM referrals r
            JOIN users u ON r.referred_id = u.id
            WHERE r.referrer_id = ?
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$referrer_id, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update referral settings
     */
    public function updateReferralSettings($level_1_rate, $level_2_rate, $level_3_rate) {
        try {
            $this->pdo->beginTransaction();
            
            // Update settings
            $settings = [
                'referral_level_1_rate' => $level_1_rate,
                'referral_level_2_rate' => $level_2_rate,
                'referral_level_3_rate' => $level_3_rate
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) 
                    VALUES (?, ?, 'number', 'referrals', ?, 1)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, $value, ucfirst(str_replace('_', ' ', $key))]);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log('Failed to update referral settings: ' . $e->getMessage());
            return false;
        }
    }
}
?>