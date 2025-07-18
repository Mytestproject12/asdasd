</div>
        </main>
    </div>
    
    <script>
        // Enhanced admin sidebar management
        class AdminSidebarManager {
            constructor() {
                this.sidebar = document.getElementById('sidebar');
                this.mainContent = document.querySelector('.main-content');
                this.mobileMenuBtn = document.getElementById('mobileMenuBtn');
                this.mobileOverlay = document.getElementById('mobileOverlay');
                this.navLinks = document.querySelectorAll('.nav-link');
                
                this.isDesktop = window.innerWidth > 768;
                this.isMobile = window.innerWidth <= 768;
                this.autoHideEnabled = false;
                
                this.init();
            }
            
            init() {
                this.createSidebarToggle();
                this.bindEvents();
                this.handleResize();
                this.setupAutoHide();
                this.wrapTables();
            }
            
            createSidebarToggle() {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'sidebar-toggle';
                toggleBtn.innerHTML = '☰';
                toggleBtn.setAttribute('aria-label', 'Toggle Admin Sidebar');
                document.body.appendChild(toggleBtn);
                
                this.sidebarToggle = toggleBtn;
            }
            
            bindEvents() {
                // Mobile menu button
                if (this.mobileMenuBtn) {
                    this.mobileMenuBtn.addEventListener('click', () => this.toggleMobileSidebar());
                }
                
                // Mobile overlay
                if (this.mobileOverlay) {
                    this.mobileOverlay.addEventListener('click', () => this.closeMobileSidebar());
                }
                
                // Desktop sidebar toggle
                if (this.sidebarToggle) {
                    this.sidebarToggle.addEventListener('click', () => this.toggleDesktopSidebar());
                }
                
                // Close mobile menu when clicking on nav links
                this.navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (this.isMobile) {
                            this.closeMobileSidebar();
                        }
                    });
                });
                
                // Window resize handler
                window.addEventListener('resize', () => this.handleResize());
                
                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isMobile) {
                        this.closeMobileSidebar();
                    }
                    
                    if (e.ctrlKey && e.key === 'b') {
                        e.preventDefault();
                        if (this.isDesktop) {
                            this.toggleDesktopSidebar();
                        } else {
                            this.toggleMobileSidebar();
                        }
                    }
                });
                
                // Auto-hide on scroll for desktop
                let scrollTimer;
                window.addEventListener('scroll', () => {
                    if (this.isDesktop && this.autoHideEnabled) {
                        clearTimeout(scrollTimer);
                        this.hideSidebar();
                        
                        scrollTimer = setTimeout(() => {
                            if (!this.sidebar?.matches(':hover')) {
                                // Keep hidden
                            }
                        }, 2000);
                    }
                });
            }
            
            handleResize() {
                const newIsDesktop = window.innerWidth > 768;
                const newIsMobile = window.innerWidth <= 768;
                
                if (newIsDesktop !== this.isDesktop) {
                    this.isDesktop = newIsDesktop;
                    this.isMobile = newIsMobile;
                    
                    if (this.isDesktop) {
                        this.closeMobileSidebar();
                        this.setupAutoHide();
                    } else {
                        this.resetDesktopSidebar();
                    }
                }
            }
            
            setupAutoHide() {
                if (this.isDesktop) {
                    setTimeout(() => {
                        this.autoHideEnabled = true;
                    }, 3000);
                }
            }
            
            toggleMobileSidebar() {
                const isActive = this.sidebar?.classList.contains('active');
                if (isActive) {
                    this.closeMobileSidebar();
                } else {
                    this.openMobileSidebar();
                }
            }
            
            openMobileSidebar() {
                this.sidebar?.classList.add('active');
                this.mobileOverlay?.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            closeMobileSidebar() {
                this.sidebar?.classList.remove('active');
                this.mobileOverlay?.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            toggleDesktopSidebar() {
                const isHidden = this.sidebar?.classList.contains('auto-hide');
                if (isHidden) {
                    this.showSidebar();
                } else {
                    this.hideSidebar();
                }
            }
            
            showSidebar() {
                this.sidebar?.classList.remove('auto-hide');
                this.mainContent?.classList.remove('sidebar-hidden');
                this.sidebarToggle?.classList.remove('show');
            }
            
            hideSidebar() {
                this.sidebar?.classList.add('auto-hide');
                this.mainContent?.classList.add('sidebar-hidden');
                this.sidebarToggle?.classList.add('show');
            }
            
            resetDesktopSidebar() {
                this.sidebar?.classList.remove('auto-hide');
                this.mainContent?.classList.remove('sidebar-hidden');
                this.sidebarToggle?.classList.remove('show');
                this.autoHideEnabled = false;
            }
            
            wrapTables() {
                // Wrap all tables in responsive containers
                document.querySelectorAll('table:not(.table-responsive table)').forEach(table => {
                    if (!table.parentElement.classList.contains('table-responsive')) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'table-responsive';
                        table.parentNode.insertBefore(wrapper, table);
                        wrapper.appendChild(table);
                    }
                });
            }
        }
        
        // Initialize admin sidebar manager
        document.addEventListener('DOMContentLoaded', function() {
            new AdminSidebarManager();
        });
        
        // Theme management
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = newTheme === 'dark' ? '☀️' : '🌓';
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const toggleButton = document.querySelector('.theme-toggle');
            toggleButton.textContent = savedTheme === 'dark' ? '☀️' : '🌓';
        });
        
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        });
        
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            mobileOverlay.classList.remove('active');
        });
        
        // Close sidebar when clicking on nav links on mobile
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    mobileOverlay.classList.remove('active');
                }
            });
        });
        
        // Add loading states for forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    
                    // Re-enable after 10 seconds as fallback
                    setTimeout(() => {
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                    }, 10000);
                }
            });
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
        
        // Performance optimization: Debounce resize events
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        const optimizedResize = debounce(() => {
            window.dispatchEvent(new CustomEvent('optimizedResize'));
        }, 250);
        
        window.addEventListener('resize', optimizedResize);
    </script>
</body>
</html>