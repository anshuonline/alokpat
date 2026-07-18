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

$categories = $category->getAll();
$page_title = 'ক্যাটাগরি ব্যবস্থাপনা';

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
                <tbody class="divide-y">
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                কোনো ক্যাটাগরি পাওয়া যায়নি
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $index => $cat): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php echo formatNumberBengali($index + 1); ?>
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
                    <input type="number" name="display_order" value="0"
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

<script>
    document.querySelectorAll('.delete-confirm').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('আপনি কি নিশ্চিত এই ক্যাটাগরিটি মুছে ফেলতে চান?')) {
                e.preventDefault();
            }
        });
    });
    
    function editCategory(cat) {
        // TODO: Implement edit functionality
        alert('Edit functionality will be added soon');
    }
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
