<?php
/**
 * Security Functions
 * XSS, CSRF, and input validation helpers
 * 
 * @package Alokpath\Helpers
 */

/**
 * Prevent XSS attacks by sanitizing output
 * 
 * @param string $string
 * @return string
 */
function xss_clean($string) {
    if (XSS_PROTECTION) {
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        $string = strip_tags($string);
        $string = trim($string);
    }
    return $string;
}

/**
 * Validate email
 * 
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate required fields
 * 
 * @param array $data
 * @param array $required_fields
 * @return array
 */
function validateRequired($data, $required_fields) {
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' প্রয়োজন';
        }
    }
    
    return $errors;
}

/**
 * Validate string length
 * 
 * @param string $string
 * @param int $min
 * @param int $max
 * @return bool
 */
function validateLength($string, $min = 0, $max = 255) {
    $length = mb_strlen($string, 'UTF-8');
    return $length >= $min && $length <= $max;
}

/**
 * Prevent SQL injection - already handled by PDO prepared statements
 * This is an additional layer for legacy code
 * 
 * @param string $value
 * @return string
 */
function preventSQLInjection($value) {
    $search = [
        '\\', "\0", "\n", "\r", "\x1a", "'", '"',
        'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP',
        'UNION', 'EXEC', '--', ';', '/*', '*/',
    ];
    
    $replace = [
        '\\\\', '', '', '', '', '', '',
        '', '', '', '', '', '', '', '', ''
    ];
    
    return str_ireplace($search, $replace, $value);
}

/**
 * Generate random string
 * 
 * @param int $length
 * @return string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password
 * 
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    if (HASH_ALGORITHM === 'md5') {
        return md5($password);
    }
    // For future bcrypt upgrade
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 * 
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    if (HASH_ALGORITHM === 'md5') {
        return md5($password) === $hash;
    }
    // For future bcrypt upgrade
    return password_verify($password, $hash);
}

/**
 * Rate limiter (simple file-based)
 * 
 * @param string $key
 * @param int $max_attempts
 * @param int $time_window
 * @return bool
 */
function checkRateLimit($key, $max_attempts = 5, $time_window = 300) {
    $cache_file = BASE_PATH . '/cache/ratelimit_' . md5($key) . '.cache';
    
    if (!file_exists($cache_file)) {
        file_put_contents($cache_file, json_encode(['attempts' => 1, 'timestamp' => time()]));
        return true;
    }
    
    $data = json_decode(file_get_contents($cache_file), true);
    
    if (time() - $data['timestamp'] > $time_window) {
        file_put_contents($cache_file, json_encode(['attempts' => 1, 'timestamp' => time()]));
        return true;
    }
    
    if ($data['attempts'] >= $max_attempts) {
        return false;
    }
    
    $data['attempts']++;
    file_put_contents($cache_file, json_encode($data));
    return true;
}

/**
 * Clear rate limit
 * 
 * @param string $key
 * @return void
 */
function clearRateLimit($key) {
    $cache_file = BASE_PATH . '/cache/ratelimit_' . md5($key) . '.cache';
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }
}

/**
 * Secure headers
 */
function setSecureHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME-type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");
}

/**
 * Validate CSRF token from request
 * 
 * @return bool
 */
function validateCSRFRequest() {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_GET[CSRF_TOKEN_NAME] ?? '';
    return verifyCSRFToken($token);
}

/**
 * Require CSRF validation
 */
function requireCSRF() {
    if (!validateCSRFRequest()) {
        setFlash('error', 'নিরাপত্তা যাচাইকরণ ব্যর্থ');
        redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL);
    }
}

/**
 * Clean file path for security
 * 
 * @param string $path
 * @return string
 */
function securePath($path) {
    // Remove directory traversal attempts
    $path = str_replace(['../', './'], '', $path);
    // Remove null bytes
    $path = str_replace("\0", '', $path);
    // Normalize path
    $path = realpath($path) ?: $path;
    return $path;
}
