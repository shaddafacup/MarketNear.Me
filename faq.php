<?php
// faq.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

require_once 'includes/header.php';
?>

<!-- Hero Banner -->
<div class="py-5" style="background: var(--primary-gradient); color: white;">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Frequently Asked Questions</h1>
        <p class="lead mb-0">Find answers to the most common questions about MarketNearMe</p>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <!-- Search FAQ -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm border-0 position-sticky" style="top: 80px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-search me-2" style="color: var(--primary-color);"></i>
                        Search FAQ
                    </h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="faqSearch" 
                               placeholder="Search questions...">
                    </div>
                    
                    <h6 class="mt-4 mb-3">Categories</h6>
                    <div class="list-group list-group-flush" id="faqCategories">
                        <a href="#general" class="list-group-item list-group-item-action border-0 active">
                            <i class="fas fa-info-circle me-2"></i> General
                        </a>
                        <a href="#account" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-user me-2"></i> Account
                        </a>
                        <a href="#listings" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-list me-2"></i> Listings
                        </a>
                        <a href="#messaging" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-comments me-2"></i> Messaging
                        </a>
                        <a href="#payments" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-money-bill me-2"></i> Payments & Currency
                        </a>
                        <a href="#safety" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-shield-alt me-2"></i> Safety & Security
                        </a>
                        <a href="#technical" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-cog me-2"></i> Technical
                        </a>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <p class="text-muted small mb-2">Can't find what you're looking for?</p>
                        <a href="/marketnearme/contact.php" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-envelope"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Content -->
        <div class="col-lg-9">
            <!-- General Questions -->
            <section id="general" class="mb-5">
                <h2 class="mb-4" style="color: var(--primary-color);">
                    <i class="fas fa-info-circle me-2"></i>General Questions
                </h2>
                <div class="accordion" id="generalAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#gen1">
                                What is MarketNearMe?
                            </button>
                        </h2>
                        <div id="gen1" class="accordion-collapse collapse show" data-bs-parent="#generalAccordion">
                            <div class="accordion-body text-muted">
                                MarketNearMe is a free, local marketplace platform that connects buyers and sellers within their communities. 
                                You can browse listings, post items for sale, message other users, and trade locally. 
                                Our platform supports multiple currencies and is designed to be safe, fast, and easy to use.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gen2">
                                Is MarketNearMe really free?
                            </button>
                        </h2>
                        <div id="gen2" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                            <div class="accordion-body text-muted">
                                <strong>Yes, completely free!</strong> MarketNearMe does not charge any fees for creating an account, 
                                posting listings, browsing items, or messaging other users. There are no hidden costs or subscription fees. 
                                We believe in providing a free platform for local communities.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gen3">
                                How is MarketNearMe different from other marketplaces?
                            </button>
                        </h2>
                        <div id="gen3" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                            <div class="accordion-body text-muted">
                                MarketNearMe focuses on <strong>local community trading</strong>. Key features include:
                                <ul class="mt-2">
                                    <li><strong>Multi-currency support</strong> - List items in your preferred currency</li>
                                    <li><strong>No transaction fees</strong> - We don't take a cut of your sales</li>
                                    <li><strong>Advanced search filters</strong> - Find exactly what you need</li>
                                    <li><strong>Real-time messaging</strong> - Communicate directly with buyers/sellers</li>
                                    <li><strong>Featured listings</strong> - Premium placement for your items</li>
                                    <li><strong>Mobile-friendly design</strong> - Works perfectly on all devices</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gen4">
                                What can I buy and sell on MarketNearMe?
                            </button>
                        </h2>
                        <div id="gen4" class="accordion-collapse collapse" data-bs-parent="#generalAccordion">
                            <div class="accordion-body text-muted">
                                You can buy and sell a wide variety of items across multiple categories including:
                                <ul class="mt-2">
                                    <li>Electronics & Gadgets</li>
                                    <li>Vehicles & Auto Parts</li>
                                    <li>Real Estate & Property</li>
                                    <li>Fashion & Accessories</li>
                                    <li>Home & Garden</li>
                                    <li>Jobs & Services</li>
                                    <li>Sports & Outdoors</li>
                                    <li>And many more!</li>
                                </ul>
                                <p class="mt-2 mb-0"><strong>Note:</strong> Illegal items, counterfeit goods, and prohibited items are not allowed. 
                                Please review our Terms of Service for a complete list of prohibited items.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Account Questions -->
            <section id="account" class="mb-5">
                <h2 class="mb-4" style="color: var(--secondary-color);">
                    <i class="fas fa-user me-2"></i>Account Questions
                </h2>
                <div class="accordion" id="accountAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc1">
                                How do I create an account?
                            </button>
                        </h2>
                        <div id="acc1" class="accordion-collapse collapse" data-bs-parent="#accountAccordion">
                            <div class="accordion-body text-muted">
                                Creating an account is easy and takes less than a minute:
                                <ol class="mt-2">
                                    <li>Click the <strong>"Sign Up Free"</strong> button in the navigation bar</li>
                                    <li>Fill in your details (name, username, email, password)</li>
                                    <li>Choose your preferred currency and location</li>
                                    <li>Agree to the Terms of Service</li>
                                    <li>Click <strong>"Create Account"</strong></li>
                                </ol>
                                <p class="mt-2 mb-0">You'll receive a confirmation email. Verify your email to activate all features.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc2">
                                I forgot my password. How do I reset it?
                            </button>
                        </h2>
                        <div id="acc2" class="accordion-collapse collapse" data-bs-parent="#accountAccordion">
                            <div class="accordion-body text-muted">
                                To reset your password:
                                <ol class="mt-2">
                                    <li>Go to the <a href="/marketnearme/login.php">Login page</a></li>
                                    <li>Click <strong>"Forgot Password?"</strong></li>
                                    <li>Enter your email address</li>
                                    <li>Check your email for a password reset link</li>
                                    <li>Click the link and enter your new password</li>
                                </ol>
                                <p class="mt-2 mb-0">The reset link expires after 1 hour for security purposes.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc3">
                                How do I change my profile information?
                            </button>
                        </h2>
                        <div id="acc3" class="accordion-collapse collapse" data-bs-parent="#accountAccordion">
                            <div class="accordion-body text-muted">
                                You can update your profile at any time:
                                <ol class="mt-2">
                                    <li>Log in to your account</li>
                                    <li>Click on your username in the top navigation</li>
                                    <li>Select <strong>"My Profile"</strong></li>
                                    <li>Go to the <strong>"Edit Profile"</strong> tab</li>
                                    <li>Update your name, phone, location, or preferred currency</li>
                                    <li>Click <strong>"Update Profile"</strong> to save changes</li>
                                </ol>
                                <p class="mt-2 mb-0"><strong>Note:</strong> Username and email address cannot be changed after registration.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acc4">
                                How do I delete my account?
                            </button>
                        </h2>
                        <div id="acc4" class="accordion-collapse collapse" data-bs-parent="#accountAccordion">
                            <div class="accordion-body text-muted">
                                If you wish to delete your account, please contact our support team at 
                                <strong>support@marketnearme.com</strong> with the subject "Account Deletion Request". 
                                Include your username and the email address associated with your account. 
                                Our team will process your request within 48 hours.
                                <p class="mt-2 mb-0 text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Warning:</strong> Account deletion is permanent and cannot be undone. 
                                    All your listings, messages, and data will be permanently removed.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Listings Questions -->
            <section id="listings" class="mb-5">
                <h2 class="mb-4" style="color: var(--accent-color);">
                    <i class="fas fa-list me-2"></i>Listings Questions
                </h2>
                <div class="accordion" id="listingsAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#list1">
                                How do I post a listing?
                            </button>
                        </h2>
                        <div id="list1" class="accordion-collapse collapse" data-bs-parent="#listingsAccordion">
                            <div class="accordion-body text-muted">
                                Posting a listing is simple:
                                <ol class="mt-2">
                                    <li>Log in to your account</li>
                                    <li>Click <strong>"Post Free Ad"</strong> in the navigation bar</li>
                                    <li>Fill in the listing details:
                                        <ul>
                                            <li>Title and description</li>
                                            <li>Category</li>
                                            <li>Price and currency</li>
                                            <li>Condition (New, Like New, Good, Fair, Used)</li>
                                        </ul>
                                    </li>
                                    <li>Upload photos (up to 5 images)</li>
                                    <li>Add your contact information and location</li>
                                    <li>Click <strong>"Post Listing"</strong></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#list2">
                                How many photos can I upload?
                            </button>
                        </h2>
                        <div id="list2" class="accordion-collapse collapse" data-bs-parent="#listingsAccordion">
                            <div class="accordion-body text-muted">
                                You can upload up to <strong>5 photos</strong> per listing. Supported formats are JPG, PNG, and GIF. 
                                Each image must be less than 5MB in size. The first image you upload will be used as the primary/cover image.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#list3">
                                How do I edit or delete my listing?
                            </button>
                        </h2>
                        <div id="list3" class="accordion-collapse collapse" data-bs-parent="#listingsAccordion">
                            <div class="accordion-body text-muted">
                                To manage your listings:
                                <ol class="mt-2">
                                    <li>Go to <strong>"My Profile"</strong> → <strong>"My Listings"</strong> tab, or click <strong>"My Listings"</strong> from the user menu</li>
                                    <li>Find the listing you want to modify</li>
                                    <li>Click the <strong>"Edit"</strong> button (pencil icon) to update details</li>
                                    <li>Click the <strong>"Delete"</strong> button (trash icon) to remove the listing</li>
                                </ol>
                                <p class="mt-2 mb-0"><strong>Note:</strong> Deleted listings cannot be recovered.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#list4">
                                What is a featured listing?
                            </button>
                        </h2>
                        <div id="list4" class="accordion-collapse collapse" data-bs-parent="#listingsAccordion">
                            <div class="accordion-body text-muted">
                                Featured listings are premium listings that receive special placement on our platform. 
                                They appear at the top of search results and on the homepage, giving them maximum visibility. 
                                Featured listings are marked with a special badge to stand out from regular listings.
                                <p class="mt-2 mb-0">To get your listing featured, contact our team at <strong>support@marketnearme.com</strong>.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#list5">
                                How long do listings stay active?
                            </button>
                        </h2>
                        <div id="list5" class="accordion-collapse collapse" data-bs-parent="#listingsAccordion">
                            <div class="accordion-body text-muted">
                                Listings remain active indefinitely until you:
                                <ul class="mt-2">
                                    <li>Manually deactivate or delete them</li>
                                    <li>Mark them as sold</li>
                                    <li>Your account is suspended or deleted</li>
                                </ul>
                                <p class="mt-2 mb-0">We recommend updating or removing listings once items are sold to keep the marketplace current.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Messaging Questions -->
            <section id="messaging" class="mb-5">
                <h2 class="mb-4" style="color: var(--info-color);">
                    <i class="fas fa-comments me-2"></i>Messaging Questions
                </h2>
                <div class="accordion" id="messagingAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#msg1">
                                How do I contact a seller?
                            </button>
                        </h2>
                        <div id="msg1" class="accordion-collapse collapse" data-bs-parent="#messagingAccordion">
                            <div class="accordion-body text-muted">
                                To contact a seller:
                                <ol class="mt-2">
                                    <li>Browse to the listing you're interested in</li>
                                    <li>Click <strong>"Send Message"</strong> on the listing page</li>
                                    <li>Type your message in the chat window</li>
                                    <li>Press Enter or click the send button</li>
                                </ol>
                                <p class="mt-2 mb-0">The seller will receive a notification and can reply through our messaging system.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#msg2">
                                Can I share my phone number or email in messages?
                            </button>
                        </h2>
                        <div id="msg2" class="accordion-collapse collapse" data-bs-parent="#messagingAccordion">
                            <div class="accordion-body text-muted">
                                Yes, you can share contact information in messages. However, for your safety, we recommend:
                                <ul class="mt-2">
                                    <li>Keeping initial communications within our platform</li>
                                    <li>Meeting in public places for transactions</li>
                                    <li>Being cautious about sharing personal information</li>
                                    <li>Using the contact information provided on listings (if the seller has shared it)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Payments & Currency Questions -->
            <section id="payments" class="mb-5">
                <h2 class="mb-4" style="color: var(--success-color);">
                    <i class="fas fa-money-bill me-2"></i>Payments & Currency Questions
                </h2>
                <div class="accordion" id="paymentsAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pay1">
                                Does MarketNearMe process payments?
                            </button>
                        </h2>
                        <div id="pay1" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                            <div class="accordion-body text-muted">
                                <strong>No.</strong> MarketNearMe is a listing and communication platform only. We do not process payments 
                                or handle transactions between buyers and sellers. All payment arrangements are made directly between 
                                the parties involved. We recommend:
                                <ul class="mt-2">
                                    <li>Meeting in person for cash transactions</li>
                                    <li>Using secure payment methods</li>
                                    <li>Being cautious of scams or fraudulent payment requests</li>
                                    <li>Never sending money before seeing the item</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pay2">
                                What currencies are supported?
                            </button>
                        </h2>
                        <div id="pay2" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                            <div class="accordion-body text-muted">
                                MarketNearMe supports multiple currencies including:
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <ul>
                                            <li>USD - US Dollar ($)</li>
                                            <li>EUR - Euro (€)</li>
                                            <li>GBP - British Pound (£)</li>
                                            <li>KES - Kenyan Shilling (KSh)</li>
                                            <li>NGN - Nigerian Naira (₦)</li>
                                            <li>ZAR - South African Rand (R)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul>
                                            <li>GHS - Ghanaian Cedi (GH₵)</li>
                                            <li>INR - Indian Rupee (₹)</li>
                                            <li>AUD - Australian Dollar (A$)</li>
                                            <li>CAD - Canadian Dollar (C$)</li>
                                            <li>JPY - Japanese Yen (¥)</li>
                                            <li>CNY - Chinese Yuan (¥)</li>
                                        </ul>
                                    </div>
                                </div>
                                <p class="mt-2 mb-0">You can set your preferred currency in your profile settings.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pay3">
                                How do I change the currency on my listing?
                            </button>
                        </h2>
                        <div id="pay3" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                            <div class="accordion-body text-muted">
                                You can select a currency when creating or editing a listing:
                                <ol class="mt-2">
                                    <li>When posting a new ad, select your preferred currency from the dropdown</li>
                                    <li>To change an existing listing's currency, edit the listing</li>
                                    <li>Your default currency can be set in your profile settings</li>
                                </ol>
                                <p class="mt-2 mb-0">The price will be displayed with the appropriate currency symbol and formatting.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Safety Questions -->
            <section id="safety" class="mb-5">
                <h2 class="mb-4" style="color: var(--danger-color);">
                    <i class="fas fa-shield-alt me-2"></i>Safety & Security Questions
                </h2>
                <div class="accordion" id="safetyAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#safe1">
                                How do I stay safe when trading?
                            </button>
                        </h2>
                        <div id="safe1" class="accordion-collapse collapse" data-bs-parent="#safetyAccordion">
                            <div class="accordion-body text-muted">
                                Follow these safety tips:
                                <ul class="mt-2">
                                    <li><strong>Meet in public places</strong> - Choose well-lit, busy locations</li>
                                    <li><strong>Bring a friend</strong> - Don't go alone to meet strangers</li>
                                    <li><strong>Use cash</strong> - Cash is the safest payment method for in-person transactions</li>
                                    <li><strong>Trust your instincts</strong> - If something feels wrong, walk away</li>
                                    <li><strong>Verify items</strong> - Inspect items carefully before paying</li>
                                    <li><strong>Keep communications on our platform</strong> - Use our messaging system initially</li>
                                    <li><strong>Report suspicious activity</strong> - Use the report button on listings or contact us</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#safe2">
                                How do I report a suspicious listing or user?
                            </button>
                        </h2>
                        <div id="safe2" class="accordion-collapse collapse" data-bs-parent="#safetyAccordion">
                            <div class="accordion-body text-muted">
                                To report a suspicious listing:
                                <ol class="mt-2">
                                    <li>Go to the listing page</li>
                                    <li>Click the <strong>"Report This Listing"</strong> button</li>
                                    <li>Select a reason for reporting</li>
                                    <li>Provide additional details if needed</li>
                                    <li>Submit the report</li>
                                </ol>
                                <p class="mt-2 mb-0">Our moderation team reviews all reports and takes appropriate action, 
                                which may include removing listings or suspending accounts.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#safe3">
                                How is my personal information protected?
                            </button>
                        </h2>
                        <div id="safe3" class="accordion-collapse collapse" data-bs-parent="#safetyAccordion">
                            <div class="accordion-body text-muted">
                                We take your privacy seriously:
                                <ul class="mt-2">
                                    <li><strong>Encrypted passwords</strong> - All passwords are hashed using bcrypt</li>
                                    <li><strong>Secure sessions</strong> - Sessions are protected against hijacking</li>
                                    <li><strong>CSRF protection</strong> - Forms are protected against cross-site request forgery</li>
                                    <li><strong>XSS prevention</strong> - All user input is sanitized</li>
                                    <li><strong>SQL injection protection</strong> - Prepared statements are used for all database queries</li>
                                    <li><strong>Data encryption</strong> - Sensitive data is encrypted</li>
                                </ul>
                                <p class="mt-2 mb-0">Read our <a href="/marketnearme/privacy.php">Privacy Policy</a> for more details.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Technical Questions -->
            <section id="technical" class="mb-5">
                <h2 class="mb-4" style="color: var(--gray-600);">
                    <i class="fas fa-cog me-2"></i>Technical Questions
                </h2>
                <div class="accordion" id="technicalAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tech1">
                                What browsers are supported?
                            </button>
                        </h2>
                        <div id="tech1" class="accordion-collapse collapse" data-bs-parent="#technicalAccordion">
                            <div class="accordion-body text-muted">
                                MarketNearMe works on all modern browsers:
                                <ul class="mt-2">
                                    <li>Google Chrome (recommended)</li>
                                    <li>Mozilla Firefox</li>
                                    <li>Safari</li>
                                    <li>Microsoft Edge</li>
                                    <li>Opera</li>
                                </ul>
                                <p class="mt-2 mb-0">We recommend keeping your browser updated to the latest version for the best experience.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tech2">
                                Can I use MarketNearMe on my phone?
                            </button>
                        </h2>
                        <div id="tech2" class="accordion-collapse collapse" data-bs-parent="#technicalAccordion">
                            <div class="accordion-body text-muted">
                                <strong>Absolutely!</strong> MarketNearMe is fully responsive and works perfectly on:
                                <ul class="mt-2">
                                    <li>Smartphones (iOS and Android)</li>
                                    <li>Tablets</li>
                                    <li>Desktop computers</li>
                                    <li>Laptops</li>
                                </ul>
                                <p class="mt-2 mb-0">Simply access our website through your mobile browser. No app download required!</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tech3">
                                Why can't I upload my images?
                            </button>
                        </h2>
                        <div id="tech3" class="accordion-collapse collapse" data-bs-parent="#technicalAccordion">
                            <div class="accordion-body text-muted">
                                If you're having trouble uploading images, check the following:
                                <ul class="mt-2">
                                    <li><strong>File format:</strong> Only JPG, PNG, and GIF are accepted</li>
                                    <li><strong>File size:</strong> Each image must be under 5MB</li>
                                    <li><strong>Number of images:</strong> Maximum 5 images per listing</li>
                                    <li><strong>Browser:</strong> Try a different browser if issues persist</li>
                                    <li><strong>Internet connection:</strong> Ensure you have a stable connection</li>
                                </ul>
                                <p class="mt-2 mb-0">If problems continue, <a href="/marketnearme/contact.php">contact support</a>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Still Have Questions -->
            <div class="text-center py-5" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.05) 0%, rgba(124, 58, 237, 0.05) 100%); border-radius: var(--radius-2xl);">
                <i class="fas fa-question-circle fa-4x mb-3" style="color: var(--primary-color);"></i>
                <h3>Still Have Questions?</h3>
                <p class="text-muted mb-4">Can't find what you're looking for? Our support team is here to help.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="/marketnearme/contact.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-envelope"></i> Contact Support
                    </a>
                    <a href="mailto:support@marketnearme.com" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Email Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// FAQ Search functionality
