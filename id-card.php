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
        <div id="id-card-container" class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-300 relative w-full max-w-[340px] mx-auto font-sans">
            <!-- Top Red Bar -->
            <div class="h-2 bg-red-600 w-full"></div>
            
            <!-- Card Header -->
            <div class="bg-gray-50 border-b border-gray-200 p-5 pb-12 flex justify-between items-start relative overflow-hidden">
                <div class="flex items-center gap-3 relative z-10">
                    <div class="bg-white p-1.5 rounded shadow-sm border border-gray-100">
                        <img src="<?php echo escape($site_logo); ?>" alt="Logo" class="h-9 object-contain">
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] leading-none mb-1">Alokpat.in</div>
                        <div class="text-xl font-black text-gray-900 uppercase tracking-tight leading-none">DIGITAL MEDIA</div>
                    </div>
                </div>
                
                <!-- PRESS Badge -->
                <div class="absolute right-0 top-5 bg-red-600 text-white text-[11px] font-black px-4 py-1.5 shadow-sm rounded-l uppercase tracking-widest z-10">
                    PRESS
                </div>
                
                <!-- Subtle background pattern -->
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-gray-200 rounded-full opacity-20 z-0"></div>
            </div>

            <!-- Card Body -->
            <div class="px-6 pb-6 pt-0 flex flex-col items-center relative z-20">
                <!-- Avatar -->
                <div class="w-[110px] h-[110px] rounded-xl border-4 border-white shadow-lg overflow-hidden -mt-[55px] mb-5 bg-gray-100 flex items-center justify-center relative z-20">
                    <?php if (!empty($user['avatar'])): ?>
                        <?php
                        $avatar_url = $user['avatar'];
                        if (strpos($avatar_url, 'http') !== 0) {
                            $avatar_url = SITE_URL . '/' . ltrim($avatar_url, '/');
                        }
                        ?>
                        <img src="<?php echo escape($avatar_url); ?>" alt="<?php echo escape($user['full_name']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user text-gray-400 text-5xl"></i>
                    <?php endif; ?>
                </div>

                <!-- Details -->
                <h2 class="text-[22px] font-black text-gray-900 mb-2 flex items-center justify-center text-center leading-tight uppercase tracking-wide">
                    <?php echo escape($user['full_name']); ?>
                </h2>
                
                <div class="bg-red-600 text-white px-5 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.1em] shadow-sm mb-6 text-center inline-block">
                    <?php echo escape($role_display); ?>
                </div>
                
                <div class="w-full border-t-2 border-dashed border-gray-200 mb-5"></div>
                
                <!-- Info & QR -->
                <div class="w-full flex justify-between items-end gap-2">
                    <div class="text-sm space-y-3 flex-1 pb-1">
                        <div>
                            <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Emp ID</span>
                            <span class="font-bold text-gray-800 text-[15px]">ALP-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <?php if (!empty($user['phone'])): ?>
                        <div>
                            <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-0.5">Contact</span>
                            <span class="font-bold text-gray-800 text-[15px]"><?php echo escape($user['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-1.5 bg-white border border-gray-200 rounded-lg shadow-sm shrink-0">
                        <div id="qrcode"></div>
                    </div>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3.5 text-center">
                <p class="text-[10px] font-bold text-gray-600 leading-tight mb-1.5">এই আইডি কার্ডটি শুধুমাত্র সাংগঠনিক ব্যবহারের জন্য। এটি কোনো সরকারি বা আইনি নথি নয়।</p>
                <p class="text-[9px] font-semibold text-gray-500 leading-tight">This ID card is for organizational use only. It is not a legally registered credential.</p>
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
        width: 72,
        height: 72,
        colorDark : "#111827",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });
</script>

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
