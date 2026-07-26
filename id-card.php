<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userModel = new User();
$user = $userModel->getById($id);

// Check if user exists and has generated card
if (!$user || empty($user['id_card_generated'])) {
    header('Location: ' . SITE_URL);
    exit;
}

$setting = new Setting();
$site_name = $setting->get('site_name') ?: 'আলোকপাত';
$site_logo = $setting->get('site_logo') ?: SITE_URL . '/assets/images/logo.png';

$catModel = new Category();
$categories = $catModel->getMenuCategories();

$meta_title = $user['full_name'] . ' - ID Card - ' . $site_name;
$meta_description = $user['full_name'] . ' - ' . $site_name . ' Digital Media ID Card';

$roles = [
    'writer' => 'Writer (লেখক)',
    'ground_reporter' => 'Ground Reporter (মাঠ প্রতিবেদক)',
    'correspondent' => 'Correspondent (সংবাদদাতা)',
    'editor' => 'Editor (সম্পাদক)',
    'photojournalist' => 'Photojournalist (ফটো সাংবাদিক)',
    'digital_creator' => 'Digital Content Creator (ডিজিটাল কন্টেন্ট ক্রিয়েটর)',
    'sub_editor' => 'Sub Editor (উপ-সম্পাদক)',
    'bureau_chief' => 'Bureau Chief (ব্যুরো চীফ)'
];

$role_display = isset($user['id_card_role']) && isset($roles[$user['id_card_role']]) ? $roles[$user['id_card_role']] : 'Member';

ob_start();
?>
<!-- Header -->
<?php component('header', ['categories' => $categories]); ?>

<!-- Content -->
<style>
    /* Card Styles for Printing */
    @media print {
        body * {
            visibility: hidden;
        }
        #id-card-container, #id-card-container * {
            visibility: visible;
        }
        #id-card-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            display: flex;
            justify-content: center;
        }
        .no-print {
            display: none !important;
        }
    }
</style>

<div class="container mx-auto px-4 py-12 flex justify-center">
    <div class="w-full max-w-sm">
        <!-- Print Button -->
        <div class="mb-6 text-center no-print">
            <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md cursor-pointer transition-colors flex items-center justify-center mx-auto">
                <i class="fas fa-print mr-2"></i> Print ID Card
            </button>
        </div>

        <!-- ID Card -->
        <div id="id-card-container" class="bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] overflow-hidden border border-gray-200 relative w-full max-w-[320px] mx-auto font-sans">
            
            <!-- Card Header -->
            <div class="bg-white px-4 pt-6 pb-4 flex flex-col items-center border-b-[4px] border-indigo-700">
                <img src="<?php echo escape($site_logo); ?>" alt="Logo" class="h-10 object-contain mb-2">
                <div class="text-[11px] font-black tracking-[0.25em] text-indigo-800 uppercase">Digital Media</div>
            </div>

            <!-- Card Body -->
            <div class="px-6 py-6 flex flex-col items-center bg-gradient-to-b from-white to-gray-50">
                <!-- Avatar -->
                <div class="w-28 h-28 rounded-full border-4 border-indigo-50 shadow-md p-1 mb-4 bg-white flex items-center justify-center overflow-hidden shrink-0">
                    <?php if (!empty($user['avatar'])): ?>
                        <?php
                        $avatar_url = $user['avatar'];
                        if (strpos($avatar_url, 'http') !== 0) {
                            $avatar_url = SITE_URL . '/' . ltrim($avatar_url, '/');
                        }
                        ?>
                        <img src="<?php echo escape($avatar_url); ?>" alt="<?php echo escape($user['full_name']); ?>" class="w-full h-full object-cover rounded-full">
                    <?php else: ?>
                        <i class="fas fa-user text-gray-300 text-5xl"></i>
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <h2 class="text-xl font-bold text-gray-900 uppercase tracking-wide mb-2 text-center leading-tight">
                    <?php echo escape($user['full_name']); ?>
                </h2>
                
                <div class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider mb-6 text-center inline-block">
                    <?php echo escape($role_display); ?>
                </div>
                
                <div class="w-full h-px bg-gray-200 mb-5"></div>
                
                <!-- Info & QR -->
                <div class="w-full flex justify-between items-center gap-2">
                    <div class="space-y-3 flex-1">
                        <div>
                            <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Emp ID</span>
                            <span class="font-bold text-gray-800 text-sm">ALP-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <?php if (!empty($user['phone'])): ?>
                        <div>
                            <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Contact</span>
                            <span class="font-bold text-gray-800 text-sm"><?php echo escape($user['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-1.5 bg-white border border-gray-200 rounded-lg shadow-sm shrink-0">
                        <div id="qrcode"></div>
                    </div>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="bg-indigo-900 px-4 py-3 text-center">
                <p class="text-[9px] font-medium text-indigo-100 leading-tight mb-1">এই আইডি কার্ডটি শুধুমাত্র সাংগঠনিক ব্যবহারের জন্য।</p>
                <p class="text-[8px] font-medium text-indigo-300 leading-tight tracking-wider uppercase">Organizational Use Only</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const qrContainer = document.getElementById('qrcode');
    const authorUrl = '<?php echo SITE_URL; ?>/author.php?id=<?php echo $user['id']; ?>&name=<?php echo urlencode($user['full_name']); ?>';
    
    new QRCode(qrContainer, {
        text: authorUrl,
        width: 68,
        height: 68,
        colorDark : "#111827",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.M
    });
</script>

<script>
    const qrContainer = document.getElementById('qrcode');
    const authorUrl = '<?php echo SITE_URL; ?>/author.php?id=<?php echo $user['id']; ?>&name=<?php echo urlencode($user['full_name']); ?>';
    
    new QRCode(qrContainer, {
        text: authorUrl,
        width: 72,
        height: 72,
        colorDark : "#111827",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
