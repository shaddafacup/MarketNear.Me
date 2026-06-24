<?php
// contact.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/currency.php';

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';
$form_data = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

// Pre-fill form if user is logged in
if (Auth::isLoggedIn()) {
    $form_data['name'] = $_SESSION['user_full_name'] ?? '';
    $form_data['email'] = $_SESSION['user_email'] ?? '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $form_data['name'] = Security::sanitize($_POST['name'] ?? '');
        $form_data['email'] = Security::sanitize($_POST['email'] ?? '');
        $form_data['subject'] = Security::sanitize($_POST['subject'] ?? '');
        $form_data['message'] = $_POST['message'] ?? '';
        
        // Validate inputs
        $errors = [];
        
        if (empty($form_data['name'])) {
            $errors[] = 'Please enter your name.';
        } elseif (strlen($form_data['name']) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        
        if (empty($form_data['email'])) {
            $errors[] = 'Please enter your email address.';
        } elseif (!Security::validateEmail($form_data['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($form_data['subject'])) {
            $errors[] = 'Please select a subject.';
        }
        
        if (empty($form_data['message'])) {
            $errors[] = 'Please enter your message.';
        } elseif (strlen($form_data['message']) < 10) {
            $errors[] = 'Message must be at least 10 characters.';
        }
        
        if (empty($errors)) {
            // Save contact message to database
            try {
                // Create contact_messages table if it doesn't exist
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS contact_messages (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(100) NOT NULL,
                        email VARCHAR(100) NOT NULL,
                        subject VARCHAR(200) NOT NULL,
                        message TEXT NOT NULL,
                        user_id INT NULL,
                        ip_address VARCHAR(45),
                        is_read TINYINT(1) DEFAULT 0,
                        status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                
                $stmt = $conn->prepare("
                    INSERT INTO contact_messages (name, email, subject, message, user_id, ip_address) 
                    VALUES (:name, :email, :subject, :message, :user_id, :ip_address)
                ");
                
                $stmt->execute([
                    ':name' => $form_data['name'],
                    ':email' => $form_data['email'],
                    ':subject' => $form_data['subject'],
                    ':message' => Security::sanitize($form_data['message']),
                    ':user_id' => $_SESSION['user_id'] ?? null,
                    ':ip_address' => $_SERVER['REMOTE_ADDR']
                ]);
                
                Security::logSecurityEvent('contact_form_submitted', "Contact form submitted by: {$form_data['name']}");
                
                $success = 'Thank you for your message! We will get back to you within 24 hours.';
                
                // Clear form data on success
                $form_data = [
                    'name' => Auth::isLoggedIn() ? ($_SESSION['user_full_name'] ?? '') : '',
                    'email' => Auth::isLoggedIn() ? ($_SESSION['user_email'] ?? '') : '',
                    'subject' => '',
                    'message' => ''
                ];
                
            } catch (Exception $e) {
                error_log("Contact form error: " . $e->getMessage());
                $error = 'Failed to send message. Please try again later.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

require_once 'includes/header.php';
?>

<!-- Hero Banner -->
<div class="py-5" style="background: var(--primary-gradient); color: white;">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Contact Us</h1>
        <p class="lead mb-0">We'd love to hear from you. Get in touch with our team.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Contact Form -->
        <div class="col-lg-7">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-paper-plane me-2" style="color: var(--primary-color);"></i>
                        Send us a Message
                    </h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle fa-lg me-2"></i>
                        <strong>Message Sent!</strong> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle fa-lg me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="contactForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    Your Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           required minlength="2"
                                           value="<?php echo htmlspecialchars($form_data['name']); ?>"
                                           placeholder="John Doe">
                                    <div class="invalid-feedback">Please enter your name (at least 2 characters).</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           required
                                           value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                           placeholder="john@example.com">
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">
                                Subject <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-tag"></i>
                                </span>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="">Select a topic...</option>
                                    <option value="General Inquiry" <?php echo $form_data['subject'] === 'General Inquiry' ? 'selected' : ''; ?>>
                                        General Inquiry
                                    </option>
                                    <option value="Technical Support" <?php echo $form_data['subject'] === 'Technical Support' ? 'selected' : ''; ?>>
                                        Technical Support
                                    </option>
                                    <option value="Account Issues" <?php echo $form_data['subject'] === 'Account Issues' ? 'selected' : ''; ?>>
                                        Account Issues
                                    </option>
                                    <option value="Report a Problem" <?php echo $form_data['subject'] === 'Report a Problem' ? 'selected' : ''; ?>>
                                        Report a Problem
                                    </option>
                                    <option value="Listing Help" <?php echo $form_data['subject'] === 'Listing Help' ? 'selected' : ''; ?>>
                                        Listing Help
                                    </option>
                                    <option value="Suggestion" <?php echo $form_data['subject'] === 'Suggestion' ? 'selected' : ''; ?>>
                                        Suggestion / Feedback
                                    </option>
                                    <option value="Partnership" <?php echo $form_data['subject'] === 'Partnership' ? 'selected' : ''; ?>>
                                        Partnership / Advertising
                                    </option>
                                    <option value="Other" <?php echo $form_data['subject'] === 'Other' ? 'selected' : ''; ?>>
                                        Other
                                    </option>
                                </select>
                                <div class="invalid-feedback">Please select a subject.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">
                                Your Message <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="message" name="message" 
                                      rows="6" required minlength="10"
                                      placeholder="How can we help you? Please provide as much detail as possible..."><?php echo htmlspecialchars($form_data['message']); ?></textarea>
                            <div class="invalid-feedback">Please enter your message (at least 10 characters).</div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">
                                    <span id="charCount">0</span> characters
                                </small>
                                <small class="text-muted">
                                    Min: 10 | Max: 2000
                                </small>
                            </div>
                        </div>
                        
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="privacy" name="privacy" required>
                            <label class="form-check-label" for="privacy">
                                I agree to the <a href="/marketnearme/privacy.php" target="_blank">Privacy Policy</a> 
                                and consent to being contacted regarding my inquiry.
                            </label>
                            <div class="invalid-feedback">You must agree to the privacy policy.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-lg w-100" style="background: var(--primary-gradient); color: white;">
                            <i class="fas fa-paper-plane me-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Contact Information Sidebar -->
        <div class="col-lg-5">
            <!-- Contact Info Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2" style="color: var(--primary-color);"></i>
                        Contact Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background: var(--primary-gradient); color: white;">
                                <i class="fas fa-envelope"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Email Us</h6>
                            <p class="text-muted mb-0">support@marketnearme.com</p>
                            <small class="text-muted">We respond within 24 hours</small>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background: var(--secondary-gradient); color: white;">
                                <i class="fas fa-phone"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Call Us</h6>
                            <p class="text-muted mb-0">+1 (234) 567-8900</p>
                            <small class="text-muted">Mon-Fri, 9am-6pm</small>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background: var(--accent-gradient); color: white;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Visit Us</h6>
                            <p class="text-muted mb-0">123 Marketplace Street</p>
                            <small class="text-muted">Business District, City</small>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background: var(--info-color); color: white;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Business Hours</h6>
                            <p class="text-muted mb-0">Monday - Friday: 9:00 AM - 6:00 PM</p>
                            <small class="text-muted">Saturday: 10:00 AM - 2:00 PM</small>
                            <br>
                            <small class="text-muted">Sunday: Closed</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-link me-2" style="color: var(--secondary-color);"></i>
                        Quick Links
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="/marketnearme/about.php" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-info-circle me-2" style="color: var(--primary-color);"></i>
                            About MarketNearMe
                        </a>
                        <a href="/marketnearme/faq.php" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-question-circle me-2" style="color: var(--warning-color);"></i>
                            Frequently Asked Questions
                        </a>
                        <a href="/marketnearme/privacy.php" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-shield-alt me-2" style="color: var(--info-color);"></i>
                            Privacy Policy
                        </a>
                        <a href="/marketnearme/terms.php" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-file-contract me-2" style="color: var(--accent-color);"></i>
                            Terms of Service
                        </a>
                        <a href="/marketnearme/search.php" class="list-group-item list-group-item-action border-0">
                            <i class="fas fa-search me-2" style="color: var(--success-color);"></i>
                            Browse Listings
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Social Media Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-share-alt me-2" style="color: var(--accent-color);"></i>
                        Follow Us
                    </h5>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-3">Stay connected with us on social media</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center" 
                           style="width: 50px; height: 50px;" title="Facebook">
                            <i class="fab fa-facebook-f fa-lg"></i>
                        </a>
                        <a href="#" class="btn btn-info text-white rounded-circle d-flex align-items-center justify-content-center" 
                           style="width: 50px; height: 50px;" title="Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <a href="#" class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center" 
                           style="width: 50px; height: 50px;" title="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center" 
                           style="width: 50px; height: 50px; background: #0a66c2; border-color: #0a66c2;" title="LinkedIn">
                            <i class="fab fa-linkedin-in fa-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Map Section -->
<div class="py-5" style="background: var(--gray-100);">
    <div class="container">
        <h2 class="text-center mb-4">Our Location</h2>
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="ratio ratio-21x9" style="min-height: 300px;">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71312937933039!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a23e28c1191%3A0x49f75d3281df052a!2s150%0APark%20Row!5e0!3m2!1sen!2sus!4v1644262070686!5m2!1sen!2sus"
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Support Banner -->
<div class="py-5" style="background: var(--primary-gradient); color: white;">
    <div class="container text-center">
        <h2 class="mb-3">Need Immediate Assistance?</h2>
        <p class="lead mb-4">Our support team is ready to help you with any urgent issues.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="tel:+12345678900" class="btn btn-light btn-lg">
                <i class="fas fa-phone-alt"></i> Call Now
            </a>
            <a href="mailto:support@marketnearme.com" class="btn btn-outline-light btn-lg">
                <i class="fas fa-envelope"></i> Email Support
            </a>
        </div>
    </div>
</div>

<script>
// Character counter
const messageTextarea = document.getElementById('message');
const charCount = document.getElementById('charCount');

messageTextarea.addEventListener('input', function() {
    const count = this.value.length;
    charCount.textContent = count;
    
    if (count > 1900) {
        charCount.style.color = 'var(--danger-color)';
        charCount.style.fontWeight = 'bold';
    } else if (count > 1800) {
        charCount.style.color = 'var(--warning-color)';
        charCount.style.fontWeight = 'normal';
    } else {
        charCount.style.color = '';
        charCount.style.fontWeight = 'normal';
    }
});

// Initialize character count
charCount.textContent = messageTextarea.value.length;

// Form validation
document.getElementById('contactForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Name validation
    const name = document.getElementById('name');
    if (!name.value.trim() || name.value.trim().length < 2) {
        name.classList.add('is-invalid');
        isValid = false;
    } else {
        name.classList.remove('is-invalid');
    }
    
    // Email validation
    const email = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email.value.trim() || !emailRegex.test(email.value)) {
        email.classList.add('is-invalid');
        isValid = false;
    } else {
        email.classList.remove('is-invalid');
    }
    
    // Subject validation
    const subject = document.getElementById('subject');
    if (!subject.value) {
        subject.classList.add('is-invalid');
        isValid = false;
    } else {
        subject.classList.remove('is-invalid');
    }
    
    // Message validation
    const message = document.getElementById('message');
    if (!message.value.trim() || message.value.trim().length < 10) {
        message.classList.add('is-invalid');
        isValid = false;
    } else {
        message.classList.remove('is-invalid');
    }
    
    // Privacy checkbox
    const privacy = document.getElementById('privacy');
    if (!privacy.checked) {
        privacy.classList.add('is-invalid');
        isValid = false;
    } else {
        privacy.classList.remove('is-invalid');
    }
    
    if (!isValid) {
        e.preventDefault();
        // Scroll to first error
        const firstError = this.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }
});

// Real-time validation clearing
document.querySelectorAll('#contactForm input, #contactForm select, #contactForm textarea').forEach(element => {
    element.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
        }
    });
    
    element.addEventListener('change', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
        }
    });
});

// Auto-resize textarea
messageTextarea.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 300) + 'px';
});
</script>

<?php require_once 'includes/footer.php'; ?>