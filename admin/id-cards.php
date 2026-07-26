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

<!-- ID Card Preview Modal (Tailwind Base) -->
<div id="idCardModal" class="fixed inset-0 z-[100] hidden items-center justify-center overflow-y-auto overflow-x-hidden bg-black bg-opacity-75 transition-opacity backdrop-blur-sm p-4">
    <div class="relative w-full max-w-lg flex flex-col items-center justify-center">
        <!-- Modal control -->
        <div class="mb-4 flex justify-between w-full max-w-[340px]">
            <button type="button" onclick="closeModal()" class="text-white bg-gray-600 hover:bg-gray-500 rounded-lg px-4 py-2 shadow-sm font-bold transition-colors">
                <i class="fas fa-times mr-2"></i> Close
            </button>
            <button type="button" onclick="flipCard()" class="text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg px-4 py-2 shadow-sm font-bold transition-colors">
                <i class="fas fa-sync-alt mr-2"></i> Flip Card
            </button>
        </div>
        
        <!-- Modal content (Flip Container) -->
        <div class="flip-container  w-[320px] h-[480px]">
            <div id="printable-card" class="flipper print-area w-full h-full relative transition-opacity duration-300">
                
                <!-- FRONT SIDE -->
                <div class="id-card-wrapper front-side bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-200 relative w-full h-full font-sans absolute top-0 left-0 w-full h-full transition-opacity duration-300">
                    <!-- Card Header -->
                    <div class="bg-white px-4 pt-6 pb-4 flex flex-col items-center border-b-[4px] border-indigo-700">
                        <?php 
                        $real_logo = $site_logo ?: SITE_URL.'/assets/images/logo.png';
                        if (strpos($real_logo, 'http') !== 0 && strpos($real_logo, SITE_URL) === false) {
                            $real_logo = SITE_URL . '/' . ltrim($real_logo, '/');
                        }
                        ?>
                        <img src="<?= escape($real_logo) ?>" alt="Logo" class="h-10 object-contain mb-2">
                        <div class="text-[11px] font-black tracking-[0.25em] text-indigo-800 uppercase">Digital Media</div>
                    </div>

                    <!-- Card Body -->
                    <div class="px-6 py-6 flex flex-col items-center bg-gradient-to-b from-white to-gray-50 flex-1">
                        <!-- Avatar -->
                        <div class="w-28 h-28 rounded-xl shadow-md border border-gray-200 p-1 mb-4 bg-white flex items-center justify-center overflow-hidden shrink-0 z-10">
                            <img src="" id="card-photo" class="w-full h-full object-cover rounded-xl" alt="Employee Photo">
                        </div>
                        
                        <h3 class="text-[22px] font-black text-gray-900 mb-1 flex items-center justify-center text-center leading-tight uppercase tracking-wide" id="card-name">JOHN DOE</h3>
                        <div class="text-[13px] font-extrabold text-gray-800 uppercase tracking-widest mb-4 text-center" id="card-role">EDITOR</div>
                        
                        <div class="w-full h-px bg-gray-200 mb-4"></div>
                        
                        <!-- Info & QR -->
                        <div class="w-full flex justify-between items-center gap-2">
                            <div class="space-y-2 flex-1">
                                <div>
                                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-0.5">Emp ID</span>
                                    <span class="font-bold text-gray-800 text-sm leading-none" id="card-empno">ALP-0000</span>
                                </div>
                                <div>
                                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-0.5">Blood Group</span>
                                    <span class="font-bold text-red-600 text-sm leading-none" id="card-bg">O+</span>
                                </div>
                                <div>
                                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-0.5">Contact</span>
                                    <span class="font-bold text-gray-800 text-[13px] leading-none" id="card-phone">N/A</span>
                                </div>
                            </div>
                            
                            <div class="p-1 bg-white border border-gray-200 rounded-lg shadow-sm shrink-0">
                                <div id="card-qr"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BACK SIDE -->
                <div class="id-card-wrapper back-side bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-200 relative w-full h-full font-sans absolute top-0 left-0 w-full h-full transition-opacity duration-300 rotate-y-180 flex flex-col">
                    <div class="h-2 bg-indigo-700 w-full"></div>
                    
                    <div class="flex-1 flex flex-col items-center justify-center p-6 relative">
                        <!-- Watermark -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-5 pointer-events-none">
                            <img src="<?= escape($real_logo) ?>" class="w-48 object-contain filter grayscale">
                        </div>
                        
                        <div class="z-10 w-full text-center space-y-6">
                            <div>
                                <h4 class="text-[11px] font-black text-gray-900 uppercase tracking-widest mb-1">Terms & Conditions</h4>
                                <p class="text-[9px] text-gray-600 leading-relaxed font-medium">
                                    This card is the property of Alokpat Digital Media. It is for organizational identification only and is not a legally registered credential.
                                    <br><br>
                                    If found, please drop it in the nearest mailbox or return to the address below.
                                </p>
                            </div>
                            
                            <div class="w-12 h-px bg-indigo-200 mx-auto"></div>
                            
                            <div>
                                <h4 class="text-[10px] font-black text-gray-900 uppercase tracking-widest mb-1">Return Address</h4>
                                <p class="text-[9px] text-gray-600 leading-relaxed font-medium">
                                    Alokpat Digital Media Office<br>
                                    Kolkata, West Bengal<br>
                                    Email: contact@alokpat.in<br>
                                    Web: www.alokpat.in
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="bg-indigo-900 px-4 py-4 text-center">
                        <p class="text-[9px] font-medium text-indigo-100 leading-tight mb-1">এই আইডি কার্ডটি শুধুমাত্র সাংগঠনিক ব্যবহারের জন্য।</p>
                        <p class="text-[8px] font-black text-indigo-300 leading-tight tracking-[0.2em] uppercase">Organizational Use Only</p>
                    </div>
                </div>

            </div>
        </div>
        
        <div class="mt-6 flex justify-center w-full">
            <button type="button" onclick="printCard()" class="text-white bg-indigo-600 hover:bg-indigo-500 font-bold rounded-xl text-base px-8 py-3 shadow-lg transition-colors flex items-center">
                <i class="fas fa-print mr-2"></i> Print Dual-Sided
            </button>
        </div>
    </div>
