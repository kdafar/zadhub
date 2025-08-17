<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zad Hub - Connecting Innovation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-navy: #2c3e50;
            --primary-blue: #3498db;
            --primary-orange: #ff8c42;
            --dark-navy: #1a252f;
            --light-blue: #74b9ff;
            --light-orange: #ffab73;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(44, 62, 80, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(255, 140, 66, 0.2);
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
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-z {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }

        .logo-text {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-menu a:hover {
            color: var(--primary-orange);
        }

        .cta-button {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--light-orange) 100%);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 140, 66, 0.4);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--primary-navy) 50%, var(--primary-blue) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a"><stop offset="0" stop-color="%23ff8c42" stop-opacity=".1"/><stop offset="1" stop-color="%23ff8c42" stop-opacity="0"/></radialGradient></defs><g opacity=".5"><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="400" cy="700" r="120" fill="url(%23a)"/></g></svg>') repeat;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(-30px, -30px) rotate(120deg); }
            66% { transform: translate(30px, -20px) rotate(240deg); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            padding: 0 2rem;
        }

        .hero h1 {
            font-size: clamp(3rem, 6vw, 5rem);
            font-weight: 700;
            margin-bottom: 1.5rem;
            opacity: 0;
            animation: slideUp 1s ease 0.2s forwards;
        }

        .hero-highlight {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--light-orange) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0;
            animation: slideUp 1s ease 0.4s forwards;
            color: #e8f4f8;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            opacity: 0;
            animation: slideUp 1s ease 0.6s forwards;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--light-orange) 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 140, 66, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            padding: 1rem 2rem;
            border: 2px solid var(--primary-orange);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--primary-orange);
            color: white;
            transform: translateY(-3px);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background: linear-gradient(135deg, #f8faff 0%, #e8f4f8 100%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-navy);
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.2rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(44, 62, 80, 0.1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(52, 152, 219, 0.1);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(44, 62, 80, 0.15);
            border-color: var(--primary-orange);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-navy);
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--primary-navy) 0%, var(--dark-navy) 100%);
            color: white;
            text-align: center;
            position: relative;
        }

        .stats::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23ff8c42" stroke-width="0.5" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }

        .stats-content {
            position: relative;
            z-index: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .stat-item {
            padding: 1rem;
            border-radius: 15px;
            background: rgba(255, 140, 66, 0.1);
            border: 1px solid rgba(255, 140, 66, 0.2);
        }

        .stat-item h3 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-orange) 0%, var(--light-orange) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-item p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Services Section */
        .services {
            padding: 5rem 0;
            background: white;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .service-card {
            background: linear-gradient(135deg, #f8faff 0%, #e8f4f8 100%);
            padding: 2rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-orange);
        }

        .service-card:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(44, 62, 80, 0.1);
        }

        .service-card h3 {
            color: var(--primary-navy);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .service-card p {
            color: #666;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-navy) 100%);
            color: white;
            text-align: center;
            position: relative;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="b"><stop offset="0" stop-color="%23ff8c42" stop-opacity=".05"/><stop offset="1" stop-color="%23ff8c42" stop-opacity="0"/></radialGradient></defs><g><circle cx="200" cy="200" r="200" fill="url(%23b)"/><circle cx="800" cy="800" r="250" fill="url(%23b)"/></g></svg>');
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
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
            background: var(--dark-navy);
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h4 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--primary-orange);
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: var(--primary-orange);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--primary-navy);
            color: #ccc;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                max-width: 300px;
            }

            .logo-text {
                font-size: 1.2rem;
            }
        }

        /* Scroll animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .logo { display:flex; align-items:center; gap:.6rem; }
.logo-icon { width:44px; height:44px; border-radius:12px; overflow:hidden; background:#f3f4f6; display:flex; align-items:center; justify-content:center; }
.logo-icon img { width:100%; height:100%; object-fit:cover; display:block; }

    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">
            <div class="logo-icon">
                <img src="/logo.jpeg" alt="Zad Hub logo">
            </div>
            <div class="logo-text">Zad Hub</div>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="#home">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
            <a href="#connect" class="cta-button">Connect Now</a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Welcome to <br><span class="hero-highlight">Zad Hub</span></h1>
            <p>Your central connection point for innovation, technology, and digital transformation. We bring ideas to life through cutting-edge solutions.</p>
            <div class="hero-buttons">
                <a href="#services" class="btn-primary">Explore Services</a>
                <a href="#about" class="btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="about">
        <div class="container">
            <div class="section-title fade-in">
                <h2>Why Choose Zad Hub</h2>
                <p>We're your trusted partner in digital transformation, connecting businesses with innovative solutions that drive growth and success</p>
            </div>
            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">⚡</div>
                    <h3>Lightning Fast Solutions</h3>
                    <p>Rapid deployment of cutting-edge technology solutions that get your business moving at the speed of innovation.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">🔒</div>
                    <h3>Enterprise Security</h3>
                    <p>Bank-level security protocols and data protection ensuring your business and customer information stays safe.</p>
                </div>
                <div class="feature-card fade-in">
                    <div class="feature-icon">🚀</div>
                    <h3>Scalable Innovation</h3>
                    <p>Future-proof solutions that grow with your business, from startup to enterprise-level operations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="container">
            <div class="section-title fade-in">
                <h2>Our Services</h2>
                <p>Comprehensive digital solutions tailored to your business needs</p>
            </div>
            <div class="services-grid">
                <div class="service-card fade-in">
                    <h3>Digital Transformation</h3>
                    <p>Complete digital overhaul of your business processes, integrating modern technology to streamline operations and enhance productivity.</p>
                </div>
                <div class="service-card fade-in">
                    <h3>Cloud Solutions</h3>
                    <p>Seamless migration to cloud infrastructure with ongoing support, ensuring scalability, security, and cost-effectiveness.</p>
                </div>
                <div class="service-card fade-in">
                    <h3>Data Analytics</h3>
                    <p>Transform your data into actionable insights with advanced analytics and business intelligence solutions.</p>
                </div>
                <div class="service-card fade-in">
                    <h3>Custom Development</h3>
                    <p>Bespoke software solutions designed specifically for your business requirements and operational workflows.</p>
                </div>
                <div class="service-card fade-in">
                    <h3>IT Consulting</h3>
                    <p>Strategic technology consulting to align your IT infrastructure with your business goals and future growth plans.</p>
                </div>
                <div class="service-card fade-in">
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock technical support and maintenance to ensure your systems run smoothly without interruption.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-content">
                <div class="section-title">
                    <h2>Trusted by Industry Leaders</h2>
                    <p>Join the growing network of businesses transforming through Zad Hub</p>
                </div>
                <div class="stats-grid">
                    <div class="stat-item fade-in">
                        <h3>500+</h3>
                        <p>Successful Projects</p>
                    </div>
                    <div class="stat-item fade-in">
                        <h3>150+</h3>
                        <p>Happy Clients</p>
                    </div>
                    <div class="stat-item fade-in">
                        <h3>25+</h3>
                        <p>Countries Served</p>
                    </div>
                    <div class="stat-item fade-in">
                        <h3>99.9%</h3>
                        <p>Uptime Guarantee</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="connect">
        <div class="container">
            <div class="cta-content fade-in">
                <h2>Ready to Transform Your Business?</h2>
                <p>Connect with Zad Hub today and discover how we can accelerate your digital transformation journey.</p>
                <a href="#contact" class="btn-primary">Get Started Today</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="logo">
            <div class="logo-icon">
                <img src="/logo.jpeg" alt="Zad Hub logo">
            </div>
            <div class="logo-text">Zad Hub</div>
            </div>
                    <p>Connecting businesses with innovative digital solutions for sustainable growth and transformation.</p>
                </div>
                <div class="footer-section">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="#digital">Digital Transformation</a></li>
                        <li><a href="#cloud">Cloud Solutions</a></li>
                        <li><a href="#analytics">Data Analytics</a></li>
                        <li><a href="#development">Custom Development</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#careers">Careers</a></li>
                        <li><a href="#news">News</a></li>
                        <li><a href="#partners">Partners</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#help">Help Center</a></li>
                        <li><a href="#contact">Contact Us</a></li>
                        <li><a href="#docs">Documentation</a></li>
                        <li><a href="#status">System Status</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Zad Hub. All rights reserved. | Connecting Innovation Worldwide</p>
            </div>
        </div>
    </footer>

    <script>
        // Scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all fade-in elements
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(44, 62, 80, 0.98)';
                header.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.3)';
            } else {
                header.style.background = 'rgba(44, 62, 80, 0.95)';
                header.style.boxShadow = 'none';
            }
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

        // Counter animation for stats
        const animateCounters = () => {
            const counters = document.querySelectorAll('.stat-item h3');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/[^0-9.]/g, '').replace('.', ''));
                const isPercentage = counter.textContent.includes('%');
                const isDecimal = counter.textContent.includes('.');
                const suffix = counter.textContent.replace(/[0-9.]/g, '');
                let current = 0;
                const increment = target / 100;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        if (isDecimal) {
                            counter.textContent = (target / 10) + suffix;
                        } else {
                            counter.textContent = target + suffix;
                        }
                        clearInterval(timer);
                    } else {
                        if (isDecimal) {
                            counter.textContent = (Math.floor(current) / 10) + suffix;
                        } else {
                            counter.textContent = Math.floor(current) + suffix;
                        }
                    }
                }, 20);
            });
        };

        // Trigger counter animation when stats section is visible
        const statsSection = document.querySelector('.stats');
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        statsObserver.observe(statsSection);
    </script>
</body>
</html>