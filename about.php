<?php
// about.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

// Get some stats for the about page
$db = new Database();
$conn = $db->getConnection();

// Get total listings
$stmt = $conn->query("SELECT COUNT(*) as total FROM listings WHERE status = 'active'");
$total_listings = $stmt->fetch()['total'];

// Get total users
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$total_users = $stmt->fetch()['total'];

// Get total categories
$stmt = $conn->query("SELECT COUNT(*) as total FROM categories WHERE status = 'active'");
$total_categories = $stmt->fetch()['total'];

// Get total messages exchanged
$stmt = $conn->query("SELECT COUNT(*) as total FROM messages");
$total_messages = $stmt->fetch()['total'];

require_once 'includes/header.php';
?>

<!-- Hero Banner -->
<div class="py-5" style="background: var(--primary-gradient); color: white;">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">About MarketNearMe</h1>
        <p class="lead mb-0">Connecting local communities through trusted marketplace solutions</p>
    </div>
</div>

<!-- Mission & Vision -->
<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <h2 class="mb-4" style="color: var(--primary-color);">Our Mission</h2>
            <p class="lead mb-3">
                To create a safe, reliable, and user-friendly platform where local communities can connect, 
                trade, and thrive together.
            </p>
            <p class="text-muted mb-4">
                MarketNearMe was founded with a simple vision: make local buying and selling easier, safer, 
                and more accessible for everyone. We believe in the power of community and the importance 
                of supporting local economies.
            </p>
            <div class="row g-3">
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3" style="color: var(--success-color);"></i>
                        <div>
                            <h5 class="mb-0">Trust & Safety</h5>
                            <small class="text-muted">Verified users & secure messaging</small>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3" style="color: var(--success-color);"></i>
                        <div>
                            <h5 class="mb-0">Free to Use</h5>
                            <small class="text-muted">No hidden fees or charges</small>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3" style="color: var(--success-color);"></i>
                        <div>
                            <h5 class="mb-0">Local Focus</h5>
                            <small class="text-muted">Community-driven marketplace</small>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-2x me-3" style="color: var(--success-color);"></i>
                        <div>
                            <h5 class="mb-0">Multi-Currency</h5>
                            <small class="text-muted">Support for global currencies</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=600&h=400&fit=crop" 
                 alt="Marketplace" class="img-fluid rounded-3 shadow-lg">
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="py-5" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%);">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="p-4 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-users fa-3x mb-3" style="color: var(--primary-color);"></i>
                    <h2 class="fw-bold mb-1"><?php echo number_format($total_users); ?>+</h2>
                    <p class="text-muted mb-0">Active Users</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-tags fa-3x mb-3" style="color: var(--secondary-color);"></i>
                    <h2 class="fw-bold mb-1"><?php echo number_format($total_listings); ?>+</h2>
                    <p class="text-muted mb-0">Active Listings</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-folder-open fa-3x mb-3" style="color: var(--accent-color);"></i>
                    <h2 class="fw-bold mb-1"><?php echo $total_categories; ?>+</h2>
                    <p class="text-muted mb-0">Categories</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-comments fa-3x mb-3" style="color: var(--info-color);"></i>
                    <h2 class="fw-bold mb-1"><?php echo number_format($total_messages); ?>+</h2>
                    <p class="text-muted mb-0">Messages Sent</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="container py-5">
    <h2 class="text-center mb-5">How MarketNearMe Works</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm text-center">
                <div class="card-body p-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-4" 
                         style="width: 80px; height: 80px; background: var(--primary-gradient); color: white;">
                        <i class="fas fa-user-plus fa-2x"></i>
                    </div>
                    <h4>1. Create Account</h4>
                    <p class="text-muted">
                        Sign up for free in less than a minute. Set up your profile, add your location, 
                        and choose your preferred currency.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm text-center">
                <div class="card-body p-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-4" 
                         style="width: 80px; height: 80px; background: var(--secondary-gradient); color: white;">
                        <i class="fas fa-camera-retro fa-2x"></i>
                    </div>
                    <h4>2. Post Your Listing</h4>
                    <p class="text-muted">
                        Take photos, write a description, set your price and currency, 
                        and publish your listing. It's completely free!
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm text-center">
                <div class="card-body p-4">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-4" 
                         style="width: 80px; height: 80px; background: var(--accent-gradient); color: white;">
                        <i class="fas fa-handshake fa-2x"></i>
                    </div>
                    <h4>3. Connect & Trade</h4>
                    <p class="text-muted">
                        Browse listings, message sellers directly, and arrange to meet locally. 
                        Trade safely within your community.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Why Choose Us -->
