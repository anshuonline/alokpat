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
        setFlash('success', $count . ' à¦Ÿà¦¿ à¦ªà§‹à¦¸à§à¦Ÿà§‡ à¦¸à¦«à¦²à¦­à¦¾à¦¬à§‡ à¦ªà¦°à¦¿à¦¬à¦°à§à¦¤à¦¨ à¦•à¦°à¦¾ à¦¹à¦¯à¦¼à§‡à¦›à§‡');
        redirect(ADMIN_URL . '/posts.php');
    }
}

// Pagination
$page = $_GET['page'] ?? 1;
$limit = ADMIN_POSTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = [];

if ($filter === 'published') {
    $where['status'] = 'published';
} elseif ($filter === 'draft') {
    $where['status'] = 'draft';
} elseif ($filter === 'breaking') {
    $where['is_breaking'] = 1;
}

// Get posts
if (empty($where)) {
    $posts = $post->getPublished($limit, $offset);
    $total = $post->getCount('published');
} else {
    // Custom query for filters
    $sql = "SELECT p.*, u.full_name as author_name, c.name as category_name 
            FROM posts p 
            LEFT JOIN users u ON p.author_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id";
    
    $conditions = [];
    if (isset($where['status'])) {
        $conditions[] = "p.status = :status";
    }
    if (isset($where['is_breaking'])) {
        $conditions[] = "p.is_breaking = 1";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $database->query($sql, array_merge($where, ['limit' => $limit, 'offset' => $offset]));
    $posts = $stmt->fetchAll();
    
    $total = $post->getCount($filter !== 'all' ? $filter : null);
}

$total_pages = ceil($total / $limit);

$page_title = 'à¦¸à¦‚à¦¬à¦¾à¦¦ à¦¬à§à¦¯à¦¬à¦¸à§à¦¥à¦¾à¦ªà¦¨à¦¾';

ob_start();
?>

<div class="space-y-6">
    
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">à¦¸à¦‚à¦¬à¦¾à¦¦ à¦¬à§à¦¯à¦¬à¦¸à§à¦¥à¦¾à¦ªà¦¨à¦¾</h2>
        <a href="<?php echo ADMIN_URL; ?>/post-create.php" 
           class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i>
            à¦¨à¦¤à§à¦¨ à¦¸à¦‚à¦¬à¦¾à¦¦
        </a>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="flex flex-wrap gap-3">
            <a href="?filter=all" 
               class="px-4 py-2 rounded-lg <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                à¦¸à¦•à¦² (<?php echo formatNumberBengali($post->getCount()); ?>)
            </a>
            <a href="?filter=published" 
               class="px-4 py-2 rounded-lg <?php echo $filter === 'published' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                à¦ªà§à¦°à¦•à¦¾à¦¶à¦¿à¦¤ (<?php echo formatNumberBengali($post->getCount('published')); ?>)
            </a>
            <a href="?filter=draft" 
               class="px-4 py-2 rounded-lg <?php echo $filter === 'draft' ? 'bg-yellow-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                à¦–à¦¸à¦¡à¦¼à¦¾ (<?php echo formatNumberBengali($post->getCount('draft')); ?>)
            </a>
            <a href="?filter=breaking" 
               class="px-4 py-2 rounded-lg <?php echo $filter === 'breaking' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                à¦¬à§à¦°à§‡à¦•à¦¿à¦‚ (<?php echo formatNumberBengali(count($post->getBreakingNews())); ?>)
            </a>
        </div>
    </div>
    
    <!-- Posts Table -->
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="mb-4 flex items-center space-x-3">
            <select name="bulk_action" class="border-gray-300 rounded-lg text-sm px-3 py-2 outline-none focus:border-blue-500">
                <option value="">à¦¬à¦¾à¦²à§à¦• à¦…à§à¦¯à¦¾à¦•à¦¶à¦¨ (Bulk Action)</option>
                <option value="published">à¦ªà¦¾à¦¬à¦²à¦¿à¦¶ à¦•à¦°à§à¦¨ (Publish)</option>
                <option value="draft">à¦¡à§à¦°à¦¾à¦«à¦Ÿ à¦•à¦°à§à¦¨ (Draft)</option>
                <option value="archived">à¦ªà§à¦°à¦¾à¦‡à¦­à§‡à¦Ÿ/à¦†à¦°à§à¦•à¦¾à¦‡à¦­ (Archive)</option>
                <option value="delete">à¦®à§à¦›à§‡ à¦«à§‡à¦²à§à¦¨ (Delete)</option>
            </select>
            <button type="submit" onclick="return confirm('à¦†à¦ªà¦¨à¦¿ à¦•à¦¿ à¦¨à¦¿à¦¶à§à¦šà¦¿à¦¤?')" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition">à¦à¦ªà§à¦²à¦¾à¦‡</button>
        </div>

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 w-12">
                                <input type="checkbox" id="selectAll" class="rounded text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">à¦¶à¦¿à¦°à§‹à¦¨à¦¾à¦®</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">à¦²à§‡à¦–à¦•</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">à¦•à§à¦¯à¦¾à¦Ÿà¦¾à¦—à¦°à¦¿</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">à¦¸à§à¦Ÿà§à¦¯à¦¾à¦Ÿà¦¾à¦¸</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">à¦­à¦¿à¦‰</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">à¦¤à¦¾à¦°à¦¿à¦–</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">à¦•à¦¾à¦œ</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                à¦•à§‹à¦¨à§‹ à¦¸à¦‚à¦¬à¦¾à¦¦ à¦ªà¦¾à¦“à¦¯à¦¼à¦¾ à¦¯à¦¾à¦¯à¦¼à¦¨à¦¿
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $post_item): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="post_ids[]" value="<?php echo $post_item['id']; ?>" class="post-checkbox rounded text-blue-600 focus:ring-blue-500">
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
                                        'published' => 'à¦ªà§à¦°à¦•à¦¾à¦¶à¦¿à¦¤',
                                        'draft' => 'à¦–à¦¸à¦¡à¦¼à¦¾',
                                        'scheduled' => 'à¦¨à¦¿à¦°à§à¦§à¦¾à¦°à¦¿à¦¤',
                                        'archived' => 'à¦¸à¦‚à¦°à¦•à§à¦·à¦¿à¦¤'
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
                                           class="text-blue-600 hover:text-blue-800" title="à¦¸à¦®à§à¦ªà¦¾à¦¦à¦¨à¦¾">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo ADMIN_URL; ?>/post-duplicate.php?id=<?php echo $post_item['id']; ?>" 
                                           class="text-gray-600 hover:text-gray-800" title="à¦¡à§à¦ªà§à¦²à¦¿à¦•à§‡à¦Ÿ">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <a href="<?php echo ADMIN_URL; ?>/post-delete.php?id=<?php echo $post_item['id']; ?>" 
                                           class="text-red-600 hover:text-red-800 delete-confirm" title="à¦®à§à¦›à§à¦¨">
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
                        à¦®à§‹à¦Ÿ <?php echo formatNumberBengali($total); ?> à¦Ÿà¦¿ à¦¸à¦‚à¦¬à¦¾à¦¦
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
            if (!confirm('à¦†à¦ªà¦¨à¦¿ à¦•à¦¿ à¦¨à¦¿à¦¶à§à¦šà¦¿à¦¤? à¦à¦Ÿà¦¿ à¦®à§à¦›à§‡ à¦«à§‡à¦²à¦²à§‡ à¦†à¦° à¦«à¦¿à¦°à§‡ à¦ªà¦¾à¦“à¦¯à¦¼à¦¾ à¦¯à¦¾à¦¬à§‡ à¦¨à¦¾à¥¤')) {
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

