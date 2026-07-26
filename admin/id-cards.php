<?php
require_once '../config/config.php';
requireAuth();
requirePermission('manage_users');

global $db;
$setting = new Setting();
$site_name = $setting->get('site_title') ?? 'Alokpat.in';
$site_logo = $setting->get('site_logo') ?: '';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
    if (isset($_POST['action']) && $_POST['action'] === 'generate') {
        $user_id = (int)$_POST['user_id'];
        $role = sanitize($_POST['id_card_role']);

        if ($user_id && $role) {
            // Check if user already has a card
            $checkStmt = $db->prepare("SELECT id_card_generated FROM users WHERE id = ?");
            $checkStmt->execute([$user_id]);
            $usr = $checkStmt->fetch();
            
            if ($usr && empty($usr['id_card_generated'])) {
                // Generate employee number
                $stmt = $db->prepare("SELECT MAX(CAST(SUBSTRING(employee_number, 5) AS UNSIGNED)) as max_emp FROM users WHERE employee_number LIKE 'ALP-%'");
                $stmt->execute();
                $result = $stmt->fetch();
                $next_num = ($result['max_emp'] ?? 0) + 1;
                $employee_number = 'ALP-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);

                $updateStmt = $db->prepare("UPDATE users SET employee_number = ?, id_card_role = ?, blood_group = ?, id_card_generated = 1 WHERE id = ?");
                $bg = sanitize($_POST['blood_group'] ?? '');
                if ($updateStmt->execute([$employee_number, $role, $bg, $user_id])) {
                    $success_msg = "ID Card generated successfully for user ID {$user_id}.";
                } else {
                    $error_msg = "Failed to generate ID card.";
                }
            } else {
                $error_msg = "User already has an ID card.";
            }
        } else {
            $error_msg = "Please select a user and role.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'revoke') {
        $user_id = (int)$_POST['user_id'];
        
        $updateStmt = $db->prepare("UPDATE users SET employee_number = NULL, id_card_role = NULL, id_card_generated = 0 WHERE id = ?");
        if ($updateStmt->execute([$user_id])) {
            $success_msg = "ID Card revoked successfully.";
        } else {
            $error_msg = "Failed to revoke ID card.";
        }
    }
}

// Fetch active users without ID card
$stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE status = 'active' AND (id_card_generated = 0 OR id_card_generated IS NULL)");
$stmt->execute();
$users_without_card = $stmt->fetchAll();

// Fetch users with ID card
$stmt = $db->prepare("SELECT id, full_name, email, avatar, phone, employee_number, id_card_role, blood_group FROM users WHERE id_card_generated = 1");
$stmt->execute();
$cards = $stmt->fetchAll();

$roles = [
    'Bureau Chief' => 'Bureau Chief',
    'Editor' => 'Editor',
    'Senior Correspondent' => 'Senior Correspondent',
    'Correspondent' => 'Correspondent',
    'Ground Reporter' => 'Ground Reporter',
    'Photojournalist' => 'Photojournalist',
    'Digital Creator' => 'Digital Creator',
    'Writer' => 'Writer'
];

$page_title = 'আইডি কার্ড (ID Cards)';
ob_start();
?>

