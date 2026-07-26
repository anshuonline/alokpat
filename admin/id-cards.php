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

                $updateStmt = $db->prepare("UPDATE users SET employee_number = ?, id_card_role = ?, id_card_generated = 1 WHERE id = ?");
                if ($updateStmt->execute([$employee_number, $role, $user_id])) {
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
$stmt = $db->prepare("SELECT id, full_name, email, avatar, phone, employee_number, id_card_role FROM users WHERE id_card_generated = 1");
$stmt->execute();
$cards = $stmt->fetchAll();

$roles = [
    'writer' => '✍️ Writer (লেখক)',
    'ground_reporter' => '📹 Ground Reporter (মাঠ প্রতিবেদক)',
    'correspondent' => '📰 Correspondent (সংবাদদাতা)',
    'editor' => '✏️ Editor (সম্পাদক)',
    'photojournalist' => '📷 Photo সাংবাদিক (Photojournalist)',
    'digital_creator' => '💻 Digital Content Creator (ডিজিটাল কন্টেন্ট ক্রিয়েটর)',
    'sub_editor' => '📝 Sub Editor (উপ-সম্পাদক)',
    'bureau_chief' => '🏢 Bureau Chief (ব্যুরো চীফ)'
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

<div class="container-fluid py-4">
    <div class="row">
        <!-- Generate Form -->
        <div class="col-lg-4 mb-4">
            <div class="premium-card p-4">
                <h4 class="mb-4 text-gray-800 font-weight-bold">
                    <i class="fas fa-id-card-alt text-indigo-500 mr-2"></i>Generate ID Card
                </h4>
                
                <?php if ($success_msg): ?>
                    <div class="alert alert-success border-0 bg-green-100 text-green-800 rounded-lg"><i class="fas fa-check-circle mr-2"></i><?= escape($success_msg) ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger border-0 bg-red-100 text-red-800 rounded-lg"><i class="fas fa-exclamation-circle mr-2"></i><?= escape($error_msg) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="generate">
                    
                    <div class="form-group mb-4">
                        <label class="text-sm font-weight-bold text-gray-600 mb-2">Select Staff</label>
                        <select name="user_id" class="premium-input" required>
                            <option value="">-- Choose User --</option>
                            <?php foreach ($users_without_card as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= escape($u['full_name']) ?> (<?= escape($u['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label class="text-sm font-weight-bold text-gray-600 mb-2">ID Card Role</label>
                        <select name="id_card_role" class="premium-input" required>
                            <option value="">-- Choose Role --</option>
                            <?php foreach ($roles as $key => $label): ?>
                                <option value="<?= $key ?>"><?= escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-generate w-100 mt-2">
                        <i class="fas fa-magic mr-2"></i> Generate ID Card
                    </button>
                </form>
            </div>
        </div>

        <!-- Generated Cards List -->
        <div class="col-lg-8">
            <div class="premium-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="m-0 text-gray-800 font-weight-bold">
                        <i class="fas fa-users text-indigo-500 mr-2"></i>Generated Cards
                    </h4>
                    <span class="badge bg-indigo-100 text-indigo-800 px-3 py-2 rounded-pill">Total: <?= count($cards) ?></span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-gray-50 text-gray-600 text-xs text-uppercase font-weight-bold">
                            <tr>
                                <th class="py-3 px-4 border-0 rounded-tl-lg">Employee</th>
                                <th class="py-3 px-4 border-0">Emp No.</th>
                                <th class="py-3 px-4 border-0">Role</th>
                                <th class="py-3 px-4 border-0 rounded-tr-lg text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (empty($cards)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-gray-500">
                                    <i class="fas fa-id-card fa-3x mb-3 opacity-25"></i>
                                    <p class="mb-0">No ID cards generated yet.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($cards as $card): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 px-4">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $img = !empty($card['avatar']) ? SITE_URL . '/' . $card['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($card['full_name']).'&background=random';
                                            ?>
                                            <img src="<?= escape($img) ?>" class="rounded-circle mr-3 object-cover" width="40" height="40" alt="">
                                            <div>
                                                <div class="font-weight-bold text-gray-800"><?= escape($card['full_name']) ?></div>
                                                <div class="text-xs text-gray-500"><?= escape($card['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-weight-semibold text-indigo-600">
                                        <?= escape($card['employee_number']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600">
                                        <span class="badge bg-blue-50 text-blue-700 border border-blue-100 p-2">
                                            <?= escape(isset($roles[$card['id_card_role']]) ? explode(' (', $roles[$card['id_card_role']])[0] : $card['id_card_role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <button type="button" 
                                                class="btn btn-sm btn-info text-white mr-1 shadow-sm rounded-lg view-card-btn"
                                                data-id="<?= $card['id'] ?>"
                                                data-name="<?= escape($card['full_name']) ?>"
                                                data-role="<?= escape(isset($roles[$card['id_card_role']]) ? $roles[$card['id_card_role']] : $card['id_card_role']) ?>"
                                                data-empno="<?= escape($card['employee_number']) ?>"
                                                data-phone="<?= escape($card['phone'] ?? 'N/A') ?>"
                                                data-img="<?= escape($img) ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to revoke this ID card?');">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="user_id" value="<?= $card['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger shadow-sm rounded-lg">
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
<div class="modal fade" id="idCardModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 rounded-2xl shadow-xl overflow-hidden bg-transparent">
            <div class="modal-body p-0 d-flex justify-content-center bg-gray-100 py-5">
                
                <!-- Printable ID Card Area -->
                <div id="printable-card" class="print-area">
                    <div class="id-card-wrapper">
                        <div class="id-card-header">
                            <div class="id-card-logo-area">
                                <?php if ($site_logo): ?>
                                    <img src="<?= SITE_URL . '/' . $site_logo ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-newspaper fa-lg"></i>
                                <?php endif; ?>
                                <h2>DIGITAL MEDIA</h2>
                            </div>
                            <p><?= escape($site_name) ?></p>
                        </div>
                        
                        <div class="id-card-body">
                            <div class="id-photo-container">
                                <img src="" id="card-photo" class="id-photo" alt="Employee Photo">
                            </div>
                            
                            <h3 class="id-name" id="card-name">John Doe</h3>
                            <div class="id-role" id="card-role">Editor (সম্পাদক)</div>
                            
                            <div class="id-details">
                                <div class="id-detail-row">
                                    <span class="id-detail-label">EMP NO:</span>
                                    <span class="id-detail-value" id="card-empno">ALP-0000</span>
                                </div>
                                <div class="id-detail-row">
                                    <span class="id-detail-label">PHONE:</span>
                                    <span class="id-detail-value" id="card-phone">N/A</span>
                                </div>
                            </div>
                            
                            <div class="id-qr-section">
                                <div id="card-qr" class="id-qr-code"></div>
                            </div>
                        </div>
                        
                        <div class="id-footer">
                            <p class="id-disclaimer mb-1">
                                এই আইডি কার্ডটি শুধুমাত্র সাংগঠনিক ব্যবহারের জন্য। এটি কোনো সরকারি বা আইনি নথি নয়।
                            </p>
                            <p class="id-disclaimer">
                                This ID card is for organizational use only. It is not a legally registered credential.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white border-top-0 d-flex justify-content-between p-4">
                <button type="button" class="btn btn-light rounded-lg px-4" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary rounded-lg px-4 shadow-sm" onclick="printCard()">
                    <i class="fas fa-print mr-2"></i>Print Card
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    const SITE_URL = '<?= SITE_URL ?>';
    let qrcode = null;

    document.querySelectorAll('.view-card-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = this.dataset;
            
            document.getElementById('card-name').textContent = data.name;
            document.getElementById('card-role').textContent = data.role.replace(/^[^\s]+\s/, ''); // Remove emoji for cleaner look or keep it. We'll keep full text
            document.getElementById('card-role').textContent = data.role;
            document.getElementById('card-empno').textContent = data.empno;
            document.getElementById('card-phone').textContent = data.phone;
            document.getElementById('card-photo').src = data.img;

            // Generate QR Code
            const qrContainer = document.getElementById('card-qr');
            qrContainer.innerHTML = ''; // Clear previous
            
            const qrUrl = `${SITE_URL}/author.php?id=${data.id}&name=${encodeURIComponent(data.name)}`;
            
            qrcode = new QRCode(qrContainer, {
                text: qrUrl,
                width: 80,
                height: 80,
                colorDark : "#111827",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // Show Modal
            $('#idCardModal').modal('show');
        });
    });

    function printCard() {
        window.print();
    }
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
