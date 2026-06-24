<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header("Location: /marketnearme/index.php");
    exit();
}

$errors = [];
$success = '';
$form_data = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'location' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $form_data['username'] = Security::sanitize($_POST['username'] ?? '');
        $form_data['email'] = Security::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $form_data['full_name'] = Security::sanitize($_POST['full_name'] ?? '');
        $form_data['phone'] = Security::sanitize($_POST['phone'] ?? '');
        $form_data['location'] = Security::sanitize($_POST['location'] ?? '');
        $terms_accepted = isset($_POST['terms']) && $_POST['terms'] === '1';
        
        // Validate terms acceptance
        if (!$terms_accepted) {
            $errors[] = 'You must agree to the Terms and Conditions to create an account.';
        }
        
        // Validate passwords match
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($errors)) {
            $result = Auth::register(
                $form_data['username'], 
                $form_data['email'], 
                $password, 
                $form_data['full_name'], 
                $form_data['phone'], 
                $form_data['location']
            );
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Registration successful! Please login to your account.';
                header("Location: /marketnearme/login.php");
                exit();
            } else {
                $errors = $result['errors'];
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="form-container">
                <div class="text-center mb-4">
                    <i class="fas fa-store-alt fa-3x mb-3" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                    <h2>Create Your Account</h2>
                    <p class="text-muted">Join MarketNearMe and start trading in your community</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       required value="<?php echo htmlspecialchars($form_data['full_name']); ?>"
                                       placeholder="John Doe">
                            </div>
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">@</span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       required pattern="[a-zA-Z0-9_]{3,20}" 
                                       value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                       placeholder="john_doe">
                            </div>
                            <div class="form-text">3-20 characters, letters, numbers, and underscores only.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   required value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                   placeholder="john@example.com">
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{8,}$"
                                       placeholder="Create a strong password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-requirements mt-2" id="passwordRequirements">
                                <small class="text-muted">Password must contain:</small>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <span class="badge bg-light text-dark req-length">8+ characters</span>
                                    <span class="badge bg-light text-dark req-uppercase">Uppercase</span>
                                    <span class="badge bg-light text-dark req-lowercase">Lowercase</span>
                                    <span class="badge bg-light text-dark req-number">Number</span>
                                    <span class="badge bg-light text-dark req-special">Special char</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       required placeholder="Confirm your password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordMatchError">Passwords do not match.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($form_data['phone']); ?>"
                                       placeholder="+1234567890">
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($form_data['location']); ?>"
                                       placeholder="City, State/Country">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Terms and Conditions Checkbox -->
                    <div class="mb-4 p-3 rounded-3" style="background: var(--gray-100);">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" required>
                            <label class="form-check-label" for="terms">
                                <strong>I agree to the Terms and Conditions <span class="text-danger">*</span></strong>
                            </label>
                            <div class="invalid-feedback">You must agree to the Terms and Conditions to create an account.</div>
                        </div>
                        <div class="mt-2 small text-muted">
                            <p class="mb-1">By checking this box, you confirm that you:</p>
                            <ul class="mb-1">
                                <li>Have read and agree to our <a href="/marketnearme/terms.php" target="_blank" class="text-primary"><strong>Terms and Conditions</strong></a></li>
                                <li>Have read and understand our <a href="/marketnearme/privacy.php" target="_blank" class="text-primary"><strong>Privacy Policy</strong></a></li>
                                <li>Are at least 18 years of age</li>
                                <li>Understand that MarketNearMe is a listing platform only and does not process payments</li>
                            </ul>
                            <a href="/marketnearme/terms.php" target="_blank" class="text-primary">
                                <i class="fas fa-external-link-alt"></i> Read full Terms and Conditions
                            </a>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-lg w-100 mb-3" style="background: var(--primary-gradient); color: white;">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="/marketnearme/login.php" class="fw-bold">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Real-time password validation
const passwordInput = document.getElementById('password');
const requirements = {
    length: document.querySelector('.req-length'),
    uppercase: document.querySelector('.req-uppercase'),
    lowercase: document.querySelector('.req-lowercase'),
    number: document.querySelector('.req-number'),
    special: document.querySelector('.req-special')
};

passwordInput.addEventListener('input', function() {
    const password = this.value;
    
    // Check each requirement
    if (password.length >= 8) {
        requirements.length.className = 'badge bg-success text-white req-length';
    } else {
        requirements.length.className = 'badge bg-light text-dark req-length';
    }
    
    if (/[A-Z]/.test(password)) {
        requirements.uppercase.className = 'badge bg-success text-white req-uppercase';
    } else {
        requirements.uppercase.className = 'badge bg-light text-dark req-uppercase';
    }
    
    if (/[a-z]/.test(password)) {
        requirements.lowercase.className = 'badge bg-success text-white req-lowercase';
    } else {
        requirements.lowercase.className = 'badge bg-light text-dark req-lowercase';
    }
    
    if (/[0-9]/.test(password)) {
        requirements.number.className = 'badge bg-success text-white req-number';
    } else {
        requirements.number.className = 'badge bg-light text-dark req-number';
    }
    
    if (/[!@#$%^&*()\-_=+{};:,<.>]/.test(password)) {
        requirements.special.className = 'badge bg-success text-white req-special';
    } else {
        requirements.special.className = 'badge bg-light text-dark req-special';
    }
});

// Password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    const errorDiv = document.getElementById('passwordMatchError');
    
    if (confirmPassword && password !== confirmPassword) {
        this.classList.add('is-invalid');
        errorDiv.style.display = 'block';
    } else {
        this.classList.remove('is-invalid');
        errorDiv.style.display = 'none';
    }
});

// Terms checkbox validation
document.getElementById('terms').addEventListener('change', function() {
    if (this.checked) {
        this.classList.remove('is-invalid');
    }
});

// Form submission validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    // Check terms
    const terms = document.getElementById('terms');
    if (!terms.checked) {
        terms.classList.add('is-invalid');
        isValid = false;
    }
    
    // Check required fields
    const requiredFields = this.querySelectorAll('[required]:not([type="checkbox"])');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        const firstError = this.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>