<?php
/**
 * Admin Login Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';

// Custom Login Slug Security Check
$setting = new Setting();
$custom_slug = $setting->get('custom_login_slug');
if (!empty($custom_slug)) {
    $requested_slug = $_GET['login_slug'] ?? '';
    $direct_access = strpos($_SERVER['REQUEST_URI'], 'login.php') !== false;
    
    if ($direct_access || $requested_slug !== $custom_slug) {
        // Obscure login page by redirecting to home
        redirect(SITE_URL);
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        setFlash('error', 'ইউজারনেম এবং পাসওয়ার্ড দিন');
    } else {
        // Check rate limiting
        if (!checkRateLimit('login_' . $_SERVER['REMOTE_ADDR'], 5, 300)) {
            setFlash('error', 'অনেকগুলি ব্যর্থ প্রচেষ্টা। ৫ মিনিট পর আবার চেষ্টা করুন');
        } else {
            $user = new User();
            $authenticated = $user->authenticate($username, $password);
            
            if ($authenticated) {
                // Set session
                $_SESSION['user_id'] = $authenticated['id'];
                $_SESSION['username'] = $authenticated['username'];
                $_SESSION['role'] = $authenticated['role'];
                
                clearRateLimit('login_' . $_SERVER['REMOTE_ADDR']);
                setFlash('success', 'স্বাগতম, ' . $authenticated['full_name'] . '!');
                redirect(ADMIN_URL . '/dashboard.php');
            } else {
                setFlash('error', 'ভুল ইউজারনেম বা পাসওয়ার্ড');
            }
        }
    }
}

$page_title = 'লগইন';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - আলোকপাত অ্যাডমিন</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛽</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans Bengali', sans-serif;
        }
        .login-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="login-gradient min-h-screen flex items-center justify-center">
    
    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    আলোকপাত
                </h1>
                <p class="text-gray-600 mt-2">অ্যাডমিন প্যানেল</p>
            </div>
            
            <!-- Flash Messages -->
            <?php if ($flash = getFlash()): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $flash['type'] == 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                    <?php echo escape($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        ইউজারনেম
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="admin"
                           value="<?php echo isset($_POST['username']) ? escape($_POST['username']) : ''; ?>">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        পাসওয়ার্ড
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="••••••••">
                </div>
                
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    লগইন
                </button>
            </form>
            
            <!-- Back to Site -->
            <div class="mt-6 text-center">
                <a href="<?php echo SITE_URL; ?>" class="text-sm text-gray-600 hover:text-blue-600">
                    <i class="fas fa-arrow-left mr-1"></i>
                    ওয়েবসাইটে ফিরে যান
                </a>
            </div>
            
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-6 text-white">
            <p class="text-sm opacity-90">
                &copy; <?php echo date('Y'); ?> আলোকপাত অ্যাডমিন কন্ট্রোল
            </p>
        </div>
    </div>
    
</body>
</html>