<div class="py-5" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%);">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose MarketNearMe?</h2>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-shield-alt fa-3x mb-3" style="color: var(--success-color);"></i>
                        <h5>Safe & Secure</h5>
                        <p class="text-muted small">
                            Advanced security measures protect your data. Encrypted messages, 
                            secure authentication, and verified user profiles.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-bolt fa-3x mb-3" style="color: var(--warning-color);"></i>
                        <h5>Lightning Fast</h5>
                        <p class="text-muted small">
                            Optimized performance ensures quick loading times. 
                            Find what you need and connect with sellers instantly.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-mobile-alt fa-3x mb-3" style="color: var(--info-color);"></i>
                        <h5>Mobile Friendly</h5>
                        <p class="text-muted small">
                            Fully responsive design works seamlessly on all devices. 
                            Browse and post listings from your phone or tablet.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-headset fa-3x mb-3" style="color: var(--accent-color);"></i>
                        <h5>24/7 Support</h5>
                        <p class="text-muted small">
                            Our dedicated support team is always ready to help. 
                            Report issues, get assistance, and trade with confidence.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features -->
<div class="container py-5">
    <h2 class="text-center mb-5">Key Features</h2>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="d-flex mb-4">
                <div class="flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px; background: var(--primary-gradient); color: white;">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5>Advanced Search & Filters</h5>
                    <p class="text-muted">
                        Find exactly what you need with powerful search capabilities. 
                        Filter by category, price, location, currency, and condition.
                    </p>
                </div>
            </div>
            <div class="d-flex mb-4">
                <div class="flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px; background: var(--secondary-gradient); color: white;">
                        <i class="fas fa-globe"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5>Multi-Currency Support</h5>
                    <p class="text-muted">
                        List items in your preferred currency. Support for USD, EUR, GBP, 
                        KES, NGN, ZAR, and many more currencies.
                    </p>
                </div>
            </div>
            <div class="d-flex mb-4">
                <div class="flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px; background: var(--accent-gradient); color: white;">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5>Featured Listings</h5>
                    <p class="text-muted">
                        Premium listings get highlighted placement. 
                        Stand out from the crowd and reach more potential buyers.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="d-flex mb-4">
                <div class="flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px; background: var(--info-gradient); color: white;">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5>Real-Time Messaging</h5>
                    <p class="text-muted">
                        Communicate directly with buyers and sellers through our secure 
                        messaging system. Get instant notifications.
                    </p>
                </div>
            </div>
            <div class="d-flex mb-4">
                <div class="flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px; background: var(--success-color); color: white;">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5>Favorites & Watchlist</h5>
                    <p class="text-muted">
                        Save listings you're interested in for later. 
                        Never miss a great deal with your personal watchlist.
                    </p>
                </div>
            </div>
            <div class="d-flex mb-4">
                <div class="flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 50px; height: 50px; background: var(--danger-color); color: white;">
                        <i class="fas fa-flag"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5>Report & Moderation</h5>
                    <p class="text-muted">
                        Help keep our community safe. Report suspicious listings 
                        and our moderation team will review them promptly.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Categories -->
<div class="py-5" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%);">
    <div class="container">
        <h2 class="text-center mb-5">Browse by Category</h2>
        <div class="row g-3">
            <?php 
            $categories = DBHelper::getCategories();
            foreach (array_slice($categories, 0, 8) as $category): 
            ?>
            <div class="col-6 col-md-3">
                <a href="/marketnearme/search.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 hover-lift">
                        <div class="card-body text-center py-4">
                            <i class="fas <?php echo htmlspecialchars($category['icon'] ?? 'fa-tag'); ?> fa-2x mb-3" 
                               style="color: var(--primary-color);"></i>
                            <h6 class="mb-1"><?php echo htmlspecialchars($category['name']); ?></h6>
                            <small class="text-muted"><?php echo $category['listing_count']; ?> listings</small>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="/marketnearme/search.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-th-list"></i> View All Categories
            </a>
        </div>
    </div>