<!-- Uiverse.io inspired CSS styles for premium look -->
<style>
    /* Premium Form Card */
    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(229, 231, 235, 0.5);
        transition: all 0.3s ease;
    }
    .premium-card:hover {
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }
    
    /* Input styling */
    .premium-input {
        width: 100%;
        padding: 12px 16px;
        border-radius: 10px;
        border: 2px solid transparent;
        background: #f3f4f6;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    .premium-input:focus {
        border-color: #4f46e5;
        background: #ffffff;
        outline: none;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }
    
    /* Button styling */
    .btn-generate {
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
    }
    .btn-generate:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
    }
    .btn-generate:active {
        transform: translateY(1px);
    }

    /* ID Card Design */
    .id-card-wrapper {
        width: 320px;
        margin: 0 auto;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        position: relative;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .id-card-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #4338ca 100%);
        padding: 20px 15px;
        text-align: center;
        color: white;
        position: relative;
    }
    
    .id-card-header::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 0;
        width: 100%;
        height: 30px;
        background: white;
        transform: skewY(-4deg);
    }

    .id-card-logo-area {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 5px;
    }

    .id-card-logo-area img {
        height: 30px;
        background: white;
        padding: 4px;
        border-radius: 4px;
    }

    .id-card-header h2 {
        font-size: 16px;
        font-weight: 800;
        letter-spacing: 2px;
        margin: 0;
        text-transform: uppercase;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }
    
    .id-card-header p {
        font-size: 12px;
        opacity: 0.9;
        margin: 4px 0 0 0;
    }

    .id-card-body {
        padding: 25px 15px 15px;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .id-photo-container {
        width: 100px;
        height: 100px;
        margin: 0 auto 15px;
        border-radius: 50%;
        padding: 3px;
        background: linear-gradient(135deg, #1e3a8a, #4338ca);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .id-photo {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid white;
        background: white;
    }

    .id-name {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 5px 0;
    }

    .id-role {
        font-size: 14px;
        color: #4f46e5;
        font-weight: 600;
        margin: 0 0 15px 0;
        background: #eef2ff;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-block;
    }

    .id-details {
        text-align: left;
        background: #f9fafb;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        margin-bottom: 15px;
    }

    .id-detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 12px;
    }
    
    .id-detail-row:last-child {
        margin-bottom: 0;
    }

    .id-detail-label {
        color: #6b7280;
        font-weight: 600;
    }

    .id-detail-value {
        color: #111827;
        font-weight: 700;
    }

    .id-qr-section {
        display: flex;
        justify-content: center;
        margin-bottom: 15px;
    }

    .id-qr-code {
        padding: 5px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .id-footer {
        background: #f3f4f6;
        padding: 12px 15px;
        text-align: center;
        border-top: 1px solid #e5e7eb;
    }
    
    .id-disclaimer {
        font-size: 9px;
        color: #6b7280;
        line-height: 1.4;
        margin: 0;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .modal, .modal-backdrop {
            display: none !important;
        }
    }
</style>

<div class="container mx-auto px-4 py-6">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Generate Form -->
        <div class="w-full lg:w-1/3">
            <div class="premium-card p-6">
                <h4 class="mb-4 text-xl text-gray-800 font-bold flex items-center">
                    <i class="fas fa-id-card-alt text-indigo-500 mr-2"></i>Generate ID Card
                </h4>
                
                <?php if ($success_msg): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-800 border-l-4 border-green-500 rounded-r-lg"><i class="fas fa-check-circle mr-2"></i><?= escape($success_msg) ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-800 border-l-4 border-red-500 rounded-r-lg"><i class="fas fa-exclamation-circle mr-2"></i><?= escape($error_msg) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="generate">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Select Staff</label>
                        <select name="user_id" class="premium-input w-full p-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" required>
                            <option value="">-- Choose User --</option>
                            <?php foreach ($users_without_card as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= escape($u['full_name']) ?> (<?= escape($u['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">ID Card Role</label>
                        <select name="id_card_role" class="premium-input w-full p-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" required>
                            <option value="">-- Choose Role --</option>
                            <?php foreach ($roles as $key => $label): ?>
                                <option value="<?= $key ?>"><?= escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                                        <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Blood Group</label>
                        <select name="blood_group" class="premium-input w-full p-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                            <option value="">-- Choose Blood Group (Optional) --</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-generate w-full flex items-center justify-center py-3 rounded-lg text-white font-bold transition-all">
                        <i class="fas fa-magic mr-2"></i> Generate ID Card
                    </button>
                </form>
            </div>
        </div>

        <!-- Generated Cards List -->
        <div class="w-full lg:w-2/3">
            <div class="premium-card p-6">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="m-0 text-xl text-gray-800 font-bold flex items-center">
                        <i class="fas fa-users text-indigo-500 mr-2"></i>Generated Cards
                    </h4>
                    <span class="inline-flex px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-bold shadow-sm">Total: <?= count($cards) ?></span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold border-b border-gray-200">
                            <tr>
                                <th class="py-3 px-4 font-bold text-gray-600">Employee</th>
                                <th class="py-3 px-4 font-bold text-gray-600">Emp No.</th>
                                <th class="py-3 px-4 font-bold text-gray-600">Role</th>
                                <th class="py-3 px-4 font-bold text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php if (empty($cards)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-gray-400">
                                    <i class="fas fa-id-card fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">No ID cards generated yet.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($cards as $card): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <?php 
                                            $img = 'https://ui-avatars.com/api/?name='.urlencode($card['full_name']).'&background=random';
                                            if (!empty($card['avatar'])) {
                                                if (strpos($card['avatar'], 'http') === 0) {
                                                    $img = $card['avatar'];
                                                } else {
                                                    $img = SITE_URL . '/' . ltrim($card['avatar'], '/');
                                                }
                                            }
                                            ?>
                                            <img src="<?= escape($img) ?>" class="w-10 h-10 rounded-full mr-3 object-cover shadow-sm" alt="">
                                            <div>
                                                <div class="font-bold text-gray-800"><?= escape($card['full_name']) ?></div>
                                                <div class="text-xs text-gray-500"><?= escape($card['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-indigo-600">
                                        <?= escape($card['employee_number']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">
                                        <span class="inline-flex px-2 py-1 bg-blue-50 text-blue-700 border border-blue-100 rounded text-xs font-semibold">
                                            <?= escape(isset($roles[$card['id_card_role']]) ? explode(' (', $roles[$card['id_card_role']])[0] : $card['id_card_role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right space-x-1">
                                        <button type="button" 
                                                class="px-3 py-1.5 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg shadow-sm text-sm transition-colors view-card-btn inline-flex items-center"
                                                data-id="<?= $card['id'] ?>"
                                                data-name="<?= escape($card['full_name']) ?>"
                                                data-role="<?= escape(isset($roles[$card['id_card_role']]) ? $roles[$card['id_card_role']] : $card['id_card_role']) ?>"
                                                data-empno="<?= escape($card['employee_number']) ?>"
                                                data-phone="<?= escape($card['phone'] ?? 'N/A') ?>"
                                                data-bg="<?= escape($card['blood_group'] ?? 'N/A') ?>"
                                                data-img="<?= escape($img) ?>">
                                            <i class="fas fa-eye mr-1.5"></i> View
                                        </button>
                                        
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to revoke this ID card?');">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="user_id" value="<?= $card['id'] ?>">
                                            <button type="submit" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow-sm text-sm transition-colors inline-flex items-center">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ID Card Preview Modal -->
<div id="idCardModal" class="fixed inset-0 z-[100] hidden bg-black/80 backdrop-blur-sm overflow-y-auto">
    <div class="min-h-screen px-4 py-8 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[400px] overflow-hidden">
            <!-- Modal Header -->
            <div class="flex justify-between items-center px-5 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">ID Card Preview</h3>
                <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-gray-500"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-5 bg-gray-50">
                <!-- Flip Button -->
                <div class="flex justify-center mb-4">
                    <button type="button" onclick="flipCard()" id="flipBtn" class="text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg px-4 py-2 transition-colors flex items-center">
                        <i class="fas fa-sync-alt mr-2 text-xs"></i> <span id="flipText">Show Back Side</span>
                    </button>
                </div>

                <!-- Card Area -->
                <div class="flex justify-center">
                    <div id="printable-card" class="relative" style="width:302px; height:480px;">

                        <!-- ===== FRONT SIDE ===== -->
                        <div id="card-front" class="absolute inset-0 transition-opacity duration-300" style="width:302px; height:480px;">
                            <div style="width:302px; height:480px; border:1px solid #c7d2e0; border-radius:14px; overflow:hidden; background:#fff; font-family:'Segoe UI',Arial,sans-serif; box-shadow:0 4px 20px rgba(0,0,0,0.08); display:flex; flex-direction:column;">
                                <!-- Blue Header -->
                                <div style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); padding:20px 20px 16px; text-align:center; position:relative;">
                                    <?php 
                                    $real_logo = $site_logo ?: SITE_URL.'/assets/images/logo.png';
                                    if (strpos($real_logo, 'http') !== 0 && strpos($real_logo, SITE_URL) === false) {
                                        $real_logo = SITE_URL . '/' . ltrim($real_logo, '/');
                                    }
                                    ?>
                                    <img src="<?= escape($real_logo) ?>" alt="Logo" style="display:block; margin:0 auto 6px; height:36px; object-fit:contain; filter:brightness(0) invert(1);">
                                    <div style="color:#93c5fd; font-size:10px; font-weight:800; letter-spacing:4px; text-transform:uppercase; margin-right:-4px;">Digital Media</div>
                                </div>

                                <!-- White Body -->
                                <div style="padding:18px 22px 16px; text-align:center;">
                                    <!-- Photo -->
                                    <div style="width:96px; height:128px; border-radius:12px; border:3px solid #2563eb; margin:-64px auto 12px; overflow:hidden; background:#f1f5f9; position:relative; z-index:10;">
                                        <img src="" id="card-photo" style="width:100%; height:100%; object-fit:cover;" alt="">
                                    </div>

                                    <div id="card-name" style="font-size:20px; font-weight:900; color:#111827; text-transform:uppercase; letter-spacing:1px; line-height:1.2; margin-bottom:4px;">JOHN DOE</div>
                                    <div id="card-role" style="font-size:12px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:2px; margin-bottom:16px;">EDITOR</div>

                                    <div style="height:1px; background:#e5e7eb; margin-bottom:14px;"></div>

                                    <!-- Info Row -->
                                    <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                                        <div style="text-align:left;">
                                            <div style="margin-bottom:8px;">
                                                <div style="font-size:8px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:2px; margin-bottom:1px;">Emp ID</div>
                                                <div id="card-empno" style="font-size:14px; font-weight:800; color:#1e3a5f;">ALP-0001</div>
                                            </div>
                                            <div style="margin-bottom:8px;">
                                                <div style="font-size:8px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:2px; margin-bottom:1px;">Blood Group</div>
                                                <div id="card-bg" style="font-size:14px; font-weight:800; color:#dc2626;">O+</div>
                                            </div>
                                            <div>
                                                <div style="font-size:8px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:2px; margin-bottom:1px;">Contact</div>
                                                <div id="card-phone" style="font-size:13px; font-weight:800; color:#1e3a5f;">N/A</div>
                                            </div>
                                        </div>
                                        <div style="padding:3px; border:1px solid #e5e7eb; border-radius:8px; background:#fff;">
                                            <div id="card-qr"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Blue Footer -->
                                <div style="background:#1e3a5f; padding:8px 16px; text-align:center; margin-top:auto;">
                                    <div style="font-size:9px; color:#93c5fd; font-weight:600; letter-spacing:1px;">www.alokpat.in</div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== BACK SIDE ===== -->
                        <div id="card-back" class="absolute inset-0 transition-opacity duration-300 opacity-0 pointer-events-none" style="width:302px; height:480px;">
                            <div style="width:302px; height:480px; border:1px solid #c7d2e0; border-radius:14px; overflow:hidden; background:#fff; font-family:'Segoe UI',Arial,sans-serif; box-shadow:0 4px 20px rgba(0,0,0,0.08); display:flex; flex-direction:column;">
                                <!-- Blue Top Bar -->
                                <div style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%); padding:20px; text-align:center;">
                                    <img src="<?= escape($real_logo) ?>" alt="Logo" style="display:block; margin:0 auto 4px; height:32px; object-fit:contain; filter:brightness(0) invert(1);">
                                    <div style="color:#93c5fd; font-size:10px; font-weight:800; letter-spacing:4px; text-transform:uppercase; margin-right:-4px;">Digital Media</div>
                                </div>

                                <!-- Content -->
                                <div style="flex:1; padding:24px 22px; display:flex; flex-direction:column; align-items:center; justify-content:center; position:relative;">
                                    <!-- Watermark -->
                                    <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; opacity:0.04; pointer-events:none;">
                                        <img src="<?= escape($real_logo) ?>" style="width:160px; object-fit:contain;">
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

                                <!-- Footer -->
                                <div style="background:#1e3a5f; padding:10px 16px; text-align:center;">
                                    <p style="font-size:8px; color:#93c5fd; font-weight:600; letter-spacing:1px; margin:0;">FOR ORGANIZATIONAL USE ONLY</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100 bg-white">
                <button type="button" onclick="closeModal()" class="text-gray-500 hover:text-gray-700 font-medium text-sm transition-colors">Close</button>
                <button type="button" onclick="printCard()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-sm px-5 py-2.5 shadow-sm transition-colors flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i> Print / Save PDF
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        /* Hide everything except the card */
        body * { visibility: hidden !important; }
        
        #printable-card, #printable-card * { visibility: visible !important; }
        
        #printable-card {
            position: fixed !important;
            left: 50% !important;
            top: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: auto !important;
            height: auto !important;
        }

        /* Show both sides for dual-sided printing */
        #card-front, #card-back {
            position: relative !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            display: block !important;
            margin: 0 auto 20px !important;
        }

        /* Cutout guide - dashed border */
        #card-front > div, #card-back > div {
            border: 2px dashed #999 !important;
            box-shadow: none !important;
        }

        /* Print colors */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Page settings */
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let isFlipped = false;
    const SITE_URL = '<?= SITE_URL ?>';
    let qrcode = null;
    const modal = document.getElementById('idCardModal');

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

    function closeModal() {
        modal.classList.add('hidden');
        isFlipped = false;
        document.getElementById('card-front').classList.remove('opacity-0', 'pointer-events-none');
        document.getElementById('card-back').classList.add('opacity-0', 'pointer-events-none');
        document.getElementById('flipText').textContent = 'Show Back Side';
    }

    document.querySelectorAll('.view-card-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.dataset;
            
            document.getElementById('card-name').textContent = data.name;
            document.getElementById('card-role').textContent = data.role;
            document.getElementById('card-empno').textContent = data.empno;
            document.getElementById('card-bg').textContent = data.bg;
            document.getElementById('card-phone').textContent = data.phone;
            document.getElementById('card-photo').src = data.img;

            // Generate QR Code
            const qrContainer = document.getElementById('card-qr');
            qrContainer.innerHTML = '';
            
            const qrUrl = `${SITE_URL}/author.php?id=${data.id}&name=${encodeURIComponent(data.name)}`;
            
            qrcode = new QRCode(qrContainer, {
                text: qrUrl,
                width: 64,
                height: 64,
                colorDark: "#1e3a5f",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });

            // Show Modal
            modal.classList.remove('hidden');
        });
    });

    // Close modal on backdrop click
    modal.addEventListener('click', function(e) {
        if (e.target === modal || e.target === modal.firstElementChild) {
            closeModal();
        }
    });

    function printCard() {
        window.print();
    }
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
