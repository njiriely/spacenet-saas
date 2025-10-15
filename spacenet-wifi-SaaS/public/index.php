<?php
// public/index.php - Main Landing Page
session_start();
require_once '../includes/Database.php';
require_once '../includes/Tenant.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPACE NET SaaS - WiFi Billing System for ISPs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #0097A7;
            --secondary: #FFFFFF;
            --light-bg: #E0F7FA;
            --dark-text: #263238;
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #F44336;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .pricing-card {
            border-radius: 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card.popular {
            border: 3px solid var(--warning);
            transform: scale(1.05);
        }
        
        .pricing-card.popular::before {
            content: "MOST POPULAR";
            position: absolute;
            top: 20px;
            right: -30px;
            background: var(--warning);
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 25px;
            padding: 10px 30px;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .trial-badge {
            background: linear-gradient(45deg, var(--success), #4CAF50);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="fas fa-satellite me-2"></i>SPACE NET SaaS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">Pricing</a></li>
                    <li class="nav-item"><a class="nav-link" href="#demo">Demo</a></li>
                    <li class="nav-item"><a class="nav-link" href="#support">Support</a></li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="#signup" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#signupModal">Start Free Trial</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="trial-badge">
                        <i class="fas fa-gift me-2"></i>15 Days Free Trial - No Credit Card Required
                    </div>
                    <h1 class="display-4 fw-bold mb-4">WiFi Billing System for Internet Service Providers</h1>
                    <p class="lead mb-4">Complete SaaS solution with MikroTik integration, M-Pesa payments, and multi-tenant management. Start serving customers in minutes, not months.</p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <button class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#signupModal">
                            <i class="fas fa-rocket me-2"></i>Start Free Trial
                        </button>
                        <button class="btn btn-outline-light btn-lg" data-bs-toggle="modal" data-bs-target="#demoModal">
                            <i class="fas fa-play me-2"></i>Watch Demo
                        </button>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-network-wired" style="font-size: 12rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Everything Your ISP Needs</h2>
                <p class="lead text-muted">Comprehensive features designed specifically for Kenyan Internet Service Providers</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center">
                            <i class="fas fa-router text-primary mb-3" style="font-size: 3rem;"></i>
                            <h5>MikroTik Integration</h5>
                            <p class="text-muted">Direct integration with MikroTik RouterOS. Automatic user creation, session management, and bandwidth control.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center">
                            <i class="fas fa-mobile-alt text-success mb-3" style="font-size: 3rem;"></i>
                            <h5>M-Pesa & PesaPal</h5>
                            <p class="text-muted">Integrated Kenyan payment gateways. STK Push, card payments, and automatic transaction processing.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center">
                            <i class="fas fa-users text-info mb-3" style="font-size: 3rem;"></i>
                            <h5>Multi-Tenant</h5>
                            <p class="text-muted">Serve multiple ISP clients from one platform. Isolated environments, custom branding, and subdomain routing.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center">
                            <i class="fas fa-shield-alt text-warning mb-3" style="font-size: 3rem;"></i>
                            <h5>Hotspot Protection</h5>
                            <p class="text-muted">Prevent unauthorized internet sharing. MAC address binding and device limit enforcement.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center">
                            <i class="fas fa-chart-line text-danger mb-3" style="font-size: 3rem;"></i>
                            <h5>Real-time Analytics</h5>
                            <p class="text-muted">Comprehensive reporting, user analytics, revenue tracking, and performance monitoring.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center">
                            <i class="fas fa-cog text-primary mb-3" style="font-size: 3rem;"></i>
                            <h5>Easy Management</h5>
                            <p class="text-muted">Intuitive admin dashboard, package configuration, user management, and automated billing.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Simple, Transparent Pricing</h2>
                <p class="lead text-muted">Choose the plan that fits your ISP business size</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="card pricing-card h-100 text-center p-4">
                        <h5 class="card-title">Standard</h5>
                        <div class="h2 text-primary my-3">Ksh 1,999<small class="text-muted">/month</small></div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Up to 1,000 users</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Basic analytics</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>M-Pesa integration</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Email support</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>MikroTik integration</li>
                        </ul>
                        <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#signupModal" data-plan="standard">Start Free Trial</button>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card pricing-card popular h-100 text-center p-4">
                        <h5 class="card-title">Professional</h5>
                        <div class="h2 text-primary my-3">Ksh 4,999<small class="text-muted">/month</small></div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Up to 5,000 users</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Advanced analytics</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All payment gateways</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Priority support</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Custom branding</li>
                        </ul>
                        <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#signupModal" data-plan="professional">Start Free Trial</button>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card pricing-card h-100 text-center p-4">
                        <h5 class="card-title">Enterprise</h5>
                        <div class="h2 text-primary my-3">Ksh 9,999<small class="text-muted">/month</small></div>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Unlimited users</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Real-time analytics</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>API access</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>24/7 phone support</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Multi-location</li>
                        </ul>
                        <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#signupModal" data-plan="enterprise">Start Free Trial</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Signup Modal -->
    <div class="modal fade" id="signupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white;">
                    <h5 class="modal-title">Start Your 15-Day Free Trial</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="signupForm" method="POST" action="register.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Company Name *</label>
                                    <input type="text" name="company_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Person *</label>
                                    <input type="text" name="contact_person" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" name="phone" class="form-control" placeholder="+254..." required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Subscription Plan</label>
                                    <select name="subscription_plan" class="form-select" id="planSelect">
                                        <option value="standard">Standard - Ksh 1,999/month</option>
                                        <option value="professional" selected>Professional - Ksh 4,999/month</option>
                                        <option value="enterprise">Enterprise - Ksh 9,999/month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Estimated Users</label>
                                    <select name="estimated_users" class="form-select">
                                        <option value="1-100">1 - 100 users</option>
                                        <option value="101-500">101 - 500 users</option>
                                        <option value="501-1000">501 - 1,000 users</option>
                                        <option value="1001-5000">1,001 - 5,000 users</option>
                                        <option value="5000+">5,000+ users</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">MikroTik Router Information (Optional)</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="text" name="mikrotik_ip" class="form-control" placeholder="Router IP">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="mikrotik_username" class="form-control" placeholder="Username">
                                </div>
                                <div class="col-md-4">
                                    <input type="password" name="mikrotik_password" class="form-control" placeholder="Password">
                                </div>
                            </div>
                            <small class="text-muted">You can configure this later in your dashboard</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="agree_terms" id="agreeTerms" required>
                                <label class="form-check-label" for="agreeTerms">
                                    I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Free Trial Benefits:</strong>
                            <ul class="mb-0 mt-2">
                                <li>15 days full access to all features</li>
                                <li>No credit card required</li>
                                <li>Setup assistance included</li>
                                <li>Cancel anytime during trial</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="signupForm" class="btn btn-primary">
                        <i class="fas fa-rocket me-2"></i>Start Free Trial
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-satellite me-2"></i>SPACE NET SaaS</h5>
                    <p>Comprehensive WiFi billing platform for Internet Service Providers across Kenya. Built by ISPs, for ISPs.</p>
                </div>
                <div class="col-md-2">
                    <h6>Platform</h6>
                    <ul class="list-unstyled">
                        <li><a href="#features" class="text-light">Features</a></li>
                        <li><a href="#pricing" class="text-light">Pricing</a></li>
                        <li><a href="api-docs.php" class="text-light">API Docs</a></li>
                        <li><a href="integrations.php" class="text-light">Integrations</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="help.php" class="text-light">Help Center</a></li>
                        <li><a href="setup-guide.php" class="text-light">Setup Guide</a></li>
                        <li><a href="tutorials.php" class="text-light">Video Tutorials</a></li>
                        <li><a href="status.php" class="text-light">System Status</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6>Company</h6>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-light">About Us</a></li>
                        <li><a href="blog.php" class="text-light">Blog</a></li>
                        <li><a href="careers.php" class="text-light">Careers</a></li>
                        <li><a href="contact.php" class="text-light">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6>Contact</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> +254 700 123 456</li>
                        <li><i class="fas fa-envelope me-2"></i> hello@spacenet.co.ke</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Nairobi, Kenya</li>
                    </ul>
                </div>
            </div>
            <hr class="bg-light">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-0">© 2025 SPACE NET SaaS. All rights reserved. Made in Kenya 🇰🇪</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="privacy.php" class="text-light me-3">Privacy</a>
                    <a href="terms.php" class="text-light me-3">Terms</a>
                    <a href="cookies.php" class="text-light">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle plan selection from pricing cards
        document.addEventListener('DOMContentLoaded', function() {
            const signupModal = document.getElementById('signupModal');
            const planSelect = document.getElementById('planSelect');
            
            signupModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const plan = button.getAttribute('data-plan');
                if (plan) {
                    planSelect.value = plan;
                }
            });
            
            // Form validation
            document.getElementById('signupForm').addEventListener('submit', function(e) {
                const phone = document.querySelector('input[name="phone"]').value;
                if (phone && !phone.startsWith('+254') && !phone.startsWith('254') && !phone.startsWith('0')) {
                    alert('Please enter a valid Kenyan phone number starting with +254, 254, or 0');
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
