<?php
/**
 * Enhanced Notification System
 * Handles in-app notifications and email notifications with improved error handling
 */

class NotificationSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializeDatabase();
    }
    
    /**
     * Initialize database tables if they don't exist
     */
    private function initializeDatabase() {
        try {
            // Insert default email templates if they don't exist
            $this->insertDefaultTemplates();
            
        } catch (Exception $e) {
            error_log('Notification system database initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Insert default email templates
     */
    private function insertDefaultTemplates() {
        $templates = [
            [
                'template_key' => 'welcome',
                'subject' => 'Welcome to {{site_name}} - Start Earning Today!',
                'body' => $this->getWelcomeTemplate()
            ],
            [
                'template_key' => 'deposit_confirmed',
                'subject' => '✅ Deposit Confirmed - {{site_name}}',
                'body' => $this->getDepositConfirmedTemplate()
            ],
            [
                'template_key' => 'withdrawal_processed',
                'subject' => '💰 Withdrawal Processed - {{site_name}}',
                'body' => $this->getWithdrawalProcessedTemplate()
            ],
            [
                'template_key' => 'investment_created',
                'subject' => '📈 Investment Created - {{site_name}}',
                'body' => $this->getInvestmentCreatedTemplate()
            ],
            [
                'template_key' => 'notification',
                'subject' => '{{title}} - {{site_name}}',
                'body' => $this->getNotificationTemplate()
            ]
        ];
        
        foreach ($templates as $template) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO email_templates (template_key, subject, body, is_active) 
                    VALUES (?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                        subject = VALUES(subject),
                        body = VALUES(body),
                        updated_at = NOW()
                ");
                $stmt->execute([$template['template_key'], $template['subject'], $template['body']]);
            } catch (Exception $e) {
                error_log("Failed to insert template {$template['template_key']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($user_id, $title, $message, $type = 'info', $send_email = false) {
        try {
            // Validate user exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                throw new Exception("User not found: {$user_id}");
            }
            
            // Insert notification into database
            $notification_id = generateUUID();
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (id, user_id, title, message, type, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$notification_id, $user_id, $title, $message, $type]);
            
            // Send email if requested
            if ($send_email) {
                $this->sendEmailNotification($user_id, $title, $message, $type);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email notification
     */
    public function sendEmailNotification($user_id, $title, $message, $type = 'info') {
        try {
            // Get user email and details
            $stmt = $this->pdo->prepare("SELECT email, username, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || empty($user['email'])) {
                throw new Exception("User email not found for user: {$user_id}");
            }
            
            // Get email template
            $template = $this->getEmailTemplate('notification', [
                'username' => $user['username'],
                'first_name' => $user['first_name'] ?? $user['username'],
                'last_name' => $user['last_name'] ?? '',
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'site_name' => getSetting('site_name', 'Star Router Rent'),
                'site_url' => getSetting('site_url', 'https://star-rent.vip'),
                'support_email' => getSetting('admin_email', 'support@star-rent.vip')
            ]);
            
            return $this->sendEmail($user['email'], $template['subject'], $template['body']);
            
        } catch (Exception $e) {
            error_log("Failed to send email notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail($user_id, $user_data) {
        try {
            if (empty($user_data['email'])) {
                throw new Exception("Email is required for welcome email");
            }
            
            $template = $this->getEmailTemplate('welcome', [
                'username' => $user_data['username'],
                'first_name' => $user_data['first_name'] ?? $user_data['username'],
                'last_name' => $user_data['last_name'] ?? '',
                'email' => $user_data['email'],
                'site_name' => getSetting('site_name', 'Star Router Rent'),
                'site_url' => getSetting('site_url', 'https://star-rent.vip'),
                'support_email' => getSetting('admin_email', 'support@star-rent.vip')
            ]);
            
            $result = $this->sendEmail($user_data['email'], $template['subject'], $template['body']);
            
            if ($result) {
                // Also create in-app notification
                $this->createNotification(
                    $user_id,
                    'Welcome to ' . getSetting('site_name', 'Star Router Rent'),
                    'Your account has been created successfully! Start earning daily profits today.',
                    'success'
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send welcome email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send deposit confirmation email
     */
    public function sendDepositConfirmationEmail($user_id, $amount, $currency = 'USD', $transaction_id = '') {
        try {
            $stmt = $this->pdo->prepare("SELECT email, username, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception("User not found: {$user_id}");
            }
            
            $template = $this->getEmailTemplate('deposit_confirmed', [
                'username' => $user['username'],
                'first_name' => $user['first_name'] ?? $user['username'],
                'last_name' => $user['last_name'] ?? '',
                'amount' => number_format($amount, 2),
                'currency' => $currency,
                'transaction_id' => $transaction_id,
                'date' => date('M j, Y H:i'),
                'site_name' => getSetting('site_name', 'Star Router Rent'),
                'site_url' => getSetting('site_url', 'https://star-rent.vip'),
                'support_email' => getSetting('admin_email', 'support@star-rent.vip')
            ]);
            
            $result = $this->sendEmail($user['email'], $template['subject'], $template['body']);
            
            if ($result) {
                // Also create in-app notification
                $this->createNotification(
                    $user_id,
                    'Deposit Confirmed',
                    "Your deposit of $" . number_format($amount, 2) . " has been confirmed and added to your account.",
                    'success'
                );
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send deposit confirmation email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get email template with variable replacement
     */
    public function getEmailTemplate($template_key, $variables = []) {
        try {
            // Get template from database
            $stmt = $this->pdo->prepare("SELECT * FROM email_templates WHERE template_key = ? AND is_active = 1");
            $stmt->execute([$template_key]);
            $template = $stmt->fetch();
            
            if (!$template) {
                // Use default template
                $template = $this->getDefaultTemplate($template_key);
            }
            
            // Replace variables
            $subject = $template['subject'];
            $body = $template['body'];
            
            foreach ($variables as $key => $value) {
                $subject = str_replace('{{' . $key . '}}', $value, $subject);
                $body = str_replace('{{' . $key . '}}', $value, $body);
            }
            
            return [
                'subject' => $subject,
                'body' => $body
            ];
            
        } catch (Exception $e) {
            error_log("Failed to get email template: " . $e->getMessage());
            return $this->getDefaultTemplate('notification');
        }
    }
    
    /**
     * Send email using configured SMTP settings or PHP mail
     */
    public function sendEmail($to, $subject, $body) {
        try {
            if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address: {$to}");
            }
            
            $smtp_host = getSetting('smtp_host');
            $smtp_port = getSetting('smtp_port', 587);
            $smtp_username = getSetting('smtp_username');
            $smtp_password = getSetting('smtp_password');
            $from_email = getSetting('admin_email', 'noreply@star-rent.vip');
            $site_name = getSetting('site_name', 'Star Router Rent');
            
            // Prepare headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $site_name . ' <' . $from_email . '>',
                'Reply-To: ' . $from_email,
                'X-Mailer: PHP/' . phpversion(),
                'X-Priority: 3',
                'Return-Path: ' . $from_email
            ];
            
            // Use PHP mail function (can be enhanced with PHPMailer later)
            $result = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($result) {
                error_log("Email sent successfully to: {$to}");
            } else {
                error_log("Failed to send email to: {$to}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $limit = 20, $unread_only = false) {
        try {
            $where_clause = "WHERE user_id = ?";
            $params = [$user_id];
            
            if ($unread_only) {
                $where_clause .= " AND is_read = 0";
            }
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                $where_clause 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $params[] = $limit;
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to get user notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            error_log("Failed to mark notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            return $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("Failed to mark all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch()['count'];
        } catch (Exception $e) {
            error_log("Failed to get unread notification count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete old notifications
     */
    public function cleanupOldNotifications($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            return $stmt->execute([$days]);
        } catch (Exception $e) {
            error_log("Failed to cleanup old notifications: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get default email templates
     */
    private function getDefaultTemplate($template_key) {
        $templates = [
            'welcome' => [
                'subject' => 'Welcome to {{site_name}}!',
                'body' => $this->getWelcomeTemplate()
            ],
            'deposit_confirmed' => [
                'subject' => 'Deposit Confirmed - {{site_name}}',
                'body' => $this->getDepositConfirmedTemplate()
            ],
            'notification' => [
                'subject' => '{{title}} - {{site_name}}',
                'body' => $this->getNotificationTemplate()
            ]
        ];
        
        return $templates[$template_key] ?? $templates['notification'];
    }
    
    /**
     * Welcome email template
     */
    private function getWelcomeTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to {{site_name}}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
        .welcome-box { background: #e8f5e8; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745; }
        .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .footer { font-size: 12px; color: #666; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="font-size: 3rem; margin-bottom: 15px;">🌟</div>
        <h1 style="margin: 0;">Welcome to {{site_name}}!</h1>
        <p style="margin: 10px 0 0; opacity: 0.9;">Your journey to financial freedom starts here</p>
    </div>
    <div class="content">
        <h2 style="color: #28a745;">🎉 Account Created Successfully!</h2>
        <p>Hello {{first_name}} {{last_name}},</p>
        <div class="welcome-box">
            <p><strong>Congratulations!</strong> Your {{site_name}} account has been created successfully. You can now start earning daily profits through our premium router rental platform.</p>
        </div>
        <h3>🚀 Next Steps:</h3>
        <ol>
            <li>Make your first deposit (minimum $100)</li>
            <li>Choose an investment plan that suits you</li>
            <li>Start earning daily profits up to 2%</li>
            <li>Share your referral code and earn 10% commission</li>
        </ol>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{site_url}}/user/dashboard.php" class="btn">🎛️ Access Dashboard</a>
            <a href="{{site_url}}/user/deposit.php" class="btn">💰 Make Deposit</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br><strong>Star Rent Team</strong><br><a href="{{site_url}}">{{site_name}}</a></p>
        <div class="footer">
            <p>For support, contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
            <p><a href="{{site_url}}">Visit our website</a></p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Deposit confirmed email template
     */
    private function getDepositConfirmedTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Deposit Confirmed</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
        .success-box { background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #28a745; }
        .btn { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .footer { font-size: 12px; color: #666; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">✅ Deposit Confirmed!</h1>
        <p style="margin: 10px 0 0; opacity: 0.9;">Your funds have been added to your account</p>
    </div>
    <div class="content">
        <p>Hello {{first_name}} {{last_name}},</p>
        <div class="success-box">
            <p><strong>Great news!</strong> Your deposit of <strong>${{amount}}</strong> has been confirmed and added to your {{site_name}} account.</p>
        </div>
        <h3>📊 Transaction Details:</h3>
        <ul>
            <li><strong>Amount:</strong> ${{amount}}</li>
            <li><strong>Currency:</strong> {{currency}}</li>
            <li><strong>Transaction ID:</strong> {{transaction_id}}</li>
            <li><strong>Date:</strong> {{date}}</li>
        </ul>
        <p>You can now start investing and earning daily profits!</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{site_url}}/user/dashboard.php" class="btn">📊 View Dashboard</a>
            <a href="{{site_url}}/user/investments.php" class="btn">💰 Start Investing</a>
        </div>
        <p style="margin-top: 30px;">Best regards,<br><strong>Star Rent Team</strong><br><a href="{{site_url}}">{{site_name}}</a></p>
        <div class="footer">
            <p>For support, contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Withdrawal processed email template
     */
    private function getWithdrawalProcessedTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Withdrawal Processed</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #17a2b8, #138496); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
        .info-box { background: #d1ecf1; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #17a2b8; }
        .footer { font-size: 12px; color: #666; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">💰 Withdrawal Processed</h1>
        <p style="margin: 10px 0 0; opacity: 0.9;">Your withdrawal has been completed</p>
    </div>
    <div class="content">
        <p>Hello {{first_name}} {{last_name}},</p>
        <p>Your withdrawal request has been processed successfully.</p>
        <div class="info-box">
            <p><strong>Withdrawal Details:</strong></p>
            <p>Amount: ${{amount}}</p>
            <p>Fee: ${{fee}}</p>
            <p>Net Amount: ${{net_amount}}</p>
            <p>Method: {{method}}</p>
            <p>Address: {{address}}</p>
        </div>
        <p>The funds should arrive in your wallet within 24-48 hours.</p>
        <p style="margin-top: 30px;">Best regards,<br><strong>Star Rent Team</strong></p>
        <div class="footer">
            <p>For support, contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Investment created email template
     */
    private function getInvestmentCreatedTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Investment Created</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
        .investment-box { background: #f3e5f5; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #7b1fa2; }
        .footer { font-size: 12px; color: #666; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">📈 Investment Created!</h1>
        <p style="margin: 10px 0 0; opacity: 0.9;">Your investment is now active</p>
    </div>
    <div class="content">
        <p>Hello {{first_name}} {{last_name}},</p>
        <div class="investment-box">
            <p><strong>Your investment has been created successfully!</strong></p>
            <p>Plan: {{plan_name}}</p>
            <p>Amount: ${{amount}}</p>
            <p>Daily Rate: {{daily_rate}}%</p>
            <p>Duration: {{duration}} days</p>
            <p>Expected Daily Profit: ${{daily_profit}}</p>
        </div>
        <p>Your investment is now active and you will start earning daily profits!</p>
        <p style="margin-top: 30px;">Best regards,<br><strong>Star Rent Team</strong></p>
        <div class="footer">
            <p>For support, contact us at <a href="mailto:{{support_email}}">{{support_email}}</a></p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * General notification email template
     */
    private function getNotificationTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{title}}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
        .message-box { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .footer { font-size: 12px; color: #666; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">{{site_name}}</h1>
    </div>
    <div class="content">
        <h2 style="color: #333; margin-bottom: 20px;">{{title}}</h2>
        <p style="margin-bottom: 20px;">Hello {{username}},</p>
        <div class="message-box">
            {{message}}
        </div>
        <p>Best regards,<br>{{site_name}} Team</p>
        <div class="footer">
            <p>This email was sent from {{site_name}}. If you have any questions, please contact our support team.</p>
        </div>
    </div>
</body>
</html>';
    }
}
?>