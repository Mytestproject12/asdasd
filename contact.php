<?php
require_once 'config.php';
require_once 'includes/session_config.php';

$site_name = getSetting('site_name', 'Star Router Rent');
$admin_email = getSetting('admin_email', 'support@star-rent.vip');
$site_url = getSetting('site_url', 'https://star-rent.vip');

$success = '';
$error = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Send email to admin
        $email_subject = "Contact Form: " . $subject;
        $email_body = "
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(htmlspecialchars($message)) . "</p>
        <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <noreply@' . parse_url($site_url, PHP_URL_HOST) . '>',
            'Reply-To: ' . $email,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        if (mail($admin_email, $email_subject, $email_body, implode("\r\n", $headers))) {
            $success = 'Thank you for your message! We will get back to you within 24 hours.';
        } else {
            $error = 'Failed to send message. Please try again or contact us directly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo htmlspecialchars($site_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --bg-color: #ffffff;
            --text-color: #333333;
            --border-color: #e9ecef;
            --shadow-color: rgba(0, 0, 0, 0.08);
            --light-bg: #f8f9fa;
            --dark-color: #2c3e50;
        }
        
        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333333;
            --shadow-color: rgba(255, 255, 255, 0.1);
            --light-bg: #2d2d2d;
            --dark-color: #0d1117;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: var(--light-bg);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 2rem;
        }
        
        .contact-info {
            background: var(--bg-color);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--border-color);
        }
        
        .contact-form {
            background: var(--bg-color);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--border-color);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-color);
            color: var(--text-color);
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .btn {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .contact-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .container {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="<?php echo htmlspecialchars($site_url); ?>/" class="logo">🌟 <?php echo htmlspecialchars($site_name); ?></a>
            <ul class="nav-links">
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/">Home</a></li>
                <li><a href="pages.php?page=about">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/user/login.php">Login</a></li>
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/user/register.php">Register</a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <h1 style="font-size: 2.5rem; font-weight: 700; text-align: center; margin-bottom: 1rem; color: var(--text-color);">Contact Us</h1>
        <p style="text-align: center; color: var(--text-color); opacity: 0.8; font-size: 1.1rem;">Get in touch with our support team</p>
        
        <div class="contact-grid">
            <div class="contact-info">
                <h2 style="margin-bottom: 2rem; color: var(--text-color);">Get in Touch</h2>
                
                <div class="contact-item">
                    <div class="contact-icon">📧</div>
                    <div>
                        <h3 style="color: var(--text-color);">Email Support</h3>
                        <p style="color: var(--text-color); opacity: 0.8;"><?php echo htmlspecialchars($admin_email); ?></p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">📱</div>
                    <div>
                        <h3 style="color: var(--text-color);">Telegram</h3>
                        <p style="color: var(--text-color); opacity: 0.8;">@starrouter_support</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">⏰</div>
                    <div>
                        <h3 style="color: var(--text-color);">Response Time</h3>
                        <p style="color: var(--text-color); opacity: 0.8;">Within 24 hours</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">🌍</div>
                    <div>
                        <h3 style="color: var(--text-color);">Support Hours</h3>
                        <p style="color: var(--text-color); opacity: 0.8;">24/7 Customer Support</p>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <h2 style="margin-bottom: 2rem; color: var(--text-color);">Send us a Message</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>