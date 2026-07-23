<?php
/**
 * User Management Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();
requirePermission('manage_users');

$userModel = new User();
$db = (new Database())->getConnection();
$rolesStmt = $db->query("SELECT slug, name FROM roles ORDER BY id ASC");
$allRoles = $rolesStmt->fetchAll();

$currentUserRole = getCurrentUser()['role'];
$role_ranks = [
    'super_admin' => 100,
    'admin' => 80,
    'editor' => 50,
    'writer' => 20
];
$currentUserRank = $role_ranks[$currentUserRole] ?? 0;

// Handle Delete
if (isset($_GET['delete'])) {
    requireCSRF();
    $id = (int)$_GET['delete'];
    
    // Prevent self-deletion
    if ($id === $_SESSION['user_id']) {
        setFlash('error', 'আপনি নিজের অ্যাকাউন্ট মুছতে পারবেন না');
    } else {
        $userToDelete = $userModel->getById($id);
        $targetRank = $userToDelete ? ($role_ranks[$userToDelete['role']] ?? 0) : 0;
        if ($currentUserRole !== 'super_admin' && $targetRank >= $currentUserRank) {
            setFlash('error', 'আপনার সমান বা উপরের রোলের ইউজার মুছতে পারবেন না');
        } else if ($userModel->delete($id)) {
            if (function_exists('clear_page_caches')) clear_page_caches();
            setFlash('success', 'ব্যবহারকারী সফলভাবে মুছে ফেলা হয়েছে');
        } else {
            setFlash('error', 'ব্যবহারকারী মুছতে সমস্যা হয়েছে');
        }
    }
    redirect(ADMIN_URL . '/users.php');
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $is_update = isset($_POST['update_id']) && !empty($_POST['update_id']);
    
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'role' => $_POST['role'] ?? 'writer',
        'status' => isset($_POST['status']) ? 'active' : 'inactive',
        'bio' => $_POST['bio'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'facebook_url' => $_POST['facebook_url'] ?? '',
        'twitter_url' => $_POST['twitter_url'] ?? '',
        'youtube_url' => $_POST['youtube_url'] ?? '',
    ];

    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    } elseif (!$is_update) {
        $data['password'] = '123456'; // Default password if not provided on create
    }
    
    // Check if username already exists
    $existingUser = $userModel->getByUsername($data['username']);
    $usernameExists = false;
    
    if ($existingUser) {
        if (!$is_update) {
            $usernameExists = true;
        } else if ($existingUser['id'] != $_POST['update_id']) {
            $usernameExists = true;
        }
    }
    
    if ($usernameExists) {
        setFlash('error', 'এই ইউজারনেমটি ইতিমধ্যে ব্যবহৃত হচ্ছে। অনুগ্রহ করে অন্য একটি ইউজারনেম নির্বাচন করুন।');
        redirect(ADMIN_URL . '/users.php');
        exit;
    }
    
    // Handle Avatar Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['avatar'], 'uploads/users');
        if (isset($upload['error'])) {
            setFlash('error', $upload['error']);
            redirect(ADMIN_URL . '/users.php');
        } else {
            $data['avatar'] = $upload['file_url'];
        }
    } else {
        if ($is_update && isset($_POST['existing_avatar'])) {
            $data['avatar'] = $_POST['existing_avatar'];
        }
    }

    $targetAssignRank = $role_ranks[$data['role']] ?? 0;
    
    // Prevent assigning role >= own rank (except super_admin)
    if ($currentUserRole !== 'super_admin' && $targetAssignRank >= $currentUserRank) {
        setFlash('error', 'আপনার সমান বা উপরের রোল দেওয়ার অনুমতি নেই');
        redirect(ADMIN_URL . '/users.php');
        exit;
    }

    if ($is_update) {
        $id = (int)$_POST['update_id'];
        
        // Prevent changing own role (unless super_admin)
        if ($id === $_SESSION['user_id'] && $data['role'] !== $currentUserRole && $currentUserRole !== 'super_admin') {
            setFlash('error', 'আপনি নিজের রোল পরিবর্তন করতে পারবেন না');
            redirect(ADMIN_URL . '/users.php');
            exit;
        }
        
        $userToEdit = $userModel->getById($id);
        $targetEditRank = $userToEdit ? ($role_ranks[$userToEdit['role']] ?? 0) : 0;
        
        if ($currentUserRole !== 'super_admin' && $targetEditRank >= $currentUserRank) {
            setFlash('error', 'আপনার সমান বা উপরের রোলের ইউজার সম্পাদনা করার অনুমতি নেই');
            redirect(ADMIN_URL . '/users.php');
            exit;
        }
        
        if ($id === $_SESSION['user_id'] && $userToEdit['role'] === 'super_admin' && $data['role'] !== 'super_admin') {
            setFlash('error', 'আপনি নিজের সুপার এডমিন রোল পরিবর্তন করতে পারবেন না');
            redirect(ADMIN_URL . '/users.php');
            exit;
        }

        if ($userModel->update($id, $data)) {
            if (function_exists('clear_page_caches')) clear_page_caches();
            setFlash('success', 'ব্যবহারকারীর তথ্য আপডেট করা হয়েছে');
        } else {
            setFlash('error', 'ব্যবহারকারীর তথ্য আপডেট করতে সমস্যা হয়েছে');
        }
    } else {
        if ($userModel->create($data)) {
            if (function_exists('clear_page_caches')) clear_page_caches();
            setFlash('success', 'নতুন ব্যবহারকারী সফলভাবে যোগ করা হয়েছে');
        } else {
            setFlash('error', 'নতুন ব্যবহারকারী যোগ করতে সমস্যা হয়েছে');
        }
    }
    
    redirect(ADMIN_URL . '/users.php');
}

$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$usersList = $userModel->getAll($limit, $offset);
$total = $userModel->getCount();
$total_pages = ceil($total / $limit);

$page_title = 'ব্যবহারকারী (Users) ব্যবস্থাপনা';

ob_start();
?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">ব্যবহারকারী ব্যবস্থাপনা</h2>
        <button onclick="openAddModal()" 
                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-sm">
            <i class="fas fa-user-plus mr-2"></i>
            নতুন ব্যবহারকারী
        </button>
    </div>
    
    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ছবি</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">নাম ও ইউজারনেম</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ভূমিকা (Role)</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">স্ট্যাটাস</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">যোগদান</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">কাজ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                কোনো ব্যবহারকারী পাওয়া যায়নি
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usersList as $u): ?>
                            <tr class="hover:bg-blue-50/50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center border border-gray-300 shadow-sm">
                                        <?php if (!empty($u['avatar'])): ?>
                                            <img src="<?php echo escape($u['avatar']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-gray-400 text-xl"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900"><?php echo escape($u['full_name']); ?></div>
                                    <div class="text-sm text-gray-500">@<?php echo escape($u['username']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($u['role'] === 'super_admin'): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 text-xs font-bold rounded-full">Super Admin</span>
                                    <?php elseif ($u['role'] === 'admin'): ?>
                                        <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full">Admin</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full"><?php echo ucfirst(str_replace('_', ' ', $u['role'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">সক্রিয়</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">নিষ্ক্রিয়</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d M, Y', strtotime($u['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex space-x-3">
                                        <?php 
                                        $targetRank = $role_ranks[$u['role']] ?? 0;
                                        $canEdit = ($currentUserRole === 'super_admin' || $targetRank < $currentUserRank); 
                                        ?>
                                        <?php if ($canEdit): ?>
                                            <button onclick="editUser(<?php echo htmlspecialchars((string)json_encode($u, JSON_INVALID_UTF8_IGNORE)); ?>)" 
                                                    class="text-blue-500 hover:text-blue-800 transition" title="সম্পাদনা">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($u['id'] !== $_SESSION['user_id'] && $canEdit): ?>
                                            <a href="?delete=<?php echo $u['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                               class="text-red-500 hover:text-red-800 transition delete-confirm" title="মুছুন">
                                                <i class="fas fa-trash-alt text-lg"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="px-4 py-2 <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white border hover:bg-gray-50'; ?> rounded-lg">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
    
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="p-6 border-b bg-gray-50 rounded-t-xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold text-gray-800" id="modalTitle">নতুন ব্যবহারকারী</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-red-600 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="update_id" id="update_id" value="">
            <input type="hidden" name="existing_avatar" id="existing_avatar" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">পূর্ণ নাম (Full Name) <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" id="full_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ইউজারনেম (Username) <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="e.g. john_doe">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ইমেইল (Email)</label>
                    <input type="email" name="email" id="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">পাসওয়ার্ড (Password) <span class="text-xs text-gray-500 font-normal" id="passwordHint">(নতুন তৈরি করলে আবশ্যিক নয়, ফাঁকা রাখলে '123456' হবে)</span></label>
                    <input type="text" name="password" id="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="Leave empty to keep existing (if editing)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ভূমিকা (Role) <span class="text-red-500">*</span></label>
                    <select name="role" id="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                        <?php foreach($allRoles as $r): ?>
                            <?php 
                            $rRank = $role_ranks[$r['slug']] ?? 0;
                            if ($currentUserRole !== 'super_admin' && $rRank >= $currentUserRank) continue; 
                            ?>
                            <option value="<?php echo escape($r['slug']); ?>"><?php echo escape($r['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ফোন নম্বর (Phone)</label>
                    <input type="text" name="phone" id="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ছবি (Avatar)</label>
                <input type="file" name="avatar" id="avatar" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <div id="currentAvatarPreview" class="mt-3 hidden">
                    <p class="text-xs text-gray-500 mb-1">বর্তমান ছবি:</p>
                    <img src="" id="avatarImage" class="w-16 h-16 rounded-full object-cover border shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">লেখকের পরিচিতি (Bio)</label>
                <textarea name="bio" id="bio" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="Write something about the author..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ফেসবুক ইউআরএল</label>
                    <input type="url" name="facebook_url" id="facebook_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="https://facebook.com/...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">টুইটার/X ইউআরএল</label>
                    <input type="url" name="twitter_url" id="twitter_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="https://x.com/...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ইউটিউব ইউআরএল</label>
                    <input type="url" name="youtube_url" id="youtube_url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="https://youtube.com/...">
                </div>
            </div>
            
            <div class="flex items-center mt-4 p-4 bg-gray-50 rounded-lg">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="status" id="status" value="active" checked class="form-checkbox h-5 w-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <span class="ml-3 text-gray-700 font-medium">অ্যাকাউন্ট সক্রিয় (Active) রাখুন</span>
                </label>
            </div>
            
            <div class="flex space-x-3 pt-6 border-t">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-sm">
                    <i class="fas fa-save mr-2"></i> সংরক্ষণ করুন
                </button>
                <button type="button" onclick="closeModal()" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-bold hover:bg-gray-300 transition">
                    বাতিল
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'নতুন ব্যবহারকারী';
        document.getElementById('update_id').value = '';
        document.getElementById('existing_avatar').value = '';
        document.getElementById('full_name').value = '';
        document.getElementById('username').value = '';
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
        document.getElementById('role').value = 'writer';
        document.getElementById('phone').value = '';
        document.getElementById('bio').value = '';
        document.getElementById('facebook_url').value = '';
        document.getElementById('twitter_url').value = '';
        document.getElementById('youtube_url').value = '';
        document.getElementById('status').checked = true;
        document.getElementById('passwordHint').innerText = "(নতুন তৈরি করলে আবশ্যিক নয়, ফাঁকা রাখলে '123456' হবে)";
        document.getElementById('currentAvatarPreview').classList.add('hidden');
        document.getElementById('userModal').classList.remove('hidden');
    }

    function editUser(user) {
        document.getElementById('modalTitle').innerText = 'ব্যবহারকারী সম্পাদনা';
        document.getElementById('update_id').value = user.id;
        document.getElementById('existing_avatar').value = user.avatar ? user.avatar : '';
        document.getElementById('full_name').value = user.full_name;
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email ? user.email : '';
        document.getElementById('password').value = '';
        document.getElementById('role').value = user.role;
        document.getElementById('phone').value = user.phone ? user.phone : '';
        document.getElementById('bio').value = user.bio ? user.bio : '';
        document.getElementById('facebook_url').value = user.facebook_url ? user.facebook_url : '';
        document.getElementById('twitter_url').value = user.twitter_url ? user.twitter_url : '';
        document.getElementById('youtube_url').value = user.youtube_url ? user.youtube_url : '';
        document.getElementById('status').checked = user.status === 'active';
        document.getElementById('passwordHint').innerText = "(ফাঁকা রাখলে বর্তমান পাসওয়ার্ডই থাকবে)";
        
        if (user.id == <?php echo $_SESSION['user_id']; ?> && '<?php echo $currentUserRole; ?>' !== 'super_admin') {
            document.getElementById('role').setAttribute('disabled', 'disabled');
            // Add hidden input so role is still submitted
            if (!document.getElementById('hidden_role')) {
                let hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'role';
                hidden.id = 'hidden_role';
                document.getElementById('role').parentNode.appendChild(hidden);
            }
            document.getElementById('hidden_role').value = user.role;
        } else {
            document.getElementById('role').removeAttribute('disabled');
            if (document.getElementById('hidden_role')) {
                document.getElementById('hidden_role').remove();
            }
        }
        
        if (user.avatar) {
            document.getElementById('avatarImage').src = user.avatar;
            document.getElementById('currentAvatarPreview').classList.remove('hidden');
        } else {
            document.getElementById('currentAvatarPreview').classList.add('hidden');
        }
        
        document.getElementById('userModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('userModal').classList.add('hidden');
    }

    document.querySelectorAll('.delete-confirm').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('আপনি কি নিশ্চিত এই ব্যবহারকারীকে মুছে ফেলতে চান? এটি ফিরিয়ে আনা সম্ভব নয়।')) {
                e.preventDefault();
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
