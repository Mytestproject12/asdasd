<?php
/**
 * Enhanced Payment Manager
 * Handles all payment operations including Plisio integration
 */

class PaymentManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Process webhook from payment gateways
     */
    public function processWebhook($gateway, $data) {
        try {
            switch ($gateway) {
                case 'plisio':
                    return $this->processPlisioWebhook($data);
                default:
                    throw new Exception('Unknown gateway: ' . $gateway);
            }
        } catch (Exception $e) {
            error_log("Webhook processing error ({$gateway}): " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process Plisio webhook
     */
    private function processPlisioWebhook($data) {
        // Validate required fields
        $required_fields = ['txn_id', 'status', 'order_number'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        $txn_id = $data['txn_id'];
        $status = $data['status'];
        $order_number = $data['order_number'];
        $amount = floatval($data['source_amount'] ?? $data['amount'] ?? 0);
        
        // Find payment record
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE id = ? OR transaction_id = ?");
        $stmt->execute([$order_number, $txn_id]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            // Try to find by transaction_id only
            $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE transaction_id = ?");
            $stmt->execute([$txn_id]);
            $payment = $stmt->fetch();
            
            if (!$payment) {
                throw new Exception("Payment not found for order: {$order_number} or txn_id: {$txn_id}");
            }
        }
        
        // Update payment status based on Plisio status
        $new_status = $this->mapPlisioStatus($status);
        
        if ($payment['status'] !== $new_status) {
            $this->pdo->beginTransaction();
            
            try {
                // Update payment record
                $stmt = $this->pdo->prepare("
                    UPDATE payments 
                    SET status = ?, transaction_id = ?, gateway_data = ? 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $new_status,
                    $txn_id,
                    json_encode($data),
                    $payment['id']
                ]);
                
                // If payment is completed, credit user balance
                if ($new_status === 'completed' && $payment['type'] === 'deposit') {
                    $credit_amount = $amount > 0 ? $amount : $payment['amount'];
                    $this->creditUserBalance($payment['user_id'], $credit_amount);
                    
                    // Process referral commissions for first 2 deposits
                    try {
                        require_once __DIR__ . '/ReferralManager.php';
                        $referralManager = new ReferralManager($this->pdo);
                        $referralManager->processReferralCommission($payment['user_id'], $credit_amount);
                    } catch (Exception $e) {
                        error_log('Failed to process referral commission: ' . $e->getMessage());
                    }
                    
                    // Send notification and email
                    try {
                        $this->sendPaymentNotification($payment['user_id'], 'deposit_completed', $credit_amount, $payment['crypto_currency'] ?? 'USD', $payment['transaction_id'] ?? '');
                    } catch (Exception $e) {
                        error_log('Failed to send payment notification: ' . $e->getMessage());
                    }
                }
                
                $this->pdo->commit();
                
                return [
                    'success' => true,
                    'payment_id' => $payment['id'],
                    'status' => $new_status
                ];
                
            } catch (Exception $e) {
                $this->pdo->rollback();
                throw $e;
            }
        }
        
        return [
            'success' => true,
            'payment_id' => $payment['id'],
            'status' => $payment['status'],
            'message' => 'No status change required'
        ];
    }
    
    /**
     * Map Plisio status to internal status
     */
    private function mapPlisioStatus($plisio_status) {
        $status_map = [
            'new' => 'pending',
            'pending' => 'pending',
            'expired' => 'failed',
            'completed' => 'completed',
            'error' => 'failed',
            'cancelled' => 'failed'
        ];
        
        return $status_map[$plisio_status] ?? 'pending';
    }
    
    /**
     * Credit user balance
     */
    private function creditUserBalance($user_id, $amount) {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET balance = balance + ?, total_invested = total_invested + ? 
            WHERE id = ?
        ");
        $stmt->execute([$amount, $amount, $user_id]);
        
        // Log activity
        try {
            logActivity($user_id, 'deposit_completed', "Deposit of $" . number_format($amount, 2) . " completed");
        } catch (Exception $e) {
            error_log('Failed to log deposit activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Send payment notification
     */
    private function sendPaymentNotification($user_id, $type, $amount, $currency = 'USD', $transaction_id = '') {
        try {
            require_once __DIR__ . '/NotificationSystem.php';
            $notifications = new NotificationSystem($this->pdo);
            
            if ($type === 'deposit_completed') {
                // Send deposit confirmation email
                $notifications->sendDepositConfirmationEmail($user_id, $amount, $currency, $transaction_id);
            }
        } catch (Exception $e) {
            error_log("Failed to send payment notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get currency manager instance
     */
    public function getCurrencyManager() {
        require_once __DIR__ . '/CurrencyManager.php';
        return new CurrencyManager($this->pdo);
    }
    
    /**
     * Create Plisio payment
     */
    public function createPlisioPayment($amount, $currency, $order_id, $callback_url) {
        $api_key = getSetting('plisio_api_key');
        if (!$api_key) {
            throw new Exception('Plisio API key not configured');
        }
        
        $params = [
            'source_currency' => 'USD',
            'source_amount' => $amount,
            'order_number' => $order_id,
            'currency' => $currency,
            'callback_url' => $callback_url,
            'api_key' => $api_key
        ];
        
        $response = $this->makeRequest('https://plisio.net/api/v1/invoices/new', $params);
        
        if ($response && $response['status'] === 'success') {
            return $response['data'];
        }
        
        throw new Exception('Failed to create Plisio payment: ' . ($response['data']['message'] ?? 'Unknown error'));
    }
    
    /**
     * Test all payment gateways
     */
    public function testAllGateways() {
        $results = [];
        
        // Test Plisio
        try {
            $api_key = getSetting('plisio_api_key');
            if ($api_key) {
                $response = $this->makeRequest('https://plisio.net/api/v1/currencies', ['api_key' => $api_key]);
                $results['plisio'] = [
                    'success' => $response && $response['status'] === 'success',
                    'message' => $response ? 'Connected successfully' : 'Connection failed'
                ];
            } else {
                $results['plisio'] = [
                    'success' => false,
                    'message' => 'API key not configured'
                ];
            }
        } catch (Exception $e) {
            $results['plisio'] = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    /**
     * Make HTTP request
     */
    private function makeRequest($url, $params = []) {
        $ch = curl_init();
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Star Router Rent/1.0');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
}

// Helper functions for backward compatibility
function createPlisioPayment($amount, $currency, $order_id, $callback_url) {
    global $pdo;
    $paymentManager = new PaymentManager($pdo);
    return $paymentManager->createPlisioPayment($amount, $currency, $order_id, $callback_url);
}
?>