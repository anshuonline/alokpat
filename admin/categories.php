<?php
/**
 * Category Management Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

$category = new Category();

// Handle delete
if (isset($_GET['delete'])) {
    requireCSRF();
    $id = (int)$_GET['delete'];
    
    if ($category->delete($id)) {
        setFlash('success', 'ক্যাটাগরি সফলভাবে মুছে ফেলা হয়েছে');
    } else {
        setFlash('error', 'ক্যাটাগরি মুছতে সমস্যা হয়েছে');
    }
    redirect(ADMIN_URL . '/categories.php');
}
// Handle AJAX Reorder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder') {
    header('Content-Type: application/json');
    $order = $_POST['order'] ?? [];
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE categories SET display_order = :order WHERE id = :id");
    
    try {
        $db->beginTransaction();
        foreach ($order as $index => $id) {
            $stmt->execute([':order' => $index + 1, ':id' => (int)$id]);
        }
        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['update_id'])) {
    requireCSRF();
    
    $data = [
        'name' => $_POST['name'] ?? '',
        'name_en' => $_POST['name_en'] ?? '',
        'slug' => generateSlug($_POST['slug'] ?? $_POST['name'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'parent_id' => isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
        'display_order' => (int)($_POST['display_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'seo_title' => $_POST['seo_title'] ?? '',
        'seo_description' => $_POST['seo_description'] ?? '',
        'seo_keywords' => $_POST['seo_keywords'] ?? '',
    ];
    
    $id = $category->create($data);
    if ($id) {
        setFlash('success', 'ক্যাটাগরি সফলভাবে তৈরি হয়েছে');
        redirect(ADMIN_URL . '/categories.php');
    } else {
        setFlash('error', 'ক্যাটাগরি তৈরিতে সমস্যা হয়েছে');
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    requireCSRF();
    $id = (int)$_POST['update_id'];
    
    $data = [
        'name' => $_POST['name'] ?? '',
        'name_en' => $_POST['name_en'] ?? '',
        'slug' => generateSlug($_POST['slug'] ?? $_POST['name'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'parent_id' => isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
        'display_order' => (int)($_POST['display_order'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'seo_title' => $_POST['seo_title'] ?? '',
        'seo_description' => $_POST['seo_description'] ?? '',
        'seo_keywords' => $_POST['seo_keywords'] ?? '',
    ];
    
    if ($category->update($id, $data)) {
        setFlash('success', 'ক্যাটাগরি সফলভাবে আপডেট করা হয়েছে');
        redirect(ADMIN_URL . '/categories.php');
    } else {
        setFlash('error', 'ক্যাটাগরি আপডেট করতে সমস্যা হয়েছে');
    }
}

$categories = $category->getAll();
$page_title = 'ক্যাটাগরি ব্যবস্থাপনা';

$next_order = 1;
if (!empty($categories)) {
    $orders = array_column($categories, 'display_order');
    $next_order = !empty($orders) ? max($orders) + 1 : 1;
}

ob_start();
?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">ক্যাটাগরি ব্যবস্থাপনা</h2>
        <button onclick="document.getElementById('addModal').classList.remove('hidden')" 
                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i>
            নতুন ক্যাটাগরি
        </button>
    </div>
    
    <!-- Categories Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">ক্রম</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">নাম (বাংলা)</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">নাম (English)</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">Slug</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">পোস্ট</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">অর্ডার</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">স্ট্যাটাস</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">কাজ</th>
                    </tr>
                </thead>
                <tbody class="divide-y" id="sortable-categories">
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                কোনো ক্যাটাগরি পাওয়া যায়নি
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $index => $cat): ?>
                            <tr class="hover:bg-gray-50 transition" data-id="<?php echo $cat['id']; ?>">
                                <td class="px-6 py-4 text-sm text-gray-700 flex items-center space-x-3">
                                    <i class="fas fa-grip-vertical text-gray-400 cursor-move hover:text-gray-600" title="Drag to reorder"></i>
                                    <span><?php echo formatNumberBengali($index + 1); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-gray-800"><?php echo escape($cat['name']); ?></span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo escape($cat['name_en'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?php echo escape($cat['slug']); ?></code>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php echo formatNumberBengali($category->getPostCount($cat['id'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php echo formatNumberBengali($cat['display_order']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if ($cat['is_active']): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">
                                            সক্রিয়
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded">
                                            নিষ্ক্রিয়
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex space-x-2">
                                        <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)" 
                                                class="text-blue-600 hover:text-blue-800" title="সম্পাদনা">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $cat['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                           class="text-red-600 hover:text-red-800 delete-confirm" title="মুছুন">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<!-- Add Category Modal -->
<div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-screen overflow-y-auto">
        <div class="p-6 border-b">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold">নতুন ক্যাটাগরি</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-500 hover:text-red-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        নাম (বাংলা) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="যেমন: রাজনীতি">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        নাম (English)
                    </label>
                    <input type="text" name="name_en"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g: Politics">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Slug (URL-friendly)
                </label>
                <input type="text" name="slug"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="politics (leave empty to auto-generate)">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    বিবরণ
                </label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        প্রদর্শন ক্রম
                    </label>
                    <input type="number" name="display_order" value="<?php echo $next_order; ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex items-end">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="mr-2 h-4 w-4">
                        <span class="text-sm">সক্রিয়</span>
                    </label>
                </div>
            </div>
            
            <!-- SEO Fields -->
            <div class="border-t pt-4">
                <h4 class="font-bold mb-3">এসইও সেটিংস</h4>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SEO Title
                        </label>
                        <input type="text" name="seo_title"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SEO Description
                        </label>
                        <textarea name="seo_description" rows="2"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SEO Keywords
                        </label>
                        <input type="text" name="seo_keywords"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>
                    সংরক্ষণ করুন
                </button>
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" 
                        class="px-6 py-3 bg-gray-200 rounded-lg font-semibold hover:bg-gray-300">
                    বাতিল
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-screen overflow-y-auto">
        <div class="p-6 border-b">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold">ক্যাটাগরি সম্পাদনা</h3>
                <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-500 hover:text-red-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <form id="editForm" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="update_id" id="edit_update_id" value="">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        নাম (বাংলা) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="edit_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        নাম (English)
                    </label>
                    <input type="text" name="name_en" id="edit_name_en"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Slug (URL-friendly)
                </label>
                <input type="text" name="slug" id="edit_slug"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    বিবরণ
                </label>
                <textarea name="description" id="edit_description" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        প্রদর্শন ক্রম
                    </label>
                    <input type="number" name="display_order" id="edit_display_order"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex items-end">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1"
                               class="mr-2 h-4 w-4">
                        <span class="text-sm">সক্রিয়</span>
                    </label>
                </div>
            </div>
            
            <!-- SEO Fields -->
            <div class="border-t pt-4">
                <h4 class="font-bold mb-3">এসইও সেটিংস</h4>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SEO Title
                        </label>
                        <input type="text" name="seo_title" id="edit_seo_title"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SEO Description
                        </label>
                        <textarea name="seo_description" id="edit_seo_description" rows="2"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            SEO Keywords
                        </label>
                        <input type="text" name="seo_keywords" id="edit_seo_keywords"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-3 pt-4">
                <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700">
                    <i class="fas fa-save mr-2"></i>
                    আপডেট করুন
                </button>
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" 
                        class="px-6 py-3 bg-gray-200 rounded-lg font-semibold hover:bg-gray-300">
                    বাতিল
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.delete-confirm').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('আপনি কি নিশ্চিত এই ক্যাটাগরিটি মুছে ফেলতে চান?')) {
                e.preventDefault();
            }
        });
    });
    
    function editCategory(cat) {
        // Populate modal fields
        document.getElementById('edit_update_id').value = cat.id;
        document.getElementById('edit_name').value = cat.name || '';
        document.getElementById('edit_name_en').value = cat.name_en || '';
        document.getElementById('edit_slug').value = cat.slug || '';
        document.getElementById('edit_description').value = cat.description || '';
        document.getElementById('edit_display_order').value = cat.display_order || 0;
        document.getElementById('edit_is_active').checked = cat.is_active == 1;
        
        // Populate SEO fields
        document.getElementById('edit_seo_title').value = cat.seo_title || '';
        document.getElementById('edit_seo_description').value = cat.seo_description || '';
        document.getElementById('edit_seo_keywords').value = cat.seo_keywords || '';
        
        // Show the modal
        document.getElementById('editModal').classList.remove('hidden');
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('sortable-categories');
        if (el) {
            Sortable.create(el, {
                handle: '.fa-grip-vertical',
                animation: 150,
                ghostClass: 'bg-blue-50',
                onEnd: function (evt) {
                    var rows = el.querySelectorAll('tr[data-id]');
                    var order = Array.from(rows).map(row => row.getAttribute('data-id'));
                    
                    var formData = new FormData();
                    formData.append('action', 'reorder');
                    order.forEach(id => formData.append('order[]', id));
                    
                    fetch('categories.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Automatically reload to update the Bengali numbers
                            window.location.reload();
                        } else {
                            alert('ক্রম পরিবর্তন করতে সমস্যা হয়েছে।');
                        }
                    });
                }
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
