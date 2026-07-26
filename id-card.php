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
<?php
// Avatar URL
$avatar_url = '';
if (!empty($user['avatar'])) {
    $avatar_url = $user['avatar'];
    if (strpos($avatar_url, 'http') !== 0) {
        $avatar_url = SITE_URL . '/' . ltrim($avatar_url, '/');
    }
}
?>

<div class="container mx-auto px-4 py-8 flex justify-center">
    <div class="w-full max-w-lg">
        <!-- Buttons -->
        <div class="mb-6 flex justify-center gap-3 no-print">
            <button onclick="flipCard()" id="flipBtn" class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 font-semibold py-2.5 px-5 rounded-lg shadow-sm cursor-pointer transition-colors flex items-center text-sm">
                <i class="fas fa-sync-alt mr-2 text-xs"></i> <span id="flipText">Show Back Side</span>
            </button>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-5 rounded-lg shadow-sm cursor-pointer transition-colors flex items-center text-sm">
                <i class="fas fa-file-pdf mr-2"></i> Print / Save PDF
            </button>
        </div>

        <!-- ID Card -->
        <div id="id-card-container" class="relative mx-auto" style="width:302px; height:480px;">

            <!-- FRONT SIDE -->
            <div id="card-front" class="absolute inset-0 transition-opacity duration-300" style="width:302px; height:480px;">
                <div style="width:302px; height:480px; border:1px solid #c7d2e0; border-radius:14px; overflow:hidden; background:#fff; font-family:'Segoe UI',Arial,sans-serif; box-shadow:0 4px 20px rgba(0,0,0,0.08); display:flex; flex-direction:column;">
                    <!-- Blue Header -->
                    <div style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); padding:20px 20px 45px; text-align:center;">
                        <img src="<?php echo escape($site_logo); ?>" alt="Logo" style="display:block; margin:0 auto 6px; height:36px; object-fit:contain; filter:brightness(0) invert(1);">
                        <div style="color:#93c5fd; font-size:10px; font-weight:800; letter-spacing:4px; text-transform:uppercase; margin-right:-4px;">Digital Media</div>
                    </div>

                    <!-- White Body -->
                    <div style="padding:0 22px 16px; text-align:center;">
                        <!-- Photo -->
                        <div style="width:96px; height:128px; border-radius:12px; border:3px solid #2563eb; margin:-50px auto 12px; overflow:hidden; background:#f1f5f9; position:relative; z-index:10;">
                            <?php if ($avatar_url): ?>
                                <img src="<?php echo escape($avatar_url); ?>" alt="<?php echo escape($user['full_name']); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#cbd5e1; font-size:40px;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="font-size:20px; font-weight:900; color:#111827; text-transform:uppercase; letter-spacing:1px; line-height:1.2; margin-bottom:4px;">
                            <?php echo escape($user['full_name']); ?>
                        </div>
                        <div style="font-size:12px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:2px; margin-bottom:16px;">
                            <?php echo escape($role_display); ?>
                        </div>

                        <div style="height:1px; background:#e5e7eb; margin-bottom:14px;"></div>

                        <!-- Info Row -->
                        <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                            <div style="text-align:left;">
                                <div style="margin-bottom:8px;">
                                    <div style="font-size:8px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:2px; margin-bottom:1px;">Emp ID</div>
                                    <div style="font-size:14px; font-weight:800; color:#1e3a5f;">ALP-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                </div>
                                <div style="margin-bottom:8px;">
                                    <div style="font-size:8px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:2px; margin-bottom:1px;">Blood Group</div>
                                    <div style="font-size:14px; font-weight:800; color:#dc2626;"><?php echo escape($user['blood_group'] ?? 'N/A'); ?></div>
                                </div>
                                <?php if (!empty($user['phone'])): ?>
                                <div>
                                    <div style="font-size:8px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:2px; margin-bottom:1px;">Contact</div>
                                    <div style="font-size:13px; font-weight:800; color:#1e3a5f;"><?php echo escape($user['phone']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="padding:3px; border:1px solid #e5e7eb; border-radius:8px; background:#fff;">
                                <div id="qrcode"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Blue Footer -->
                    <div style="background:#1e3a5f; padding:8px 16px; text-align:center; margin-top:auto;">
                        <div style="font-size:9px; color:#93c5fd; font-weight:600; letter-spacing:1px;">www.alokpat.in</div>
                    </div>
                </div>
            </div>

            <!-- BACK SIDE -->
            <div id="card-back" class="absolute inset-0 transition-opacity duration-300 opacity-0 pointer-events-none" style="width:302px; height:480px;">
                <div style="width:302px; height:480px; border:1px solid #c7d2e0; border-radius:14px; overflow:hidden; background:#fff; font-family:'Segoe UI',Arial,sans-serif; box-shadow:0 4px 20px rgba(0,0,0,0.08); display:flex; flex-direction:column;">
                    <div style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); padding:20px; text-align:center;">
                        <img src="<?php echo escape($site_logo); ?>" alt="Logo" style="display:block; margin:0 auto 4px; height:32px; object-fit:contain; filter:brightness(0) invert(1);">
                        <div style="color:#93c5fd; font-size:10px; font-weight:800; letter-spacing:4px; text-transform:uppercase; margin-right:-4px;">Digital Media</div>
                    </div>

                    <div style="flex:1; padding:24px 22px; display:flex; flex-direction:column; align-items:center; justify-content:center; position:relative;">
                        <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; opacity:0.04; pointer-events:none;">
                            <img src="<?php echo escape($site_logo); ?>" style="width:160px; object-fit:contain;">
                        </div>
                        <div style="position:relative; z-index:1; text-align:center; width:100%;">
                            <div style="font-size:11px; font-weight:800; color:#1e3a5f; text-transform:uppercase; letter-spacing:2px; margin-bottom:12px;">Terms & Conditions</div>
                            <p style="font-size:10px; color:#6b7280; line-height:1.6; margin-bottom:20px;">
                                This card is the property of Alokpat Digital Media. It is for organizational identification only and is not a legally registered credential.<br><br>
                                If found, please return to the address below.
                            </p>
                            <div style="width:40px; height:1px; background:#bfdbfe; margin:0 auto 20px;"></div>
                            <div style="font-size:10px; font-weight:800; color:#1e3a5f; text-transform:uppercase; letter-spacing:2px; margin-bottom:8px;">Contact</div>
                            <p style="font-size:10px; color:#6b7280; line-height:1.7;">
                                Alokpat Digital Media<br>
                                Kolkata, West Bengal<br>
                                contact@alokpat.in<br>
                                www.alokpat.in
                            </p>
                        </div>
                    </div>

                    <div style="background:#1e3a5f; padding:10px 16px; text-align:center;">
                        <p style="font-size:8px; color:#93c5fd; font-weight:600; letter-spacing:1px; margin:0;">FOR ORGANIZATIONAL USE ONLY</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden !important; }
        .no-print { display: none !important; }
        
        #id-card-container, #id-card-container * { visibility: visible !important; }
        
        #id-card-container {
            position: fixed !important;
            left: 50% !important;
            top: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: auto !important;
            height: auto !important;
        }

        #card-front, #card-back {
            position: relative !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            display: block !important;
            margin: 0 auto 20px !important;
        }

        #card-front > div, #card-back > div {
            border: 2px dashed #999 !important;
            box-shadow: none !important;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @page {
            size: A4 portrait;
            margin: 15mm;
        }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let isFlipped = false;
    function flipCard() {
        const front = document.getElementById("card-front");
        const back = document.getElementById("card-back");
        const flipText = document.getElementById("flipText");
        isFlipped = !isFlipped;
        if (isFlipped) {
            front.classList.add("opacity-0", "pointer-events-none");
            back.classList.remove("opacity-0", "pointer-events-none");
            flipText.textContent = "Show Front Side";
        } else {
            front.classList.remove("opacity-0", "pointer-events-none");
            back.classList.add("opacity-0", "pointer-events-none");
            flipText.textContent = "Show Back Side";
        }
    }

    new QRCode(document.getElementById('qrcode'), {
        text: '<?php echo SITE_URL; ?>/author.php?id=<?php echo $user['id']; ?>&name=<?php echo urlencode($user['full_name']); ?>',
        width: 64,
        height: 64,
        colorDark: "#1e3a5f",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
</script>

<?php
$content = ob_get_clean();
include 'layouts/main.php';
?>
