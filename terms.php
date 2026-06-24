<?php
// terms.php
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
        <h1 class="display-4 fw-bold mb-3">Terms and Conditions</h1>
        <p class="lead mb-0">Please read these terms carefully before using MarketNearMe</p>
        <small>Last updated: <?php echo date('F j, Y'); ?></small>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">1. Acceptance of Terms</h4>
                        <p>By accessing and using MarketNearMe ("the Platform"), you agree to be bound by these Terms and Conditions. If you do not agree with any part of these terms, you must not use our services.</p>
                        <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting. Your continued use of the Platform after modifications constitutes acceptance of the updated terms.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">2. User Accounts</h4>
                        <p><strong>2.1 Registration:</strong> To access certain features, you must create an account. You agree to provide accurate, current, and complete information during registration.</p>
                        <p><strong>2.2 Account Security:</strong> You are responsible for maintaining the confidentiality of your password and account. Notify us immediately of any unauthorized use of your account.</p>
                        <p><strong>2.3 Age Requirement:</strong> You must be at least 18 years old to use this Platform. By registering, you confirm that you meet this age requirement.</p>
                        <p><strong>2.4 One Account:</strong> Each user is limited to one account. Multiple accounts are not permitted.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">3. Listing Rules</h4>
                        <p><strong>3.1 Accurate Information:</strong> All listings must contain accurate and truthful information. Misleading or false descriptions are prohibited.</p>
                        <p><strong>3.2 Prohibited Items:</strong> The following items are strictly prohibited on MarketNearMe:</p>
                        <ul>
                            <li>Illegal drugs and controlled substances</li>
                            <li>Firearms, weapons, and ammunition</li>
                            <li>Stolen or counterfeit goods</li>
                            <li>Adult content or services</li>
                            <li>Hazardous materials</li>
                            <li>Live animals (except in designated categories)</li>
                            <li>Human body parts or fluids</li>
                            <li>Gambling or lottery items</li>
                            <li>Any item or service that violates local, national, or international laws</li>
                        </ul>
                        <p><strong>3.3 Image Guidelines:</strong> Images must be of the actual item being sold. Stock photos or misleading images are not allowed. Images must not contain contact information overlaid on them.</p>
                        <p><strong>3.4 Pricing:</strong> All prices must be clearly stated. Price manipulation or bait-and-switch tactics are prohibited.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">4. User Conduct</h4>
                        <p>Users agree to:</p>
                        <ul>
                            <li>Treat all members with respect and courtesy</li>
                            <li>Not engage in harassment, hate speech, or abusive behavior</li>
                            <li>Not spam or post duplicate listings</li>
                            <li>Not use the Platform for any illegal purpose</li>
                            <li>Not attempt to manipulate or exploit the Platform's features</li>
                            <li>Not impersonate other users or provide false information</li>
                            <li>Not collect or harvest user information without consent</li>
                        </ul>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">5. Transactions and Payments</h4>
                        <p><strong>5.1 Platform Role:</strong> MarketNearMe is a listing and communication platform only. We do not process payments, hold funds, or guarantee transactions between users.</p>
                        <p><strong>5.2 Buyer-Seller Arrangements:</strong> All transaction terms, including payment methods, delivery, and returns, are agreed upon directly between buyers and sellers.</p>
                        <p><strong>5.3 No Endorsement:</strong> We do not endorse, guarantee, or assume responsibility for any products or services listed on our Platform.</p>
                        <p><strong>5.4 Disputes:</strong> Users are responsible for resolving disputes among themselves. We may assist in dispute resolution but are not obligated to do so.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">6. Intellectual Property</h4>
                        <p><strong>6.1 Platform Content:</strong> All content, design, and functionality of the Platform are owned by MarketNearMe and protected by intellectual property laws.</p>
                        <p><strong>6.2 User Content:</strong> By posting content on the Platform, you grant us a non-exclusive license to display, distribute, and promote your content in connection with our services.</p>
                        <p><strong>6.3 Copyright Infringement:</strong> We respect intellectual property rights. Report copyright violations to us promptly.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">7. Privacy and Data</h4>
                        <p><strong>7.1 Privacy Policy:</strong> Your use of the Platform is also governed by our <a href="/marketnearme/privacy.php">Privacy Policy</a>.</p>
                        <p><strong>7.2 Data Collection:</strong> We collect and process personal data as described in our Privacy Policy.</p>
                        <p><strong>7.3 Communications:</strong> By registering, you consent to receive communications from us regarding your account and our services.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">8. Termination</h4>
                        <p><strong>8.1 By User:</strong> You may terminate your account at any time by contacting us.</p>
                        <p><strong>8.2 By Platform:</strong> We reserve the right to suspend or terminate your account at our discretion, without prior notice, for violation of these terms or any reason we deem appropriate.</p>
                        <p><strong>8.3 Effect of Termination:</strong> Upon termination, your right to use the Platform ceases immediately. We may retain certain information as required by law.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">9. Limitation of Liability</h4>
                        <p><strong>9.1 No Warranty:</strong> The Platform is provided "as is" without warranties of any kind, express or implied.</p>
                        <p><strong>9.2 Liability Limit:</strong> MarketNearMe shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the Platform.</p>
                        <p><strong>9.3 Transaction Risk:</strong> You acknowledge that all transactions are at your own risk. We are not responsible for the actions of other users.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">10. Governing Law</h4>
                        <p>These terms shall be governed by and construed in accordance with applicable laws. Any disputes arising from these terms shall be resolved through arbitration or in courts of competent jurisdiction.</p>
                    </section>
                    
                    <section class="mb-4">
                        <h4 class="mb-3" style="color: var(--primary-color);">11. Contact Information</h4>
                        <p>For questions about these Terms and Conditions, please contact us:</p>
                        <ul>
                            <li><strong>Email:</strong> legal@marketnearme.com</li>
                            <li><strong>Address:</strong> 123 Marketplace Street, Business District</li>
                            <li><strong>Contact Form:</strong> <a href="/marketnearme/contact.php">Contact Us</a></li>
                        </ul>
                    </section>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="mb-3">Ready to join MarketNearMe?</p>
                        <a href="/marketnearme/register.php" class="btn btn-lg" style="background: var(--primary-gradient); color: white;">
                            <i class="fas fa-user-plus"></i> Create Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>