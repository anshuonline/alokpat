<?php
/**
 * Tags Management Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

requirePermission('manage_tags');
// Permission check
if (!in_array(getCurrentUser()['role'], ['super_admin', 'admin', 'editor'])) {
    setFlash('error', 'আপনার এই পৃষ্ঠা দেখার অনুমতি নেই');
    redirect(ADMIN_URL . '/dashboard.php');
}

$tagModel = new Tag();
$db = (new Database())->getConnection();

// Handle Delete
if (isset($_GET['delete'])) {
    requireCSRF();
    $id = (int)$_GET['delete'];
    
    // Check if tag is used in post_tags before deleting
    $check = $db->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        setFlash('error', 'এই ট্যাগটি কিছু পোস্টে ব্যবহার করা হয়েছে, তাই মুছে ফেলা সম্ভব নয়।');
    } else {
        $stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlash('success', 'ট্যাগ সফলভাবে মুছে ফেলা হয়েছে');
        } else {
            setFlash('error', 'ট্যাগ মুছে ফেলতে সমস্যা হয়েছে');
        }
    }
    redirect(ADMIN_URL . '/tags.php');
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    requireCSRF();
    $name = sanitize($_POST['name'] ?? '');
    $slug = generateSlug($_POST['slug'] ?? $name);
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($name)) {
        setFlash('error', 'নাম প্রদান করুন');
    } else {
        $check = $db->prepare("SELECT id FROM tags WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->rowCount() > 0) {
            setFlash('error', 'এই স্লাগ বা নাম ইতিমধ্যে বিদ্যমান');
        } else {
            $stmt = $db->prepare("INSERT INTO tags (name, slug, description) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $slug, $description])) {
                setFlash('success', 'নতুন ট্যাগ যুক্ত হয়েছে');
                redirect(ADMIN_URL . '/tags.php');
            } else {
                setFlash('error', 'ডেটাবেস এরর');
            }
        }
    }
}

// Pagination and Search setup
$page_num = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page_num < 1) $page_num = 1;
$limit = 15;
$offset = ($page_num - 1) * $limit;

$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Fetch Tags
$tags = $tagModel->getPaginatedAndSearch($search_query, $limit, $offset);
$total_tags = $tagModel->getTotalTagsCount($search_query);
$total_pages = ceil($total_tags / $limit);

$page_title = 'ট্যাগ ব্যবস্থাপনা (Tags)';
ob_start();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">ট্যাগ (Tags)</h2>
            <p class="text-sm text-gray-500 mt-1">আপনার সংবাদের ট্যাগগুলো পরিচালনা করুন</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Add Tag Form -->
        <div class="lg:col-span-1 sticky top-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-800 border-b pb-3 mb-4">নতুন ট্যাগ যোগ করুন</h3>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ট্যাগের নাম *</label>
                            <input type="text" name="name" required class="mt-1 w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="যেমন: খেলাধুলা">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">স্লাগ (URL)</label>
                            <input type="text" name="slug" class="mt-1 w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="খালি রাখলে স্বয়ংক্রিয়ভাবে তৈরি হবে">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">বর্ণনা</label>
                            <textarea name="description" rows="3" class="mt-1 w-full px-4 py-2 border rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition font-bold shadow-sm">
                            যোগ করুন
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tags List -->
        <div class="lg:col-span-2">
            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                <form action="" method="GET" class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" value="<?php echo escape($search_query); ?>" placeholder="ট্যাগ খুঁজুন (নাম বা স্লাগ)..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <button type="submit" class="px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition whitespace-nowrap font-medium">
                        খুঁজুন
                    </button>
                    <?php if(!empty($search_query)): ?>
                        <a href="tags.php" class="px-6 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition whitespace-nowrap font-medium text-center">
                            ক্লিয়ার
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">নাম / স্লাগ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">বর্ণনা</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php if(empty($tags)): ?>
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">কোনো ট্যাগ পাওয়া যায়নি।</td></tr>
                        <?php endif; ?>
                        <?php foreach($tags as $tag): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 text-sm mb-1"><?php echo escape($tag['name']); ?></div>
                                <div class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded inline-block">/tag/<?php echo escape($tag['slug']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo escape(strlen($tag['description']) > 50 ? substr($tag['description'], 0, 50) . '...' : $tag['description']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="tags.php?delete=<?php echo $tag['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" onclick="return confirm('আপনি কি নিশ্চিত? এটি মুছে ফেললে রিকভার করা যাবে না।')" class="inline-block p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-900 transition" title="ডিলিট">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="mt-6">
                <nav class="flex items-center justify-between bg-white px-4 py-3 sm:px-6 rounded-lg shadow-sm border border-gray-100">
                    <div class="hidden sm:block">
                        <p class="text-sm text-gray-700">
                            মোট <span class="font-medium"><?php echo $total_tags; ?></span> টি ট্যাগের মধ্যে <span class="font-medium"><?php echo $offset + 1; ?></span> থেকে <span class="font-medium"><?php echo min($offset + $limit, $total_tags); ?></span> দেখানো হচ্ছে
                        </p>
                    </div>
                    <div class="flex-1 flex justify-between sm:justify-end">
                        <?php 
                        $query_str = '';
                        if (!empty($search_query)) {
                            $query_str = '&search=' . urlencode($search_query);
                        }
                        ?>
                        
                        <?php if($page_num > 1): ?>
                            <a href="tags.php?page=<?php echo $page_num - 1; ?><?php echo $query_str; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                পূর্ববর্তী
                            </a>
                        <?php else: ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">
                                পূর্ববর্তী
                            </span>
                        <?php endif; ?>
                        
                        <?php if($page_num < $total_pages): ?>
                            <a href="tags.php?page=<?php echo $page_num + 1; ?><?php echo $query_str; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                পরবর্তী
                            </a>
                        <?php else: ?>
                            <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed">
                                পরবর্তী
                            </span>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once 'layouts/admin.php';
?>
