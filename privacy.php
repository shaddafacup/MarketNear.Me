<?php
// privacy.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_once 'includes/header.php';
?>

<div class="py-5" style="background: var(--primary-gradient); color: white;">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Privacy Policy</h1>
        <p class="lead mb-0">How we collect, use, and protect your information</p>
        <small>Last updated: <?php echo date('F j, Y'); ?></small>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">1. Information We Collect</h4>
                        <p><strong>1.1 Personal Information:</strong> When you register, we collect:</p>
                        <ul>
                            <li>Full name</li>
                            <li>Username</li>
                            <li>Email address</li>
                            <li>Phone number (optional)</li>
                            <li>Location (optional)</li>
                        </ul>
                        <p><strong>1.2 Listing Information:</strong> Content you post including descriptions, images, prices, and contact details.</p>
                        <p><strong>1.3 Usage Data:</strong> IP address, browser type, pages visited, time spent, and other analytics.</p>
                        <p><strong>1.4 Communications:</strong> Messages sent through our platform between users.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">2. How We Use Your Information</h4>
                        <p>We use collected information to:</p>
                        <ul>
                            <li>Provide and maintain our services</li>
                            <li>Authenticate users and secure accounts</li>
                            <li>Display listings to relevant users</li>
                            <li>Facilitate communication between users</li>
                            <li>Improve our platform and user experience</li>
                            <li>Send service-related notifications</li>
                            <li>Comply with legal obligations</li>
                            <li>Prevent fraud and abuse</li>
                        </ul>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">3. Data Security</h4>
                        <p>We implement robust security measures:</p>
                        <ul>
                            <li>Passwords are encrypted using bcrypt hashing</li>
                            <li>CSRF protection on all forms</li>
                            <li>XSS prevention through input sanitization</li>
                            <li>SQL injection protection with prepared statements</li>
                            <li>Secure session management</li>
                            <li>Regular security audits</li>
                        </ul>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">4. Data Sharing</h4>
                        <p>We do <strong>not</strong> sell your personal data. We may share information:</p>
                        <ul>
                            <li>With your consent</li>
                            <li>To comply with legal obligations</li>
                            <li>To protect our rights and safety</li>
                            <li>With service providers who assist our operations</li>
                        </ul>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">5. Your Rights</h4>
                        <p>You have the right to:</p>
                        <ul>
                            <li>Access your personal data</li>
                            <li>Correct inaccurate data</li>
                            <li>Delete your account and data</li>
                            <li>Object to data processing</li>
                            <li>Data portability</li>
                        </ul>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">6. Contact Us</h4>
                        <p>For privacy concerns:<br>
                        <strong>Email:</strong> privacy@marketnearme.com<br>
                        <a href="/marketnearme/contact.php">Contact Form</a></p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>