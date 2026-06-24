<?php
// includes/functions.php
// Remove the session_start() from here - it will be managed by header.php

// Security functions
class Security {
    
    // CSRF Token generation
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // CSRF Token validation
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            Security::logSecurityEvent('csrf_violation', 'CSRF token validation failed');
            return false;
        }
        return true;
    }
    
    // Input sanitization
    public static function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // XSS prevention
    public static function xss_clean($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    // Validate email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Password strength validation
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    // Secure file upload validation
    public static function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload failed";
            return $errors;
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowed_types);
        }
        
        if ($file['size'] > $max_size) {
            $errors[] = "File size too large. Maximum size: " . ($max_size / 1048576) . "MB";
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        
        if (!isset($allowed_mimes[$file_extension]) || $mime_type !== $allowed_mimes[$file_extension]) {
            $errors[] = "Invalid file content";
        }
        
        return $errors;
    }
    
    // Rate limiting
    public static function rateLimit($key, $max_attempts = 5, $time_window = 300) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $current_time = time();
        $time_threshold = $current_time - $time_window;
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM security_logs 
            WHERE action = :action 
            AND ip_address = :ip 
            AND created_at > FROM_UNIXTIME(:time_threshold)
        ");
        
        $stmt->execute([
            ':action' => $key,
            ':ip' => $ip,
            ':time_threshold' => $time_threshold
        ]);
        
        $result = $stmt->fetch();
        
        return $result['attempt_count'] < $max_attempts;
    }
    
    // Log security events
    public static function logSecurityEvent($action, $details = '', $user_id = null) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $stmt = $conn->prepare("
                INSERT INTO security_logs (user_id, action, ip_address, user_agent, details) 
                VALUES (:user_id, :action, :ip_address, :user_agent, :details)
            ");
            
            $stmt->execute([
                ':user_id' => $user_id ?? ($_SESSION['user_id'] ?? null),
                ':action' => $action,
                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                ':details' => $details
            ]);
        } catch (Exception $e) {
            error_log("Security log error: " . $e->getMessage());
        }
    }
    
    // Generate secure slug
    public static function generateSlug($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }
}

// Database helper functions
class DBHelper {
    
    public static function getCategories($parent_id = null) {
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($parent_id === null) {
            $stmt = $conn->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM listings WHERE category_id = c.id AND status = 'active') as listing_count 
                FROM categories c 
                WHERE c.status = 'active' 
                ORDER BY c.name
            ");
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("
                SELECT c.*, 
                       (SELECT COUNT(*) FROM listings WHERE category_id = c.id AND status = 'active') as listing_count 
                FROM categories c 
                WHERE c.parent_id = :parent_id AND c.status = 'active' 
                ORDER BY c.name
            ");
            $stmt->execute([':parent_id' => $parent_id]);
        }
        
        return $stmt->fetchAll();
    }
    
    // Add this inside the DBHelper class
public static function getRecentListings($limit = 12) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT l.*, 
               u.username, 
               u.location as user_location,
               c.name as category_name,
               c.slug as category_slug,
               cur.code as currency_code,
               cur.symbol as currency_symbol,
               cur.symbol_position as currency_symbol_position,
               cur.decimal_places as currency_decimal_places,
               cur.decimal_separator as currency_decimal_separator,
               cur.thousands_separator as currency_thousands_separator,
               (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM listings l
        JOIN users u ON l.user_id = u.id
        JOIN categories c ON l.category_id = c.id
        LEFT JOIN currencies cur ON l.currency_id = cur.id
        WHERE l.status = 'active'
        ORDER BY l.created_at DESC
        LIMIT :limit
    ");
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}
    
    public static function getFeaturedListings($limit = 6) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT l.*, 
                   u.username, 
                   u.location as user_location,
                   c.name as category_name,
                   c.slug as category_slug,
                   (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM listings l
            JOIN users u ON l.user_id = u.id
            JOIN categories c ON l.category_id = c.id
            WHERE l.status = 'active' AND l.is_featured = 1
            ORDER BY l.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public static function searchListings($query, $category_id = null, $min_price = null, $max_price = null, $location = null) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "
            SELECT l.*, 
                   u.username, 
                   c.name as category_name,
                   c.slug as category_slug,
                   (SELECT image_path FROM listing_images WHERE listing_id = l.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM listings l
            JOIN users u ON l.user_id = u.id
            JOIN categories c ON l.category_id = c.id
            WHERE l.status = 'active'
        ";
        
        $params = [];
        
        if ($query) {
            $sql .= " AND MATCH(l.title, l.description) AGAINST(:query IN BOOLEAN MODE)";
            $params[':query'] = $query;
        }
        
        if ($category_id) {
            $sql .= " AND l.category_id = :category_id";
            $params[':category_id'] = $category_id;
        }
        
        if ($min_price !== null) {
            $sql .= " AND l.price >= :min_price";
            $params[':min_price'] = $min_price;
        }
        
        if ($max_price !== null) {
            $sql .= " AND l.price <= :max_price";
            $params[':max_price'] = $max_price;
        }
        
        if ($location) {
            $sql .= " AND l.location LIKE :location";
            $params[':location'] = "%$location%";
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}
?>