</div>

<!-- Team/Contact -->
<div class="container py-5">
    <div class="row align-items-center">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <h2 class="mb-4">Get in Touch</h2>
            <p class="lead mb-4">
                Have questions, suggestions, or need help? We'd love to hear from you!
            </p>
            <div class="d-flex mb-3">
                <i class="fas fa-envelope fa-2x me-3" style="color: var(--primary-color);"></i>
                <div>
                    <h6 class="mb-1">Email Us</h6>
                    <p class="text-muted mb-0">support@marketnearme.com</p>
                </div>
            </div>
            <div class="d-flex mb-3">
                <i class="fas fa-phone fa-2x me-3" style="color: var(--secondary-color);"></i>
                <div>
                    <h6 class="mb-1">Call Us</h6>
                    <p class="text-muted mb-0">+1 (234) 567-8900</p>
                </div>
            </div>
            <div class="d-flex mb-3">
                <i class="fas fa-map-marker-alt fa-2x me-3" style="color: var(--accent-color);"></i>
                <div>
                    <h6 class="mb-1">Visit Us</h6>
                    <p class="text-muted mb-0">123 Marketplace Street, Business District</p>
                </div>
            </div>
            <div class="mt-4">
                <h6>Follow Us</h6>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-outline-primary rounded-circle" style="width: 45px; height: 45px;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="btn btn-outline-info rounded-circle" style="width: 45px; height: 45px;">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="btn btn-outline-danger rounded-circle" style="width: 45px; height: 45px;">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="btn btn-outline-primary rounded-circle" style="width: 45px; height: 45px;">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <h4 class="mb-4">Send us a Message</h4>
                    <form action="/marketnearme/contact.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo Auth::isLoggedIn() ? htmlspecialchars($_SESSION['user_full_name'] ?? '') : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo Auth::isLoggedIn() ? htmlspecialchars($_SESSION['user_email'] ?? '') : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Select a topic</option>
                                <option value="general">General Inquiry</option>
                                <option value="support">Technical Support</option>
                                <option value="report">Report a Problem</option>
                                <option value="suggestion">Suggestion</option>
                                <option value="partnership">Partnership</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required 
                                      placeholder="How can we help you?"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-lg w-100" style="background: var(--primary-gradient); color: white;">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="py-5" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%);">
    <div class="container">
        <h2 class="text-center mb-5">Frequently Asked Questions</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Is MarketNearMe free to use?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Yes! MarketNearMe is completely free to use. You can create an account, 
                                post listings, browse items, and message sellers without any charges. 
                                We believe in providing a free platform for local communities to connect and trade.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How do I stay safe when trading?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                We recommend meeting in public places, bringing a friend, and using cash or secure payment methods. 
                                Always communicate through our platform's messaging system, and report any suspicious activity. 
                                Check seller profiles and reviews before making transactions.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What currencies are supported?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                We support multiple currencies including USD, EUR, GBP, KES, NGN, ZAR, GHS, INR, 
                                AUD, CAD, JPY, CNY, AED, SAR, and more. You can select your preferred currency 
                                when creating listings and set a default currency in your profile settings.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                How do I report a suspicious listing?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                You can report any listing by clicking the "Report" button on the listing page. 
                                Our moderation team reviews all reports and takes appropriate action. 
                                You can also contact us directly through the contact form or email.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                What are featured listings?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                Featured listings are premium listings that appear prominently on our platform. 
                                They get more visibility and reach more potential buyers. 
                                Contact our team if you're interested in featuring your listing.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="py-5" style="background: var(--primary-gradient); color: white;">
    <div class="container text-center">
        <h2 class="mb-3">Ready to Get Started?</h2>
        <p class="lead mb-4">Join thousands of users in your community. It's free and easy!</p>
        <div class="d-flex justify-content-center gap-3">
            <?php if (!Auth::isLoggedIn()): ?>
            <a href="/marketnearme/register.php" class="btn btn-light btn-lg px-4">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <?php endif; ?>
            <a href="/marketnearme/search.php" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-search"></i> Browse Listings
            </a>
            <a href="/marketnearme/post-ad.php" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-plus-circle"></i> Post Free Ad
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>