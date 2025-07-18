<?php
require_once 'config.php';

// Get system settings
$site_name = getSetting('site_name', 'Star Router Rent');
$site_description = getSetting('site_description', 'Premium router rental platform with guaranteed daily profits');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_name); ?> - Premium Router Rental & Investment Platform</title>
    <meta name="description" content="<?php echo htmlspecialchars($site_description); ?>">
    <meta name="keywords" content="router rental, passive income, daily profits, cryptocurrency, investment">
    <link rel="canonical" href="https://test.star-rent.vip/">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($site_description); ?>">
    <meta property="og:url" content="https://test.star-rent.vip/">
    <meta property="og:type" content="website">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --bg-color: #ffffff;
            --text-color: #333333;
            --border-color: #e9ecef;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333333;
            --shadow-color: rgba(255, 255, 255, 0.1);
            --light-color: #2d2d2d;
            --dark-color: #f8f9fa;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--bg-color);
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
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
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
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
            background: white;
            color: var(--primary-color);
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 8rem 0 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }
        
        /* Features Section */
        .features {
            padding: 5rem 0;
            background: var(--light-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            color: var(--text-color);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: white;
            background: var(--bg-color);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--shadow-color);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px var(--shadow-color);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            display: block;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        .feature-card p {
            color: var(--text-color);
            opacity: 0.8;
            line-height: 1.6;
        }
        
        /* Investment Plans */
        .plans {
            padding: 5rem 0;
            background: var(--bg-color);
        }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .plan-card {
            background: var(--bg-color);
            border: 2px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .plan-card.featured {
            border-color: var(--primary-color);
            transform: scale(1.05);
            box-shadow: 0 20px 40px var(--shadow-color);
        }
        
        .plan-card.featured::before {
            content: 'MOST POPULAR';
            position: absolute;
            top: 20px;
            right: -30px;
            background: var(--primary-color);
            color: white;
            padding: 5px 40px;
            font-size: 0.8rem;
            font-weight: 600;
            transform: rotate(45deg);
        }
        
        .plan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px var(--shadow-color);
        }
        
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        .plan-rate {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .plan-period {
            color: var(--text-color);
            opacity: 0.7;
            margin-bottom: 2rem;
        }
        
        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }
        
        .plan-features li {
            padding: 0.5rem 0;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .plan-features li::before {
            content: '✓';
            color: var(--success-color);
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 5rem 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 3rem 0 1rem;
        }
        
        [data-theme="dark"] .footer {
            background: #0d1117;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section ul li a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-section ul li a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 1rem;
            }
            
            .nav-links,
            .cta-buttons {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
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
                color: #333;
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
            
            .mobile-cta-buttons {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                margin-top: 2rem;
            }
            
            .theme-toggle {
                margin-left: 0;
                margin-top: 1rem;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .hero-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .hero-content {
                padding: 0 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .plan-card.featured {
                transform: none;
            }
            
            .hero .cta-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .features-grid,
            .plans-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .feature-card,
            .plan-card {
                padding: 2rem 1.5rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .hero {
                padding: 6rem 0 3rem;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .plan-rate {
                font-size: 2rem;
            }
            
            .feature-card,
            .plan-card {
                padding: 1.5rem 1rem;
            }
            
            .section-title {
                font-size: 1.75rem;
            }
            
            .cta-section h2 {
                font-size: 2rem;
            }
            
            .cta-section p {
                font-size: 1rem;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .feature-card,
        .plan-card,
        .stat-card {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .feature-card:nth-child(2) { animation-delay: 0.1s; }
        .feature-card:nth-child(3) { animation-delay: 0.2s; }
        .plan-card:nth-child(2) { animation-delay: 0.1s; }
        .plan-card:nth-child(3) { animation-delay: 0.2s; }
        .plan-card:nth-child(4) { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <a href="/" class="logo">
                🌟 <?php echo htmlspecialchars($site_name); ?>
            </a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#plans">Plans</a></li>
                <li><a href="pages.php?page=about">About</a></li>
                <li><a href="pages.php?page=how-it-works">How It Works</a></li>
                <li><a href="pages.php?page=contact">Contact</a></li>
            </ul>
            <button class="mobile-menu-btn" id="mobileMenuBtn" style="display: none;">☰</button>
            <div class="cta-buttons">
                <a href="/user/login.php" class="btn btn-secondary">Login</a>
                <a href="/user/register.php" class="btn btn-primary">Get Started</a>
                <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
            </div>
        </nav>
        
        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <button class="mobile-menu-close" id="mobileMenuClose">×</button>
            <ul class="mobile-nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#plans">Plans</a></li>
                <li><a href="pages.php?page=about">About</a></li>
                <li><a href="pages.php?page=how-it-works">How It Works</a></li>
                <li><a href="pages.php?page=contact">Contact</a></li>
            </ul>
            <div class="mobile-cta-buttons">
                <a href="/user/login.php" class="btn btn-secondary">Login</a>
                <a href="/user/register.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Premium Router Rental Platform</h1>
            <p>Earn guaranteed daily profits by renting premium routers and investing in our proven plans. Start with as little as $100 and watch your income grow.</p>
            
            <div class="hero-stats">
                <div class="stat-card">
                    <span class="stat-number">2.0%</span>
                    <span class="stat-label">Daily Returns</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">99.9%</span>
                    <span class="stat-label">Uptime</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Support</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">$100</span>
                    <span class="stat-label">Min. Investment</span>
                </div>
            </div>
            
            <div class="cta-buttons">
                <a href="/user/register.php" class="btn btn-primary">Start Earning Today</a>
                <a href="#plans" class="btn btn-secondary">View Plans</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="section-title">Why Choose Star Router Rent?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">🚀</span>
                    <h3>Guaranteed Returns</h3>
                    <p>Earn up to 2% daily returns on your investments with our proven router rental system and premium devices.</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">🔒</span>
                    <h3>Secure Platform</h3>
                    <p>Your investments are protected with bank-level security, SSL encryption, and secure cryptocurrency payments.</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">💰</span>
                    <h3>Multiple Income Streams</h3>
                    <p>Earn from router rentals, investment plans, and our multi-level referral program with up to 10% commissions.</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">🌍</span>
                    <h3>Global Network</h3>
                    <p>Access premium routers in major cities worldwide including New York, London, Tokyo, and Singapore.</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">📱</span>
                    <h3>Easy Management</h3>
                    <p>Monitor your investments, track earnings, and manage withdrawals through our intuitive dashboard.</p>
                </div>
                <div class="feature-card">
                    <span class="feature-icon">⚡</span>
                    <h3>Instant Activation</h3>
                    <p>Start earning immediately after deposit confirmation. No waiting periods or complex approval processes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Investment Plans -->
    <section id="plans" class="plans">
        <div class="container">
            <h2 class="section-title">Choose Your Investment Plan</h2>
            <div class="plans-grid">
                <div class="plan-card">
                    <h3 class="plan-name">Starter Plan</h3>
                    <div class="plan-rate">0.8%</div>
                    <div class="plan-period">Daily for 30 days</div>
                    <ul class="plan-features">
                        <li>$100 - $1,000 investment</li>
                        <li>24% total return</li>
                        <li>24/7 support</li>
                        <li>Instant activation</li>
                        <li>Daily payouts</li>
                    </ul>
                    <a href="/user/register.php" class="btn btn-primary">Get Started</a>
                </div>
                
                <div class="plan-card featured">
                    <h3 class="plan-name">Growth Plan</h3>
                    <div class="plan-rate">1.2%</div>
                    <div class="plan-period">Daily for 90 days</div>
                    <ul class="plan-features">
                        <li>$500 - $5,000 investment</li>
                        <li>108% total return</li>
                        <li>Priority support</li>
                        <li>Weekly reports</li>
                        <li>Bonus features</li>
                    </ul>
                    <a href="/user/register.php" class="btn btn-primary">Most Popular</a>
                </div>
                
                <div class="plan-card">
                    <h3 class="plan-name">Premium Plan</h3>
                    <div class="plan-rate">1.5%</div>
                    <div class="plan-period">Daily for 180 days</div>
                    <ul class="plan-features">
                        <li>$1,000 - $10,000 investment</li>
                        <li>270% total return</li>
                        <li>VIP support</li>
                        <li>Daily reports</li>
                        <li>Premium features</li>
                    </ul>
                    <a href="/user/register.php" class="btn btn-primary">Invest Now</a>
                </div>
                
                <div class="plan-card">
                    <h3 class="plan-name">Elite Plan</h3>
                    <div class="plan-rate">2.0%</div>
                    <div class="plan-period">Daily for 365 days</div>
                    <ul class="plan-features">
                        <li>$5,000 - $50,000 investment</li>
                        <li>730% total return</li>
                        <li>Personal manager</li>
                        <li>Real-time analytics</li>
                        <li>Elite benefits</li>
                    </ul>
                    <a href="/user/register.php" class="btn btn-primary">Elite Access</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Start Earning?</h2>
            <p>Join thousands of satisfied investors earning daily profits with Star Router Rent</p>
            <a href="/user/register.php" class="btn btn-primary">Create Account Now</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Star Router Rent</h3>
                    <p>Premium router rental platform providing guaranteed daily returns through innovative investment plans.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="pages.php?page=about">About Us</a></li>
                        <li><a href="pages.php?page=how-it-works">How It Works</a></li>
                        <li><a href="pages.php?page=contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="pages.php?page=terms">Terms of Service</a></li>
                        <li><a href="pages.php?page=privacy">Privacy Policy</a></li>
                        <li><a href="pages.php?page=disclaimer">Risk Disclaimer</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="pages.php?page=help">Help Center</a></li>
                        <li><a href="pages.php?page=faq">FAQ</a></li>
                        <li><a href="pages.php?page=security">Security</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 <?php echo htmlspecialchars($site_name); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update theme toggle icon
            const toggleButtons = document.querySelectorAll('.theme-toggle');
            toggleButtons.forEach(btn => {
                btn.textContent = newTheme === 'dark' ? '☀️' : '🌓';
            });
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            // Update theme toggle icon
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
        
        // Show mobile menu button on mobile
        if (window.innerWidth <= 768) {
            mobileMenuBtn.style.display = 'block';
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                mobileMenuBtn.style.display = 'block';
            } else {
                mobileMenuBtn.style.display = 'none';
                mobileMenu.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
            }
        });
        
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
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>