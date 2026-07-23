<?php
/**
 * Setup 2FA Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
require_once '../helpers/GoogleAuthenticator.php';
requireAuth();

$user_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();
$user_model = new User();
$current_user = $user_model->getById($user_id);

$is_active = ($current_user['two_factor_enabled'] == 1);

$ga = new GoogleAuthenticator();

// Generate a secret if not already in session
if (!isset($_SESSION['setup_2fa_secret'])) {
    $_SESSION['setup_2fa_secret'] = $ga->createSecret();
}

$secret = $_SESSION['setup_2fa_secret'];
$qrCodeUrl = $ga->getQRCodeGoogleUrl('Alokpat Admin (' . $current_user['username'] . ')', $secret, 'Alokpat');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_active) {
    requireCSRF();
    $code = $_POST['code'] ?? '';
    
    if (empty($code)) {
        setFlash('error', 'দয়া করে ৬-ডিজিটের কোডটি দিন।');
    } else {
        $checkResult = $ga->verifyCode($secret, $code, 2); // 2 = 2*30sec clock tolerance
        
        if ($checkResult) {
            // Update database
            $stmt = $db->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?");
            if ($stmt->execute([$secret, $user_id])) {
                unset($_SESSION['setup_2fa_secret']);
                setFlash('success', 'Two-Factor Authentication (2FA) সফলভাবে চালু হয়েছে!');
                redirect(ADMIN_URL . '/setup-2fa.php');
            } else {
                setFlash('error', 'ডাটাবেস আপডেট করতে সমস্যা হয়েছে।');
            }
        } else {
            setFlash('error', 'কোডটি ভুল হয়েছে। আবার চেষ্টা করুন।');
        }
    }
}

$page_title = '2FA Security';
ob_start();
?>
<div class="max-w-3xl mx-auto py-10 px-4">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
        <div class="p-6 bg-blue-600 text-white text-center">
            <h2 class="text-3xl font-bold">Two-Factor Authentication (2FA)</h2>
            <p class="mt-2 text-blue-100">আপনার অ্যাকাউন্টের নিরাপত্তা বাড়াতে 2FA সেটআপ করা বাধ্যতামূলক</p>
        </div>
        
        <div class="p-8">
            <?php if ($is_active): ?>
            
            <div class="flex flex-col items-center justify-center py-8">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-check-circle text-6xl text-green-500"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">2FA সক্রিয় আছে</h3>
                <p class="text-gray-600 text-center max-w-md">
                    আপনার অ্যাকাউন্টটি বর্তমানে <strong>Two-Factor Authentication</strong> দ্বারা সুরক্ষিত। 
                    প্রতিবার লগইন করার সময় আপনাকে Google Authenticator অ্যাপ থেকে কোড দিতে হবে।
                </p>
                <!-- If you need a reset option in the future, it can be added here -->
            </div>
            
            <?php else: ?>
            
            <div class="flex flex-col md:flex-row gap-8 items-center">
                <div class="w-full md:w-1/2 flex flex-col items-center">
                    <p class="text-gray-600 mb-4 text-center">১. আপনার মোবাইলে <strong>Google Authenticator</strong> বা <strong>Authy</strong> অ্যাপ ওপেন করুন এবং নিচের QR কোডটি স্ক্যান করুন:</p>
                    
                    <div class="p-4 bg-gray-50 border rounded-lg shadow-inner inline-block">
                        <img src="<?php echo escape($qrCodeUrl); ?>" alt="2FA QR Code" class="w-48 h-48">
                    </div>
                    
                    <p class="mt-4 text-sm text-gray-500">QR কোড স্ক্যান করতে না পারলে, ম্যানুয়ালি এই কোডটি দিন:</p>
                    <code class="mt-2 px-3 py-1 bg-gray-100 text-gray-800 font-bold rounded tracking-widest text-lg"><?php echo escape($secret); ?></code>
                </div>
                
                <div class="w-full md:w-1/2 border-t md:border-t-0 md:border-l pt-6 md:pt-0 md:pl-8">
                    <p class="text-gray-600 mb-6">২. অ্যাপে যে ৬-ডিজিটের কোডটি দেখাচ্ছে, সেটি নিচে দিন:</p>
                    
                    <?php if (isset($_SESSION['flash'])): ?>
                        <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                            <div class="p-3 mb-4 rounded-lg <?php echo $type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo escape($message); ?>
                            </div>
                        <?php endforeach; unset($_SESSION['flash']); ?>
                    <?php endif; ?>

                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">৬-ডিজিটের কোড (6-digit code)</label>
                            <input type="text" name="code" required maxlength="6" pattern="\d{6}" autocomplete="off"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-center text-2xl tracking-widest"
                                   placeholder="123456">
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition shadow-md">
                            যাচাই করুন এবং চালু করুন (Verify & Enable)
                        </button>
                    </form>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once 'layouts/admin.php';
?>
