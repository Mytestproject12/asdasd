</div>
    
    <script>
        // Enhanced mobile menu functionality with auto-hide and responsive features
        class SidebarManager {
            constructor() {
                this.sidebar = document.querySelector('.sidebar');
                this.mainContent = document.querySelector('.main-content');
                this.mobileMenuBtn = document.getElementById('mobileMenuBtn');
                this.mobileOverlay = document.getElementById('mobileOverlay');
                this.sidebarLinks = this.sidebar?.querySelectorAll('a') || [];
                
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
            }
            
            createSidebarToggle() {
                // Create desktop sidebar toggle button
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'sidebar-toggle';
                toggleBtn.innerHTML = '☰';
                toggleBtn.setAttribute('aria-label', 'Toggle Sidebar');
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
                
                // Close mobile menu when clicking on links
                this.sidebarLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (this.isMobile) {
                            this.closeMobileSidebar();
                        }
                    });
                });
                
                // Window resize handler
                window.addEventListener('resize', () => this.handleResize());
                
                // Scroll handler for auto-hide
                let scrollTimer;
                window.addEventListener('scroll', () => {
                    if (this.isDesktop && this.autoHideEnabled) {
                        clearTimeout(scrollTimer);
                        this.sidebar?.classList.add('auto-hide');
                        this.mainContent?.classList.add('sidebar-hidden');
                        this.sidebarToggle?.classList.add('show');
                        
                        scrollTimer = setTimeout(() => {
                            if (!this.sidebar?.matches(':hover')) {
                                this.showSidebar();
                            }
                        }, 3000);
                    }
                });
                
                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isMobile) {
                        this.closeMobileSidebar();
                    }
                    
                    // Toggle sidebar with Ctrl+B
                    if (e.ctrlKey && e.key === 'b') {
                        e.preventDefault();
                        if (this.isDesktop) {
                            this.toggleDesktopSidebar();
                        } else {
                            this.toggleMobileSidebar();
                        }
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
                        this.sidebarToggle?.classList.remove('show');
                    } else {
                        this.resetDesktopSidebar();
                        this.sidebarToggle?.classList.remove('show');
                    }
                }
                
                // Update mobile menu button visibility
                if (this.mobileMenuBtn) {
                    this.mobileMenuBtn.style.display = this.isMobile ? 'block' : 'none';
                }
            }
            
            setupAutoHide() {
                if (this.isDesktop) {
                    // Enable auto-hide after 5 seconds of inactivity
                    setTimeout(() => {
                        this.autoHideEnabled = true;
                    }, 5000);
                }
            }
            
            toggleMobileSidebar() {
                if (!this.sidebar || !this.mobileOverlay) return;
                
                const isActive = this.sidebar.classList.contains('active');
                
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
                if (!this.sidebar || !this.mainContent) return;
                
                const isHidden = this.sidebar.classList.contains('auto-hide');
                
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
                this.autoHideEnabled = false;
            }
        }
        
        // Initialize sidebar manager when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            new SidebarManager();
        });
        
        // Legacy mobile menu functionality for backward compatibility
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
        
        // Add loading states for forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
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
        
        // Add table responsiveness
        document.querySelectorAll('table').forEach(table => {
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
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
        
        // Optimized resize handler
        const optimizedResize = debounce(() => {
            // Trigger custom resize event for components that need it
            window.dispatchEvent(new CustomEvent('optimizedResize'));
        }, 250);
        
        window.addEventListener('resize', optimizedResize);
    </script>
</body>
</html>