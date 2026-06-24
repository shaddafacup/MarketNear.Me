<?php
// login.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Process login BEFORE any HTML output
$error = '';
$email_value = '';

// Redirect if already logged in
if (Auth::isLoggedIn()) {
    header("Location: /marketnearme/index.php");
    exit();
}

// Check for registration success message
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email_value = Security::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email_value) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $result = Auth::login($email_value, $password);
            
            if ($result['success']) {
                // Set welcome message
                $_SESSION['welcome_message'] = 'Welcome back, ' . $result['user']['username'] . '!';
                header("Location: /marketnearme/index.php");
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Now include header (after all processing and potential redirects)
require_once 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2 class="text-center">Welcome Back</h2>
        <p class="text-center text-muted">Login to your MarketNearMe account</p>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
            
            <div class="mb-3">
                <label for="email" class="form-label">Email or Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="text" class="form-control" id="email" name="email" 
                           required placeholder="Enter your email or username"
                           value="<?php echo htmlspecialchars($email_value); ?>">
                    <div class="invalid-feedback">Please enter your email or username.</div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" 
                           required placeholder="Enter your password">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            
            <div class="text-center">
                <a href="/marketnearme/forgot-password.php" class="text-muted">Forgot Password?</a>
            </div>
        </form>
        
        <hr>
        
        <div class="text-center">
            <p>Don't have an account? <a href="/marketnearme/register.php">Sign Up</a></p>
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

// Form validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    let isValid = true;
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    
    if (!email.value.trim()) {
        email.classList.add('is-invalid');
        isValid = false;
    } else {
        email.classList.remove('is-invalid');
    }
    
    if (!password.value) {
        password.classList.add('is-invalid');
        isValid = false;
    } else {
        password.classList.remove('is-invalid');
    }
    
    if (!isValid) {
        e.preventDefault();
    }
});

// Clear validation styling on input
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('input', function() {
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>