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
            <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow cursor-pointer">
                <i class="fas fa-print mr-2"></i> Print ID Card
            </button>
        </div>

        <!-- ID Card -->
        <div id="id-card-container" class="bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-200">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-navy-900 to-indigo-900 text-white text-center py-4" style="background: linear-gradient(to right, #0f172a, #312e81);">
                <img src="<?php echo escape($site_logo); ?>" alt="Logo" class="h-12 mx-auto mb-2 bg-white rounded p-1 object-contain">
                <div class="text-yellow-400 font-bold tracking-widest text-sm">DIGITAL MEDIA</div>
                <div class="text-xs font-semibold mt-1 uppercase">Alokpat.in</div>
            </div>

            <!-- Card Body -->
            <div class="p-6 flex flex-col items-center relative">
                <!-- Avatar -->
                <div class="w-24 h-24 rounded-full border-4 border-white shadow-lg overflow-hidden -mt-12 mb-4 bg-gray-100 flex items-center justify-center">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="<?php echo SITE_URL . '/' . escape($user['avatar']); ?>" alt="<?php echo escape($user['full_name']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user text-gray-400 text-4xl"></i>
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <h2 class="text-2xl font-bold text-gray-900 mb-1 text-center"><?php echo escape($user['full_name']); ?></h2>
                <div class="text-indigo-600 font-semibold mb-3 text-center"><?php echo escape($role_display); ?></div>
                
                <div class="w-full border-t border-gray-200 my-3"></div>
                
                <div class="w-full text-sm text-gray-700 space-y-2 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">ID No:</span>
                        <span class="font-bold">ALP-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <?php if (!empty($user['phone'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Phone:</span>
                        <span class="font-bold"><?php echo escape($user['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium">Joined:</span>
                        <span class="font-bold"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>

                <!-- QR Code -->
                <div id="qrcode" class="mt-2 mb-4 p-2 bg-white border border-gray-200 rounded shadow-sm"></div>

                <!-- Disclaimer -->
                <div class="text-center mt-2">
                    <p class="text-[10px] text-gray-500 leading-tight mb-1">
                        ⚠️ এই আইডি কার্ডটি শুধুমাত্র সাংগঠনিক ব্যবহারের জন্য। এটি কোনো সরকারি বা আইনি নথি নয়।
                    </p>
                    <p class="text-[9px] text-gray-400 leading-tight">
                        This ID card is for organizational use only. Not a legally registered credential.
                    </p>
                </div>
            </div>
            
            <!-- Card Footer -->
            <div class="bg-gray-100 py-2 text-center border-t border-gray-200">
                <a href="<?php echo SITE_URL; ?>" class="text-xs text-gray-600 font-medium">www.alokpat.in</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var qrData = "<?php echo SITE_URL; ?>/author.php?id=<?php echo $user['id']; ?>&name=<?php echo urlencode($user['full_name']); ?>";
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 80,
            height: 80,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.L
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'layouts/main.php';
?>