document.getElementById('faqSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const accordionItems = document.querySelectorAll('.accordion-item');
    const sections = document.querySelectorAll('section[id]');
    
    if (searchTerm.length < 2) {
        // Show all items if search is cleared
        accordionItems.forEach(item => item.style.display = '');
        sections.forEach(section => section.style.display = '');
        return;
    }
    
    let foundInSection = {};
    
    accordionItems.forEach(item => {
        const question = item.querySelector('.accordion-button').textContent.toLowerCase();
        const answer = item.querySelector('.accordion-body').textContent.toLowerCase();
        
        if (question.includes(searchTerm) || answer.includes(searchTerm)) {
            item.style.display = '';
            // Expand matching items
            const collapse = item.querySelector('.accordion-collapse');
            if (collapse) {
                new bootstrap.Collapse(collapse, { toggle: false }).show();
            }
            // Track which sections have matches
            const section = item.closest('section');
            if (section) {
                foundInSection[section.id] = true;
            }
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show/hide sections based on matches
    sections.forEach(section => {
        if (searchTerm.length >= 2 && !foundInSection[section.id]) {
            section.style.display = 'none';
        } else {
            section.style.display = '';
        }
    });
    
    // If no results found
    const visibleItems = document.querySelectorAll('.accordion-item[style*="display:"]').length;
    const noResults = document.getElementById('noSearchResults');
    
    if (visibleItems === 0 && searchTerm.length >= 2) {
        if (!noResults) {
            const noResultsDiv = document.createElement('div');
            noResultsDiv.id = 'noSearchResults';
            noResultsDiv.className = 'text-center py-5';
            noResultsDiv.innerHTML = `
                <i class="fas fa-search fa-4x mb-3 text-muted"></i>
                <h4>No Results Found</h4>
                <p class="text-muted">No FAQ matches your search for "<strong>${searchTerm}</strong>"</p>
                <button class="btn btn-outline-primary" onclick="document.getElementById('faqSearch').value = ''; document.getElementById('faqSearch').dispatchEvent(new Event('input'));">
                    <i class="fas fa-times"></i> Clear Search
                </button>
            `;
            document.getElementById('listingsAccordion').parentElement.appendChild(noResultsDiv);
        }
    } else {
        if (noResults) {
            noResults.remove();
        }
    }
});

// Smooth scroll to category sections
document.querySelectorAll('#faqCategories a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Update active state
        document.querySelectorAll('#faqCategories a').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        
        // Scroll to section
        const targetId = this.getAttribute('href').substring(1);
        const target = document.getElementById(targetId);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Highlight active category on scroll
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('section[id]');
    const scrollPosition = window.scrollY + 100;
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        
        if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
            document.querySelectorAll('#faqCategories a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + section.id) {
                    link.classList.add('active');
                }
            });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>