<?php
/**
 * Verify 2FA Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
require_once '../helpers/GoogleAuthenticator.php';

// Redirect if fully logged in
if (isLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

// Redirect if no pending 2FA login
if (!isset($_SESSION['2fa_pending_user_id'])) {
    redirect(ADMIN_URL . '/login.php');
}

$user_id = (int)$_SESSION['2fa_pending_user_id'];
$db = (new Database())->getConnection();
$user_model = new User();
$user = $user_model->getById($user_id);

if (!$user || $user['two_factor_enabled'] == 0) {
    // Should not happen, but fallback
    unset($_SESSION['2fa_pending_user_id']);
    redirect(ADMIN_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    // Check rate limiting to prevent brute force
    if (!checkRateLimit('2fa_' . $_SERVER['REMOTE_ADDR'], 10, 300)) {
        setFlash('error', 'অনেকগুলি ব্যর্থ প্রচেষ্টা। ৫ মিনিট পর আবার চেষ্টা করুন');
    } else {
        $code = $_POST['code'] ?? '';
        
        if (empty($code)) {
            setFlash('error', 'দয়া করে ৬-ডিজিটের কোডটি দিন।');
        } else {
            $ga = new GoogleAuthenticator();
            $checkResult = $ga->verifyCode($user['two_factor_secret'], $code, 2);
            
            if ($checkResult) {
                // Success! Establish full session
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                unset($_SESSION['2fa_pending_user_id']);
                clearRateLimit('2fa_' . $_SERVER['REMOTE_ADDR']);
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                setFlash('success', 'স্বাগতম, ' . $user['full_name'] . '!');
                redirect(ADMIN_URL . '/dashboard.php');
            } else {
                setFlash('error', 'ভুল 2FA কোড। আবার চেষ্টা করুন।');
            }
        }
    }
}

$page_title = '2FA Verify';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - আলোকপাত অ্যাডমিন</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 m-4">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Two-Factor Authentication</h1>
            <p class="text-sm text-gray-500 mt-2">লগইন সম্পূর্ণ করতে আপনার Google Authenticator অ্যাপ থেকে ৬-ডিজিটের কোডটি দিন</p>
        </div>
        
        <?php if (isset($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="p-3 mb-6 rounded-lg text-sm <?php echo $type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo escape($message); ?>
                </div>
            <?php endforeach; unset($_SESSION['flash']); ?>
        <?php endif; ?>
        
        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">৬-ডিজিটের কোড</label>
                <input type="text" name="code" required maxlength="6" pattern="\d{6}" autocomplete="off" autofocus
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-center text-2xl tracking-widest"
                       placeholder="123456">
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition">
                যাচাই করুন (Verify)
            </button>
            
            <div class="text-center mt-4">
                <a href="<?php echo ADMIN_URL; ?>/login.php" class="text-sm text-gray-500 hover:text-blue-600">লগইন পেজে ফিরে যান</a>
            </div>
        </form>
    </div>
    
</body>
</html>
