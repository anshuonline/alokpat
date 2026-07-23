<?php
/**
 * Role Management Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requirePermission('manage_roles'); // Ensure only authorized users can access

$db = (new Database())->getConnection();

// Handle Delete
if (isset($_GET['delete'])) {
    requireCSRF();
    $id = (int)$_GET['delete'];
    
    // Check if role is system role
    $stmt = $db->prepare("SELECT is_system FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    $role = $stmt->fetch();
    
    if ($role && $role['is_system'] == 1) {
        setFlash('error', 'সিস্টেম রোল (System Role) মোছা সম্ভব নয়।');
    } else {
        $stmt = $db->prepare("DELETE FROM roles WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlash('success', 'রোলটি সফলভাবে মুছে ফেলা হয়েছে।');
        } else {
            setFlash('error', 'রোল মুছতে সমস্যা হয়েছে।');
        }
    }
    redirect(ADMIN_URL . '/roles.php');
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $is_update = isset($_POST['update_id']) && !empty($_POST['update_id']);
    
    $name = trim($_POST['name'] ?? '');
    // Simple slug generator
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $name)));
    
    $submitted_permissions = $_POST['permissions'] ?? [];
    if (!is_array($submitted_permissions)) $submitted_permissions = [];
    
    $permissions_json = json_encode($submitted_permissions);
    
    if (empty($name)) {
        setFlash('error', 'রোলের নাম দেওয়া আবশ্যক।');
        redirect(ADMIN_URL . '/roles.php');
    }

    if ($is_update) {
        $id = (int)$_POST['update_id'];
        
        // Check if system role, prevent slug rename
        $stmt = $db->prepare("SELECT is_system FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch();
        
        if ($role && $role['is_system'] == 1) {
            $stmt = $db->prepare("UPDATE roles SET name = ?, permissions = ? WHERE id = ?");
            $success = $stmt->execute([$name, $permissions_json, $id]);
        } else {
            $stmt = $db->prepare("UPDATE roles SET name = ?, slug = ?, permissions = ? WHERE id = ?");
            $success = $stmt->execute([$name, $slug, $permissions_json, $id]);
        }
        
        if ($success) {
            setFlash('success', 'রোলটি সফলভাবে আপডেট করা হয়েছে।');
        } else {
            setFlash('error', 'আপডেট করতে সমস্যা হয়েছে।');
        }
    } else {
        // Create new role
        $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            setFlash('error', 'এই নামের একটি রোল ইতিমধ্যে আছে। অন্য নাম দিন।');
        } else {
            $stmt = $db->prepare("INSERT INTO roles (name, slug, permissions) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $slug, $permissions_json])) {
                setFlash('success', 'নতুন রোল সফলভাবে তৈরি করা হয়েছে।');
            } else {
                setFlash('error', 'রোল তৈরি করতে সমস্যা হয়েছে।');
            }
        }
    }
    
    redirect(ADMIN_URL . '/roles.php');
}

// Fetch all roles
$stmt = $db->query("SELECT * FROM roles ORDER BY id ASC");
$rolesList = $stmt->fetchAll();

// Fetch defined permissions
$permissionGroups = Permissions::getAll();

$page_title = 'রোল ও পারমিশন (Roles & Permissions)';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">রোল ও পারমিশন</h2>
            <p class="text-gray-500 mt-1">কে কী করতে পারবে তা কন্ট্রোল করুন (Discord-এর মতো)</p>
        </div>
        <button onclick="openAddModal()" 
                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-sm">
            <i class="fas fa-plus mr-2"></i>
            নতুন রোল তৈরি করুন
        </button>
    </div>
    
    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($rolesList as $role): 
            $perms = json_decode($role['permissions'], true) ?: [];
            $isWildcard = in_array('*', $perms);
        ?>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden flex flex-col">
            <div class="p-5 border-b flex justify-between items-center bg-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $role['is_system'] ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600'; ?>">
                        <i class="fas <?php echo $isWildcard ? 'fa-crown' : 'fa-user-shield'; ?>"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800"><?php echo escape($role['name']); ?></h3>
                        <span class="text-xs text-gray-500 font-mono">@<?php echo escape($role['slug']); ?></span>
                    </div>
                </div>
                <?php if ($role['is_system']): ?>
                    <span class="px-2 py-1 bg-gray-200 text-gray-600 text-xs font-bold rounded">সিস্টেম</span>
                <?php endif; ?>
            </div>
            
            <div class="p-5 flex-grow">
                <?php if ($isWildcard): ?>
                    <p class="text-purple-600 font-semibold"><i class="fas fa-star mr-1"></i> সমস্ত পারমিশন (All Permissions)</p>
                <?php else: ?>
                    <p class="text-sm text-gray-600 mb-2 font-semibold">পারমিশন সমুহ:</p>
                    <div class="flex flex-wrap gap-2">
                        <?php 
                        $showCount = 0;
                        foreach ($perms as $p) {
                            if ($showCount >= 5) break;
                            echo "<span class='px-2 py-1 bg-blue-50 text-blue-600 text-xs rounded border border-blue-100'>$p</span>";
                            $showCount++;
                        }
                        if (count($perms) > 5) {
                            echo "<span class='px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded border border-gray-200'>+" . (count($perms) - 5) . " more</span>";
                        }
                        if (empty($perms)) {
                            echo "<span class='text-gray-400 text-sm italic'>কোনো পারমিশন নেই</span>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="p-4 border-t bg-gray-50 flex justify-end space-x-3">
                <button onclick="editRole(<?php echo htmlspecialchars(json_encode($role)); ?>)" class="text-blue-600 hover:text-blue-800 font-medium text-sm transition flex items-center">
                    <i class="fas fa-edit mr-1"></i> এডিট
                </button>
                <?php if (!$role['is_system']): ?>
                    <a href="?delete=<?php echo $role['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                       class="text-red-500 hover:text-red-700 font-medium text-sm transition flex items-center delete-confirm">
                        <i class="fas fa-trash-alt mr-1"></i> মুছুন
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Role Modal -->
<div id="roleModal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl">
        <div class="p-6 border-b bg-gray-50 rounded-t-xl flex-shrink-0">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold text-gray-800" id="modalTitle">নতুন রোল তৈরি</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-red-600 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6 overflow-y-auto flex-grow">
            <form method="POST" id="roleForm" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="update_id" id="update_id" value="">
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">রোলের নাম (Role Name) <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" 
                           placeholder="e.g. Content Creator">
                    <p class="text-xs text-gray-500 mt-1" id="systemRoleNotice"></p>
                </div>

                <div class="border-t pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-bold text-gray-800">পারমিশন সমূহ (Permissions)</h4>
                        <label class="flex items-center space-x-2 text-sm text-blue-600 cursor-pointer select-none">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500" onchange="toggleAll(this)">
                            <span class="font-bold">সব সিলেক্ট করুন</span>
                        </label>
                    </div>
                    
                    <div id="wildcardNotice" class="hidden bg-purple-50 text-purple-700 p-4 rounded-lg mb-6 border border-purple-200">
                        <i class="fas fa-crown mr-2"></i> এটি একটি সুপার অ্যাডমিন রোল। এর সকল পারমিশন অটোমেটিক্যালি রয়েছে।
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8" id="permissionsGrid">
                        <?php foreach ($permissionGroups as $groupName => $permissions): ?>
                            <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                                <h5 class="font-bold text-gray-700 mb-4 pb-2 border-b uppercase text-sm tracking-wider"><?php echo $groupName; ?></h5>
                                <div class="space-y-3">
                                    <?php foreach ($permissions as $key => $label): ?>
                                        <label class="flex items-start space-x-3 cursor-pointer group">
                                            <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" 
                                                   class="perm-checkbox mt-1 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 transition">
                                            <span class="text-gray-700 group-hover:text-blue-600 transition font-medium text-sm"><?php echo $label; ?> <br><span class="text-xs font-mono text-gray-400"><?php echo $key; ?></span></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="p-6 border-t bg-gray-50 rounded-b-xl flex justify-end space-x-3 flex-shrink-0">
            <button onclick="closeModal()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-bold hover:bg-gray-100 transition">বাতিল</button>
            <button onclick="document.getElementById('roleForm').submit()" class="px-6 py-2 bg-blue-600 rounded-lg text-white font-bold hover:bg-blue-700 transition">সেভ করুন</button>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('roleForm').reset();
    document.getElementById('update_id').value = '';
    document.getElementById('modalTitle').innerText = 'নতুন রোল তৈরি';
    document.getElementById('systemRoleNotice').innerText = '';
    
    document.getElementById('wildcardNotice').classList.add('hidden');
    document.getElementById('permissionsGrid').classList.remove('opacity-50', 'pointer-events-none');
    
    document.getElementById('roleModal').classList.remove('hidden');
}

function editRole(role) {
    document.getElementById('roleForm').reset();
    document.getElementById('update_id').value = role.id;
    document.getElementById('name').value = role.name;
    document.getElementById('modalTitle').innerText = 'রোল সম্পাদনা';
    
    if (role.is_system == 1) {
        document.getElementById('systemRoleNotice').innerText = 'এটি একটি সিস্টেম রোল। এর নাম বা স্লাগ পরিবর্তন করলেও কোড ব্রেক হতে পারে, তাই সাবধানতা অবলম্বন করুন।';
    } else {
        document.getElementById('systemRoleNotice').innerText = '';
    }
    
    let perms = [];
    try { perms = JSON.parse(role.permissions) || []; } catch(e) {}
    
    if (perms.includes('*')) {
        document.getElementById('wildcardNotice').classList.remove('hidden');
        document.getElementById('permissionsGrid').classList.add('opacity-50', 'pointer-events-none');
        // Check all boxes visually
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = true);
    } else {
        document.getElementById('wildcardNotice').classList.add('hidden');
        document.getElementById('permissionsGrid').classList.remove('opacity-50', 'pointer-events-none');
        
        document.querySelectorAll('.perm-checkbox').forEach(cb => {
            cb.checked = perms.includes(cb.value);
        });
    }
    
    document.getElementById('roleModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('roleModal').classList.add('hidden');
}

function toggleAll(source) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = source.checked;
    });
}
</script>

<?php
$content = ob_get_clean();
require_once 'layouts/admin.php';
?>
