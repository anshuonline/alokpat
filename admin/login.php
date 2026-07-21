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
    
    // Check Honeypot Field (Bot detection)
    if (!empty($_POST['website_url'])) {
        // Silently reject or fake error
        setFlash('error', 'ভুল ইউজারনেম বা পাসওয়ার্ড');
        redirect(ADMIN_URL . '/login.php');
    }

    // Validate inputs
    $captcha = $_POST['captcha'] ?? '';
    
    if (empty($username) || empty($password)) {
        setFlash('error', 'ইউজারনেম এবং পাসওয়ার্ড দিন');
    } elseif (!isset($_SESSION['login_captcha']) || $captcha != $_SESSION['login_captcha']) {
        setFlash('error', 'ক্যাপচা (CAPTCHA) ভুল হয়েছে। আবার চেষ্টা করুন।');
    } else {
        // Check rate limiting
        if (!checkRateLimit('login_' . $_SERVER['REMOTE_ADDR'], 5, 300)) {
            setFlash('error', 'অনেকগুলি ব্যর্থ প্রচেষ্টা। ৫ মিনিট পর আবার চেষ্টা করুন');
        } else {
            $user = new User();
            $authenticated = $user->authenticate($username, $password);
            
            if ($authenticated) {
                // Prevent Session Fixation attacks
                session_regenerate_id(true);
                
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

// Generate new Math Captcha for the form
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['login_captcha'] = $num1 + $num2;
$captcha_question = "{$num1} + {$num2} = ?";
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - আলোকপাত অ্যাডমিন</title>
    <?php 
    if (function_exists('component')) {
        component('favicon'); 
    } else {
        echo '<link rel="icon" href="' . SITE_URL . '/assets/images/favicon.ico">';
    }
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Noto Sans Bengali', sans-serif;
        }
        .bg-tech {
            background-image: url('<?php echo SITE_URL; ?>/assets/images/admin-bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        /* Hide honeypot field */
        .hp-field {
            display: none !important;
        }
    </style>
</head>
<body class="bg-tech min-h-screen flex items-center justify-center relative">
    
    <!-- Dark overlay for better contrast -->
    <div class="absolute inset-0 bg-black/70 backdrop-blur-[2px]"></div>
    
    <div class="relative z-10 w-full max-w-md px-4">
        <!-- Minimalist Black & White Card -->
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border-t-4 border-black">
            
            <div class="p-8">
                <!-- Logo -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-black text-white rounded-full mb-4 shadow-lg">
                        <i class="fas fa-shield-alt text-2xl"></i>
                    </div>
                    <h1 class="text-3xl font-extrabold text-black tracking-tight">
                        আলোকপাত
                    </h1>
                    <p class="text-gray-500 mt-1 font-medium tracking-widest text-sm uppercase">অ্যাডমিন প্যানেল</p>
                </div>
                
                <!-- Flash Messages -->
                <?php if ($flash = getFlash()): ?>
                    <div class="mb-6 p-4 rounded-lg text-sm font-medium <?php echo $flash['type'] == 'success' ? 'bg-green-50 text-green-900 border border-green-200' : 'bg-red-50 text-red-900 border border-red-200'; ?>">
                        <div class="flex items-center">
                            <i class="fas <?php echo $flash['type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                            <?php echo escape($flash['message']); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="" class="space-y-5" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Honeypot Field -->
                    <input type="text" name="website_url" class="hp-field" tabindex="-1" autocomplete="off">
                    
                    <div>
                        <label for="username" class="block text-sm font-bold text-black mb-1">
                            ইউজারনেম
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   required
                                   class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-0 focus:border-black transition-colors outline-none bg-gray-50 focus:bg-white text-black font-medium"
                                   placeholder="admin"
                                   value="<?php echo isset($_POST['username']) ? escape($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-bold text-black mb-1">
                            পাসওয়ার্ড
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required
                                   class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-0 focus:border-black transition-colors outline-none bg-gray-50 focus:bg-white text-black font-medium"
                                   placeholder="••••••••">
                        </div>
                    </div>
                    
                    <!-- CAPTCHA Field -->
                    <div>
                        <label for="captcha" class="block text-sm font-bold text-black mb-1">
                            নিরাপত্তা যাচাই: <span class="text-blue-700 bg-blue-50 px-2 py-0.5 rounded font-mono border border-blue-200"><?php echo $captcha_question; ?></span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-robot text-gray-400"></i>
                            </div>
                            <input type="number" 
                                   id="captcha" 
                                   name="captcha" 
                                   required
                                   class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-0 focus:border-black transition-colors outline-none bg-gray-50 focus:bg-white text-black font-medium"
                                   placeholder="ফলাফল লিখুন">
                        </div>
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full bg-black text-white py-3.5 rounded-lg font-bold hover:bg-gray-800 transition-colors shadow-md flex justify-center items-center group">
                            <span>লগইন করুন</span>
                            <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>
                
            </div>
            
            <!-- Back to Site -->
            <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center">
                <a href="<?php echo SITE_URL; ?>" class="text-sm font-medium text-gray-600 hover:text-black transition-colors inline-flex items-center">
                    <i class="fas fa-globe mr-2"></i>
                    ওয়েবসাইটে ফিরে যান
                </a>
            </div>
            
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-gray-400">
            <p class="text-xs tracking-wide">
                &copy; <?php echo date('Y'); ?> আলোকপাত অ্যাডমিন কন্ট্রোল. All Rights Reserved.
            </p>
        </div>
    </div>
    
    <script>
        // Simple client-side validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            btn.classList.add('opacity-80', 'cursor-not-allowed');
        });
    </script>
</body>
</html>
