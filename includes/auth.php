<?php
// includes/auth.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class Auth {
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['login_ip']) && 
               $_SESSION['login_ip'] === $_SERVER['REMOTE_ADDR'] &&
               isset($_SESSION['user_agent']) &&
               $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'];
    }
    
    /**
     * Require user to be logged in
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            Security::logSecurityEvent('unauthorized_access', 'Attempt to access protected page');
            
            // Store the requested URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error_message'] = 'Please login to access this page.';
            
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header("Location: /marketnearme/login.php");
            exit();
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
            self::logout();
            $_SESSION['error_message'] = 'Your session has expired. Please login again.';
            
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header("Location: /marketnearme/login.php");
            exit();
        }
        
        // Update last activity time
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::isLoggedIn() && 
               isset($_SESSION['user_role']) && 
               $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Require admin privileges with detailed error messages
     */
    public static function requireAdmin() {
        // First check if user is logged in
        if (!self::isLoggedIn()) {
            Security::logSecurityEvent('admin_access_denied', 'Non-logged-in user attempted to access admin area');
            
            // Store the requested URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error_message'] = 'Please login with an admin account to access the admin panel.';
            
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header("Location: /marketnearme/login.php?redirect=admin");
            exit();
        }
        
        // Then check if user is admin
        if (!self::isAdmin()) {
            $username = $_SESSION['username'] ?? 'Unknown';
            Security::logSecurityEvent(
                'admin_access_denied', 
                "User '{$username}' (ID: {$_SESSION['user_id']}) attempted to access admin area without admin privileges"
            );
            
            // Set detailed error message
            $_SESSION['error_message'] = sprintf(
                'Access denied. Your account (%s) does not have administrator privileges. ' .
                'If you believe this is an error, please contact the site administrator.',
                htmlspecialchars($username)
            );
            
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            header("Location: /marketnearme/index.php");
            exit();
        }
        
        // Update last activity time
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Login user
     */
    public static function login($email, $password) {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        // Rate limiting check
        if (!Security::rateLimit('login_attempt', 5, 300)) {
            Security::logSecurityEvent('rate_limit_exceeded', 'Too many login attempts for: ' . $email);
            return [
                'success' => false, 
                'message' => 'Too many login attempts. Please wait 5 minutes and try again.'
            ];
        }
        
        // Find user by email or username
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
        $stmt->execute([
            ':email' => $email,
            ':username' => $email
        ]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if account is suspended
            if ($user['status'] === 'suspended') {
                Security::logSecurityEvent('suspended_login', 'Suspended account attempted login', $user['id']);
                return [
                    'success' => false, 
                    'message' => 'Your account has been suspended. Please contact support for assistance.'
                ];
            }
            
            // Check if email is verified (optional - can be configured)
            // if (!$user['email_verified']) {
            //     return [
            //         'success' => false, 
            //         'message' => 'Please verify your email address before logging in.'
            //     ];
            // }
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['login_time'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            
            // Update last login timestamp
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $updateStmt->execute([':id' => $user['id']]);
            
            Security::logSecurityEvent('successful_login', 'User logged in successfully', $user['id']);
            
            // Set welcome message
            $_SESSION['welcome_message'] = 'Welcome back, ' . htmlspecialchars($user['full_name'] ?: $user['username']) . '!';
            
            // Check if there's a redirect URL
            $redirect_url = $_SESSION['redirect_after_login'] ?? '/marketnearme/index.php';
            unset($_SESSION['redirect_after_login']);
            
            return [
                'success' => true, 
                'user' => $user,
                'redirect' => $redirect_url
            ];
        }
        
        // Log failed attempt
        Security::logSecurityEvent('failed_login', "Failed login attempt for: $email");
        
        return [
            'success' => false, 
            'message' => 'Invalid email/username or password. Please try again.'
        ];
    }
    
    /**
     * Register new user
     */
    public static function register($username, $email, $password, $full_name, $phone, $location) {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        // Validate inputs
        $errors = [];
        
        // Username validation
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors[] = "Username must be 3-20 characters and contain only letters, numbers, and underscores.";
        } elseif (in_array(strtolower($username), ['admin', 'administrator', 'root', 'system', 'moderator'])) {
            $errors[] = "This username is reserved. Please choose a different one.";
        }
        
        // Email validation
        if (empty($email)) {
            $errors[] = "Email address is required.";
        } elseif (!Security::validateEmail($email)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        // Password validation
        if (empty($password)) {
            $errors[] = "Password is required.";
        } else {
            $password_errors = Security::validatePasswordStrength($password);
            if (!empty($password_errors)) {
                $errors = array_merge($errors, $password_errors);
            }
        }
        
        // Full name validation
        if (empty($full_name)) {
            $errors[] = "Full name is required.";
        } elseif (strlen($full_name) < 2) {
            $errors[] = "Full name must be at least 2 characters.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['This username is already taken. Please choose another.']];
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['An account with this email already exists. Please login instead.']];
        }
        
        // Create user
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $verification_token = bin2hex(random_bytes(32));
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, full_name, phone, location, verification_token) 
                VALUES (:username, :email, :password, :full_name, :phone, :location, :verification_token)
            ");
            
            $result = $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password,
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':location' => $location,
                ':verification_token' => $verification_token
            ]);
            
            if ($result) {
                $user_id = $conn->lastInsertId();
                Security::logSecurityEvent('user_registered', "New user registered: $username (ID: $user_id)", $user_id);
                
                return [
                    'success' => true, 
                    'message' => 'Registration successful! You can now login to your account.',
                    'user_id' => $user_id
                ];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed due to a system error. Please try again later.']];
        }
        
        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Log the logout event
        if (isset($_SESSION['user_id'])) {
            Security::logSecurityEvent('user_logout', 'User logged out', $_SESSION['user_id']);
        }
        
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    /**
     * Get current user details
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        
        return $stmt->fetch();
    }
    
    /**
     * Check if user owns a listing
     */
    public static function ownsListing($listing_id) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT id FROM listings WHERE id = :listing_id AND user_id = :user_id");
        $stmt->execute([
            ':listing_id' => $listing_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        return $stmt->fetch() ? true : false;
    }
}
?>