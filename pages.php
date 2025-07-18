<?php
require_once 'config.php';
require_once 'includes/session_config.php';

$page = $_GET['page'] ?? '';
$allowed_pages = ['terms', 'privacy', 'about', 'contact', 'faq', 'help', 'disclaimer', 'how-it-works', 'security'];

if (!in_array($page, $allowed_pages)) {
    header('HTTP/1.0 404 Not Found');
    require_once '404.php';
    exit;
}

// Get page content from database
try {
    $stmt = $pdo->prepare("SELECT * FROM webpages WHERE slug = ? AND status = 'published'");
    $stmt->execute([$page]);
    $page_data = $stmt->fetch();
} catch (Exception $e) {
    error_log('Pages database error: ' . $e->getMessage());
    $page_data = null;
}

$site_name = getSetting('site_name', 'Star Router Rent');
$site_url = getSetting('site_url', 'https://star-rent.vip');

// Default content if not found in database
$default_content = [
    'terms' => [
        'title' => 'Terms of Service',
        'content' => '<h1>Terms of Service</h1>
        <p>Welcome to ' . $site_name . '. By using our platform, you agree to these terms.</p>
        <h2>1. Investment Risks</h2>
        <p>All investments carry risk. Past performance does not guarantee future results.</p>
        <h2>2. User Responsibilities</h2>
        <p>Users must provide accurate information and comply with all applicable laws.</p>
        <h2>3. Platform Usage</h2>
        <p>Our platform is for legitimate investment purposes only.</p>'
    ],
    'privacy' => [
        'title' => 'Privacy Policy',
        'content' => '<h1>Privacy Policy</h1>
        <p>Your privacy is important to us. This policy explains how we collect and use your data.</p>
        <h2>Information We Collect</h2>
        <p>We collect information you provide directly, such as account details and transaction data.</p>
        <h2>How We Use Information</h2>
        <p>We use your information to provide services, process transactions, and improve our platform.</p>
        <h2>Data Security</h2>
        <p>We implement industry-standard security measures to protect your data.</p>'
    ],
    'about' => [
        'title' => 'About Us',
        'content' => '<h1>About ' . $site_name . '</h1>
        <p>We are a leading platform for router rental investments, providing guaranteed daily returns through our innovative technology.</p>
        <h2>Our Mission</h2>
        <p>To democratize access to profitable technology investments and provide sustainable passive income opportunities.</p>
        <h2>Why Choose Us</h2>
        <ul>
        <li>Guaranteed daily returns up to 2%</li>
        <li>Premium router devices worldwide</li>
        <li>24/7 customer support</li>
        <li>Secure cryptocurrency payments</li>
        <li>Multi-level referral program</li>
        </ul>'
    ],
    'contact' => [
        'title' => 'Contact Us',
        'content' => '<h1>Contact Us</h1>
        <p>Get in touch with our support team for any questions or assistance.</p>
        <h2>Support Channels</h2>
        <p><strong>Email:</strong> ' . getSetting('admin_email', 'support@star-rent.vip') . '</p>
        <p><strong>Telegram:</strong> @starrouter_support</p>
        <p><strong>Response Time:</strong> Within 24 hours</p>
        <h2>Business Hours</h2>
        <p>Our support team is available 24/7 to assist you with any questions or concerns.</p>'
    ],
    'faq' => [
        'title' => 'FAQ',
        'content' => '<h1>Frequently Asked Questions</h1>
        <h2>How do I start investing?</h2>
        <p>Create an account, make a deposit, and choose an investment plan that suits your budget.</p>
        <h2>What are the minimum and maximum investment amounts?</h2>
        <p>Minimum investment is $100, maximum varies by plan up to $50,000.</p>
        <h2>How are profits calculated?</h2>
        <p>Profits are calculated daily based on your investment amount and the plan\'s daily rate.</p>
        <h2>When can I withdraw?</h2>
        <p>You can withdraw your earnings anytime. Withdrawals are processed within 24-48 hours.</p>'
    ],
    'help' => [
        'title' => 'Help Center',
        'content' => '<h1>Help Center</h1>
        <h2>Getting Started</h2>
        <p>Learn how to create your account and make your first investment.</p>
        <h2>Investment Plans</h2>
        <p>Understand our different investment plans and their benefits.</p>
        <h2>Withdrawals</h2>
        <p>Learn how to withdraw your earnings safely and securely.</p>
        <h2>Referral Program</h2>
        <p>Discover how to earn commissions by referring friends.</p>'
    ],
    'disclaimer' => [
        'title' => 'Risk Disclaimer',
        'content' => '<h1>Risk Disclaimer</h1>
        <p><strong>Important:</strong> All investments involve risk and may result in loss of capital.</p>
        <h2>Investment Risks</h2>
        <p>Cryptocurrency and technology investments are subject to market volatility and regulatory changes.</p>
        <h2>No Guarantee</h2>
        <p>Past performance does not guarantee future results. Projected returns are estimates only.</p>
        <h2>Regulatory Notice</h2>
        <p>Please ensure compliance with your local laws and regulations before investing.</p>'
    ],
    'how-it-works' => [
        'title' => 'How It Works',
        'content' => '<h1>How ' . $site_name . ' Works</h1>
        <h2>Step 1: Register</h2>
        <p>Create your free account in minutes with just basic information.</p>
        <h2>Step 2: Deposit</h2>
        <p>Add funds using cryptocurrency (Bitcoin, USDT, Ethereum, etc.)</p>
        <h2>Step 3: Invest</h2>
        <p>Choose an investment plan that matches your goals and budget.</p>
        <h2>Step 4: Earn</h2>
        <p>Watch your investment grow with guaranteed daily returns.</p>
        <h2>Step 5: Withdraw</h2>
        <p>Withdraw your profits anytime to your cryptocurrency wallet.</p>'
    ],
    'security' => [
        'title' => 'Security',
        'content' => '<h1>Security & Safety</h1>
        <h2>Platform Security</h2>
        <p>We use bank-level security measures including SSL encryption and secure payment processing.</p>
        <h2>Fund Protection</h2>
        <p>Your investments are protected through multiple security layers and regular audits.</p>
        <h2>Privacy Protection</h2>
        <p>We never share your personal information with third parties without consent.</p>
        <h2>Secure Payments</h2>
        <p>All payments are processed through secure cryptocurrency gateways with real-time monitoring.</p>'
    ]
];

