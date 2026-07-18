<?php
/**
 * Alokpat - Bengali News Portal CMS
 * Main Configuration File
 * 
 * @package Alokpat
 * @version 1.0.0
 * @author Alokpat Team
 */

// Environment Detection (Local vs Live)
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // LOCAL XAMPP ENVIRONMENT
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'alokpath');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('SITE_URL', 'http://localhost/alokpath');
    
    // Error Reporting for Local
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    // LIVE HOSTINGER ENVIRONMENT
    define('DB_HOST', 'localhost'); // Hostinger internal connection
    define('DB_NAME', 'u388169091_alokpat');
    define('DB_USER', 'u388169091_alokpat');
    define('DB_PASS', '@Alokpat.in1234');
    define('SITE_URL', 'https://alokpat.in');
    
    // Disable Error Reporting for Security on Live
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Global Database Charset
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'Alokpat');
define('ADMIN_URL', SITE_URL . '/admin');

// Site Font Configuration (Centralized)
define('SITE_FONT_NAME', 'Noto Serif Bengali');
define('SITE_FONT_CSS', "'Noto Serif Bengali', serif");
define('SITE_FONT_URL', 'https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@300;400;500;600;700&display=swap');

// Path Configuration
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_URL', SITE_URL . '/uploads');

// Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif']);
define('UPLOAD_DIR_STRUCTURE', true); // Organize by date folders

// Session Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'alokpath_session');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('HASH_ALGORITHM', 'md5'); // Temporary - upgrade to bcrypt later
define('XSS_PROTECTION', true);

// Pagination
define('POSTS_PER_PAGE', 10);
define('ADMIN_POSTS_PER_PAGE', 15);

// SEO Settings
define('DEFAULT_META_TITLE', 'Alokpat - Bengali News Portal');
define('DEFAULT_META_DESCRIPTION', 'à¦†à¦²à§‹à¦•à¦ªà¦¾à¦¤ - à¦¬à¦¾à¦‚à¦²à¦¾ à¦¸à¦‚à¦¬à¦¾à¦¦ à¦à¦¬à¦‚ à¦¨à¦¿à¦‰à¦œ à¦ªà§‹à¦°à§à¦Ÿà¦¾à¦²');
define('DEFAULT_KEYWORDS', 'à¦–à¦¬à¦° à¦†à¦œ, bengali news, à¦†à¦²à§‹à¦•à¦ªà¦¾à¦¤, alokpat');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Include Core Files
require_once BASE_PATH . '/helpers/functions.php';
require_once BASE_PATH . '/helpers/security.php';
require_once BASE_PATH . '/database/Database.php';
require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/models/Post.php';
require_once BASE_PATH . '/models/Category.php';
require_once BASE_PATH . '/models/Menu.php';
require_once BASE_PATH . '/models/Tag.php';
require_once BASE_PATH . '/models/Media.php';
require_once BASE_PATH . '/models/Setting.php';

// Initialize Database
$database = new Database();
$db = $database->getConnection();


