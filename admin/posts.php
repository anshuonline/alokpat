<?php
/**
 * Admin Posts Management
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

$post = new Post();
$db = (new Database())->getConnection();

// Bulk Actions Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    requireCSRF();
    $action = $_POST['bulk_action'];
    $post_ids = $_POST['post_ids'] ?? [];
    
    if (!empty($post_ids)) {
        $count = 0;
        foreach ($post_ids as $id) {
            $id = (int)$id;
            if ($action === 'delete') {
                if ($post->delete($id)) $count++;
            } elseif (in_array($action, ['published', 'draft', 'archived'])) {
                $stmt = $db->prepare("UPDATE posts SET status = ? WHERE id = ?");
                if ($stmt->execute([$action, $id])) $count++;
            }
        }
        setFlash('success', $count . ' টি পোস্টে সফলভাবে পরিবর্তন করা হয়েছে');
        
        $redirect_url = ADMIN_URL . '/posts.php';
        $query_parts = [];
        if (isset($_GET['filter']) && $_GET['filter'] !== 'all') {
            $query_parts[] = 'filter=' . urlencode($_GET['filter']);
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $query_parts[] = 'search=' . urlencode($_GET['search']);
        }
        if (isset($_GET['page']) && $_GET['page'] > 1) {
            $query_parts[] = 'page=' . (int)$_GET['page'];
        }
        
        if (!empty($query_parts)) {
            $redirect_url .= '?' . implode('&', $query_parts);
        }
        
        redirect($redirect_url);
    }
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = ADMIN_POSTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Filter & Search
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$conditions = [];
$params = [];

if ($filter === 'published') {
    $conditions[] = "p.status = 'published'";
} elseif ($filter === 'draft') {
    $conditions[] = "p.status = 'draft'";
} elseif ($filter === 'breaking') {
    $conditions[] = "p.is_breaking = 1";
}

if (!empty($search)) {
    $conditions[] = "(p.title LIKE :search OR p.id = :search_id)";
    $params[':search'] = '%' . $search . '%';
    $params[':search_id'] = (int)$search;
}

// Get posts
$sql = "SELECT p.*, u.full_name as author_name, c.name as category_name 
        FROM posts p 
        LEFT JOIN users u ON p.author_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Get total count
$count_sql = "SELECT COUNT(*) FROM posts p";
if (!empty($conditions)) {
    $count_sql .= " WHERE " . implode(' AND ', $conditions);
}
$count_stmt = $db->prepare($count_sql);
foreach ($params as $key => $val) {
    $count_stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$count_stmt->execute();
$total = $count_stmt->fetchColumn();

$total_pages = ceil($total / $limit);

$page_title = 'সংবাদ ব্যবস্থাপনা';

ob_start();
?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">সংবাদ ব্যবস্থাপনা</h2>
        <a href="<?php echo ADMIN_URL; ?>/post-create.php" 
           class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i>
            নতুন সংবাদ
        </a>
    </div>
    
    <!-- Filters and Search -->
    <div class="bg-white rounded-xl shadow-md p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex flex-wrap gap-2">
            <a href="?filter=all" 
               class="px-4 py-2 rounded-lg text-sm <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                সকল (<?php echo formatNumberBengali($post->getCount()); ?>)
            </a>
            <a href="?filter=published" 
               class="px-4 py-2 rounded-lg text-sm <?php echo $filter === 'published' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                প্রকাশিত (<?php echo formatNumberBengali($post->getCount('published')); ?>)
            </a>
            <a href="?filter=draft" 
               class="px-4 py-2 rounded-lg text-sm <?php echo $filter === 'draft' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                খসড়া (<?php echo formatNumberBengali($post->getCount('draft')); ?>)
            </a>
            <a href="?filter=breaking" 
               class="px-4 py-2 rounded-lg text-sm <?php echo $filter === 'breaking' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                ব্রেকিং (<?php echo formatNumberBengali(count($post->getBreakingNews())); ?>)
            </a>
        </div>
        
        <form action="" method="GET" class="w-full md:w-auto flex">
            <?php if($filter !== 'all'): ?>
            <input type="hidden" name="filter" value="<?php echo escape($filter); ?>">
            <?php endif; ?>
            <input type="text" name="search" value="<?php echo escape($search); ?>" placeholder="Search ID or Title..." 
                   class="border border-gray-300 rounded-l-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500 w-full md:w-64">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-r-lg hover:bg-blue-700 transition">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    
    <!-- Posts Table -->
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="mb-4 flex items-center space-x-3">
            <select name="bulk_action" class="border-gray-300 rounded-lg text-sm px-3 py-2 outline-none focus:border-blue-500">
                <option value="">বাল্ক অ্যাকশন (Bulk Action)</option>
                <option value="published">পাবলিশ করুন (Publish)</option>
                <option value="draft">ড্রাফট করুন (Draft)</option>
                <option value="archived">প্রাইভেট/আর্কাইভ (Archive)</option>
                <option value="delete">মুছে ফেলুন (Delete)</option>
            </select>
            <button type="submit" onclick="return confirm('আপনি কি নিশ্চিত?')" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition">এপ্লাই</button>
        </div>

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 w-1/3">শিরোনাম</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">লেখক</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">ক্যাটাগরি</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">স্ট্যাটাস</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">ভিউ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">তারিখ</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">কাজ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    কোনো সংবাদ পাওয়া যায়নি
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post_item): ?>
                                <tr class="hover:bg-gray-50 transition border-b border-gray-100">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="post_ids[]" value="<?php echo $post_item['id']; ?>" class="post-checkbox rounded border-gray-300">
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-500">
                                        #<?php echo $post_item['id']; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-start space-x-3">
                                            <?php if (!empty($post_item['featured_image'])): ?>
                                                <img src="<?php echo escape($post_item['featured_image']); ?>" 
                                                     alt="" 
                                                     class="w-16 h-16 object-cover rounded">
                                            <?php endif; ?>
                                        <div>
                                            <h4 class="font-semibold text-gray-800 hover:text-blue-600">
                                                <a href="<?php echo url_for_post($post_item); ?>" target="_blank">
                                                    <?php echo escape($post_item['title']); ?>
                                                </a>
                                            </h4>
                                            <p class="text-xs text-gray-500 mt-1">
                                                /<?php echo escape($post_item['slug']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php echo escape($post_item['author_name']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if (!empty($post_item['category_name'])): ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-600 text-xs rounded">
                                            <?php echo escape($post_item['category_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php
                                    $status_classes = [
                                        'published' => 'bg-green-100 text-green-800',
                                        'draft' => 'bg-yellow-100 text-yellow-800',
                                        'scheduled' => 'bg-blue-100 text-blue-800',
                                        'archived' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $status_labels = [
                                        'published' => 'প্রকাশিত',
                                        'draft' => 'খসড়া',
                                        'scheduled' => 'নির্ধারিত',
                                        'archived' => 'সংরক্ষিত'
                                    ];
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded <?php echo $status_classes[$post_item['status']]; ?>">
                                        <?php echo $status_labels[$post_item['status']]; ?>
                                    </span>
                                    <?php if ($post_item['is_breaking']): ?>
                                        <span class="ml-1 px-2 py-1 bg-red-100 text-red-800 text-xs rounded">
                                            <i class="fas fa-bolt"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php echo formatNumberBengali($post_item['view_count']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo formatDateBengali($post_item['created_at'], 'd M Y'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex space-x-2">
                                        <a href="<?php echo ADMIN_URL; ?>/post-edit.php?id=<?php echo $post_item['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800" title="সম্পাদনা">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo ADMIN_URL; ?>/post-duplicate.php?id=<?php echo $post_item['id']; ?>" 
                                           class="text-gray-600 hover:text-gray-800" title="ডুপ্লিকেট">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <a href="<?php echo ADMIN_URL; ?>/post-delete.php?id=<?php echo $post_item['id']; ?>" 
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
    </form>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t bg-gray-50">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        মোট <?php echo formatNumberBengali($total); ?> টি সংবাদ
                    </p>
                    <nav class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>" 
                               class="px-3 py-2 bg-white border rounded hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" 
                               class="px-3 py-2 <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white border hover:bg-gray-50'; ?> rounded">
                                <?php echo formatNumberBengali($i); ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>" 
                               class="px-3 py-2 bg-white border rounded hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<script>
    document.querySelectorAll('.delete-confirm').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('আপনি কি নিশ্চিত? এটি মুছে ফেললে আর ফিরে পাওয়া যাবে না।')) {
                e.preventDefault();
            }
        });
    });

    // Select All Checkboxes
    const selectAll = document.getElementById('selectAll');
    const postCheckboxes = document.querySelectorAll('.post-checkbox');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            postCheckboxes.forEach(cb => cb.checked = this.checked);
        });
    }
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>