$title = $page_data['title'] ?? $default_content[$page]['title'];
$content = $page_data['content'] ?? $default_content[$page]['content'];
$meta_title = $page_data['meta_title'] ?? $title . ' - ' . $site_name;
$meta_description = $page_data['meta_description'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta_title); ?></title>
    <?php if ($meta_description): ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php endif; ?>
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
        
        .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            margin-left: 1rem;
        }
        
        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
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
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 300px;
            height: 100vh;
            background: var(--bg-color);
            box-shadow: -5px 0 15px var(--shadow-color);
            border-left: 1px solid var(--border-color);
            transition: right 0.3s ease;
            z-index: 1001;
            padding: 2rem 1rem;
        }
        
        .mobile-menu.active {
            right: 0;
        }
        
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
        }
        
        .mobile-menu-overlay.active {
            display: block;
        }
        
        .mobile-menu-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-color);
        }
        
        .mobile-nav-links {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }
        
        .mobile-nav-links li {
            margin-bottom: 1rem;
        }
        
        .mobile-nav-links a {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1rem;
            display: block;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        
        .page-content {
            background: var(--bg-color);
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--border-color);
        }
        
        .page-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--text-color);
        }
        
        .page-content h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 2rem 0 1rem;
            color: var(--text-color);
        }
        
        .page-content h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 1.5rem 0 1rem;
            color: var(--text-color);
        }
        
        .page-content p {
            margin-bottom: 1rem;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .page-content ul,
        .page-content ol {
            margin: 1rem 0 1rem 2rem;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .page-content li {
            margin-bottom: 0.5rem;
        }
        
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .nav-links {
                display: none;
            }
            
            .theme-toggle {
                margin-left: 0;
            }
            
            .container {
                padding: 2rem 1rem;
            }
            
            .page-content {
                padding: 2rem;
            }
            
            .page-content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    
    <header class="header">
        <nav class="nav-container">
            <a href="<?php echo htmlspecialchars($site_url); ?>/" class="logo">🌟 <?php echo htmlspecialchars($site_name); ?></a>
            <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
            <ul class="nav-links">
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/">Home</a></li>
                <li><a href="pages.php?page=about">About</a></li>
                <li><a href="pages.php?page=contact">Contact</a></li>
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/user/login.php">Login</a></li>
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/user/register.php">Register</a></li>
            </ul>
            <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
        </nav>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <button class="mobile-menu-close" id="mobileMenuClose">×</button>
            <ul class="mobile-nav-links">
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/">Home</a></li>
                <li><a href="pages.php?page=about">About</a></li>
                <li><a href="pages.php?page=contact">Contact</a></li>
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/user/login.php">Login</a></li>
                <li><a href="<?php echo htmlspecialchars($site_url); ?>/user/register.php">Register</a></li>
            </ul>
            <button class="theme-toggle" onclick="toggleTheme()" style="margin-top: 2rem;">🌓</button>
        </div>
    </header>
    
    <div class="container">
        <div class="page-content">
            <?php echo $content; ?>
        </div>
    </div>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 <?php echo htmlspecialchars($site_name); ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update theme toggle icons
            const toggleButtons = document.querySelectorAll('.theme-toggle');
            toggleButtons.forEach(btn => {
                btn.textContent = newTheme === 'dark' ? '☀️' : '🌓';
            });
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Update theme toggle icons
            const toggleButtons = document.querySelectorAll('.theme-toggle');
            toggleButtons.forEach(btn => {
                btn.textContent = savedTheme === 'dark' ? '☀️' : '🌓';
            });
        });
        
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuClose = document.getElementById('mobileMenuClose');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        
        // Open mobile menu
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            mobileMenuOverlay.classList.add('active');
        });
        
        // Close mobile menu
        mobileMenuClose.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
        });
        
        // Close menu when clicking overlay
        mobileMenuOverlay.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
        });
        
        // Close menu when clicking on a link
        const mobileNavLinks = document.querySelectorAll('.mobile-nav-links a');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
            });
        });
        
        // Desktop header auto-hide functionality
        let lastScrollTop = 0;
        const header = document.querySelector('.header');
        
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (window.innerWidth > 768) { // Only on desktop
                if (scrollTop > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }
            
            lastScrollTop = scrollTop;
        });
    </script>
</body>
</html>