</div>

<style>
    . { perspective: 1000px; }
    .preserve-3d { transform-style: preserve-3d; }
    .backface-hidden { backface-visibility: hidden; -webkit-backface-visibility: hidden; }
    
    
    /* Print Styles */
    @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        
        /* Layout front and back side-by-side for printing */
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            transform: none !important;
            width: 100% !important;
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        
        .front-side, .back-side {
            position: relative !important;
            transform: none !important;
            width: 320px !important;
            height: 480px !important;
            page-break-inside: avoid;
            box-shadow: none !important;
            border: 1px solid #ccc !important;
        }

        /* Ensure background colors print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>
<!-- Modal footer -->
            <div class="flex items-center justify-between p-4 bg-white border-t border-gray-200 rounded-b-2xl">
                <button type="button" onclick="closeModal()" class="text-gray-600 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center transition-colors">Close</button>
                <button type="button" onclick="printCard()" class="text-white bg-indigo-600 hover:bg-indigo-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center shadow-sm transition-colors flex items-center">
                    <i class="fas fa-print mr-2"></i> Print Card
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-card, #printable-card * {
            visibility: visible;
        }
        #printable-card {
            position: absolute;
            left: 50%;
            top: 20px;
            transform: translateX(-50%);
            width: 320px !important;
        }
        .id-card-wrapper {
            box-shadow: none !important;
            border: 1px solid #ddd;
        }
        /* Ensure background colors print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const SITE_URL = '<?= SITE_URL ?>';
    let qrcode = null;
    const modal = document.getElementById('idCardModal');
    let isFlipped = false;
    
    function flipCard() {
        const front = document.querySelector(".front-side");
        const back = document.querySelector(".back-side");
        isFlipped = !isFlipped;
        if(isFlipped) {
            front.classList.add("opacity-0", "pointer-events-none");
            back.classList.remove("opacity-0", "pointer-events-none");
        } else {
            front.classList.remove("opacity-0", "pointer-events-none");
            back.classList.add("opacity-0", "pointer-events-none");
        }
    } else {
            flipper.classList.remove('flip-active');
        }
    }

    function closeModal() {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
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
            qrContainer.innerHTML = ''; // Clear previous
            
            const qrUrl = `${SITE_URL}/author.php?id=${data.id}&name=${encodeURIComponent(data.name)}`;
            
            qrcode = new QRCode(qrContainer, {
                text: qrUrl,
                width: 68,
                height: 68,
                colorDark : "#111827",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // Show Modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal || e.target.closest('.w-full.max-w-md') === modal.firstElementChild && !e.target.closest('.bg-gray-100')) {
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