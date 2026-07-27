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

// Handle inline status update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'update_status') {
    header('Content-Type: application/json');
    $id = (int)$_POST['post_id'];
    $status = $_POST['status'];
    $allowed_statuses = ['published', 'draft', 'scheduled', 'archived', 'trashed', 'unlisted', 'pending_review'];
    
    if (!in_array($status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'অবৈধ স্ট্যাটাস']);
        exit;
    }
    
    // Get the post to check ownership
    $target_post = $post->getById($id);
    if (!$target_post) {
        echo json_encode(['success' => false, 'message' => 'পোস্ট পাওয়া যায়নি']);
        exit;
    }
    
    $current_user = getCurrentUser();
    $is_own_post = ($target_post['author_id'] == $current_user['id']);
    
    // Permission: Can this user edit this post at all?
    if (!$is_own_post && !hasPermission('edit_others_posts')) {
        echo json_encode(['success' => false, 'message' => 'অন্যের পোস্ট পরিবর্তন করার অনুমতি নেই']);
        exit;
    }
    
    // Permission: publish_posts required to set published/scheduled/unlisted
    if (in_array($status, ['published', 'scheduled', 'unlisted']) && !hasPermission('publish_posts')) {
        echo json_encode(['success' => false, 'message' => 'পোস্ট প্রকাশ করার অনুমতি নেই']);
        exit;
    }
    
    // Permission: delete_posts required to trash
    if ($status === 'trashed' && !hasPermission('delete_posts')) {
        echo json_encode(['success' => false, 'message' => 'পোস্ট ট্র্যাশ করার অনুমতি নেই']);
        exit;
    }
    
    // When publishing, also set published_at if not already set
    if ($status === 'published') {
        $stmt = $db->prepare("UPDATE posts SET status = ?, published_at = COALESCE(published_at, NOW()) WHERE id = ?");
    } else {
        $stmt = $db->prepare("UPDATE posts SET status = ? WHERE id = ?");
    }
    if ($stmt->execute([$status, $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'আপডেট ব্যর্থ']);
    }
    exit;
}

// Bulk Actions Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    requireCSRF();
    $action = $_POST['bulk_action'];
    $post_ids = $_POST['post_ids'] ?? [];
    
    if (!empty($post_ids)) {
        $count = 0;
        foreach ($post_ids as $id) {
            $id = (int)$id;
            
            $target_post = $post->getById($id);
            if (!$target_post) continue;
            
            $is_own_post = ($target_post['author_id'] == $_SESSION['user_id']);
            if (!$is_own_post && !hasPermission('edit_others_posts')) {
                continue; // Skip if no permission to edit others' posts
            }
            
            if ($action === 'delete') {
                if (hasPermission('delete_posts')) {
                    if ($post->delete($id)) $count++;
                }
            } elseif ($action === 'force_delete') {
                if (hasPermission('delete_posts')) {
                    if ($post->forceDelete($id)) $count++;
                }
            } elseif ($action === 'restore') {
                if (hasPermission('delete_posts')) {
                    if ($post->restore($id)) $count++;
                }
            } elseif (in_array($action, ['published', 'draft', 'archived'])) {
                if ($action === 'published' && !hasPermission('publish_posts')) {
                    continue; // Skip if no permission to publish
                }
                
                if ($action === 'published') {
                    $stmt = $db->prepare("UPDATE posts SET status = ?, published_at = COALESCE(published_at, NOW()) WHERE id = ?");
                } else {
                    $stmt = $db->prepare("UPDATE posts SET status = ? WHERE id = ?");
                }
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
    $conditions[] = "p.status != 'trashed'";
} elseif ($filter === 'trash') {
    $conditions[] = "p.status = 'trashed'";
} else {
    // all
    $conditions[] = "p.status != 'trashed'";
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

// Sorting logic
$sort_by = $_GET['sort'] ?? 'date_desc';
$order_by = 'p.created_at DESC';

if ($sort_by === 'views_desc') {
    $order_by = 'p.view_count DESC';
} elseif ($sort_by === 'views_asc') {
    $order_by = 'p.view_count ASC';
} elseif ($sort_by === 'date_asc') {
    $order_by = 'p.created_at ASC';
}

$sql .= " ORDER BY " . $order_by . " LIMIT :limit OFFSET :offset";

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
            <a href="?filter=trash" 
               class="px-4 py-2 rounded-lg text-sm <?php echo $filter === 'trash' ? 'bg-gray-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> transition">
                ট্র্যাশ (<?php echo formatNumberBengali($post->getCount('trashed')); ?>)
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
                <?php if (hasPermission('publish_posts')): ?>
                <option value="published">পাবলিশ করুন (Publish)</option>
                <option value="archived">প্রাইভেট/আর্কাইভ (Archive)</option>
                <?php endif; ?>
                <option value="draft">ড্রাফট করুন (Draft)</option>
                <?php if(hasPermission('delete_posts')): ?>
                <option value="delete">ট্র্যাশে পাঠান (Trash)</option>
                <option value="restore">রিস্টোর করুন (Restore)</option>
                <option value="force_delete">স্থায়ীভাবে মুছুন (Perm. Delete)</option>
                <?php endif; ?>
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
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">
                                <a href="?sort=<?php echo $sort_by === 'views_desc' ? 'views_asc' : 'views_desc'; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" class="hover:text-blue-600 flex items-center gap-1">
                                    ভিউ
                                    <i class="fas fa-sort<?php echo $sort_by === 'views_desc' ? '-amount-down' : ($sort_by === 'views_asc' ? '-amount-up' : ''); ?>"></i>
                                </a>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600">
                                <a href="?sort=<?php echo $sort_by === 'date_desc' ? 'date_asc' : 'date_desc'; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>" class="hover:text-blue-600 flex items-center gap-1">
                                    তারিখ
                                    <i class="fas fa-sort<?php echo $sort_by === 'date_desc' ? '-amount-down' : ($sort_by === 'date_asc' ? '-amount-up' : ''); ?>"></i>
                                </a>
                            </th>
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
                                                     class="w-16 h-16 object-cover rounded" onerror="this.src='<?php echo SITE_URL; ?>/assets/images/default-news.jpg'; this.onerror=null;">
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
                                        'archived' => 'bg-gray-100 text-gray-800',
                                        'trashed' => 'bg-red-100 text-red-800',
                                        'unlisted' => 'bg-purple-100 text-purple-800',
                                        'pending_review' => 'bg-yellow-100 text-yellow-800',
                                        'pending_delete' => 'bg-orange-100 text-orange-800'
                                    ];
                                    // Build permission-aware status options
                                    $current_user_posts = getCurrentUser();
                                    $is_own = ($post_item['author_id'] == $current_user_posts['id']);
                                    $can_edit = $is_own || hasPermission('edit_others_posts');
                                    $can_publish = hasPermission('publish_posts');
                                    $can_delete = hasPermission('delete_posts');
                                    
                                    // Statuses this user is allowed to SET
                                    $allowed_options = ['draft' => 'খসড়া', 'archived' => 'আর্কাইভ'];
                                    if ($can_publish) {
                                        $allowed_options['published'] = 'প্রকাশিত';
                                        $allowed_options['scheduled'] = 'নির্ধারিত';
                                        $allowed_options['unlisted'] = 'আনলিস্টেড';
                                    }
                                    if ($can_delete) {
                                        $allowed_options['trashed'] = 'ট্র্যাশ';
                                    }
                                    // Always show current status even if user can't change to it
                                    $current_status = $post_item['status'];
                                    $all_status_labels = [
                                        'published' => 'প্রকাশিত', 'draft' => 'খসড়া', 'scheduled' => 'নির্ধারিত',
                                        'archived' => 'আর্কাইভ', 'trashed' => 'ট্র্যাশ', 'unlisted' => 'আনলিস্টেড',
                                        'pending_review' => 'পেন্ডিং এডিট', 'pending_delete' => 'পেন্ডিং ডিলিট'
                                    ];
                                    if (!isset($allowed_options[$current_status])) {
                                        $allowed_options[$current_status] = $all_status_labels[$current_status] ?? $current_status;
                                    }
                                    ?>
                                    <?php if ($can_edit): ?>
                                    <select class="inline-status-update w-full px-2 py-1 text-xs font-semibold rounded outline-none border border-transparent hover:border-gray-300 cursor-pointer focus:border-blue-500 <?php echo $status_classes[$post_item['status']] ?? 'bg-gray-100 text-gray-800'; ?>" data-id="<?php echo $post_item['id']; ?>">
                                        <?php foreach ($allowed_options as $val => $label): ?>
                                            <option value="<?php echo $val; ?>" <?php echo $current_status === $val ? 'selected' : ''; ?> class="bg-white text-gray-800 font-normal">
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded <?php echo $status_classes[$current_status] ?? 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $all_status_labels[$current_status] ?? $current_status; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($post_item['is_breaking']): ?>
                                        <div class="mt-1">
                                            <span class="px-2 py-0.5 bg-red-100 text-red-800 text-[10px] rounded-full" title="Breaking News">
                                                <i class="fas fa-bolt mr-1"></i>ব্রেকিং
                                            </span>
                                        </div>
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
                                        <?php if ($post_item['status'] === 'trashed'): ?>
                                            <?php if(hasPermission('delete_posts')): ?>
                                            <a href="<?php echo ADMIN_URL; ?>/post-restore.php?id=<?php echo $post_item['id']; ?>" 
                                               class="text-green-600 hover:text-green-800" title="রিস্টোর করুন">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                            <a href="<?php echo ADMIN_URL; ?>/post-force-delete.php?id=<?php echo $post_item['id']; ?>" 
                                               class="text-red-600 hover:text-red-800 delete-confirm" title="স্থায়ীভাবে মুছুন">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php 
                                            $can_edit_this = ($post_item['author_id'] == $current_user_posts['id'] && hasPermission('edit_own_posts')) || hasPermission('edit_others_posts');
                                            ?>
                                            <?php if ($can_edit_this): ?>
                                            <a href="<?php echo ADMIN_URL; ?>/post-edit.php?id=<?php echo $post_item['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-800" title="সম্পাদনা">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($post_item['status'] === 'published'): ?>
                                                <button type="button" 
                                                        class="text-green-600 hover:text-green-800 instant-index-btn" 
                                                        data-id="<?php echo $post_item['id']; ?>"
                                                        title="Instant Index (গুগলে ইনডেক্স করুন)">
                                                    <i class="fab fa-google"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?php echo ADMIN_URL; ?>/post-share.php?id=<?php echo $post_item['id']; ?>" 
                                               class="text-indigo-600 hover:text-indigo-800" title="সোশ্যাল শেয়ার ইমেজ (Share Image)">
                                                <i class="fas fa-share-alt"></i>
                                            </a>
                                            <?php if (hasPermission('create_posts')): ?>
                                            <a href="<?php echo ADMIN_URL; ?>/post-duplicate.php?id=<?php echo $post_item['id']; ?>" 
                                               class="text-gray-600 hover:text-gray-800" title="ডুপ্লিকেট">
                                                <i class="fas fa-copy"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if(hasPermission('delete_posts')): ?>
                                            <a href="<?php echo ADMIN_URL; ?>/post-delete.php?id=<?php echo $post_item['id']; ?>" 
                                               class="text-red-600 hover:text-red-800 delete-confirm" title="ট্র্যাশে পাঠান">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
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

    // Custom Toast Notification
    function showToast(message, type = 'success', url = '') {
        const toastId = 'toast-' + Math.random().toString(36).substr(2, 9);
        const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        let html = `
            <div id="${toastId}" class="fixed bottom-5 right-5 ${bgColor} text-white px-6 py-4 rounded-lg shadow-xl transform transition-all duration-300 translate-y-full opacity-0 z-50 flex flex-col max-w-sm">
                <div class="flex items-center space-x-3 mb-2">
                    <i class="fas ${icon} text-2xl"></i>
                    <p class="font-bold text-lg">Alokpat Indexing</p>
                    <button onclick="document.getElementById('${toastId}').remove()" class="ml-auto text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-sm opacity-90">${message}</p>
        `;
        
        if (url) {
            html += `<p class="text-xs mt-2 opacity-75 break-all">${url}</p>`;
        }
        
        html += `</div>`;
        
        document.body.insertAdjacentHTML('beforeend', html);
        
        const toast = document.getElementById(toastId);
        
        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-y-full', 'opacity-0');
        }, 10);
        
        // Remove after 6 seconds
        setTimeout(() => {
            toast.classList.add('translate-y-full', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 6000);
    }

    // Inline Status Update
    const statusClasses = {
        'published': 'bg-green-100 text-green-800',
        'draft': 'bg-yellow-100 text-yellow-800',
        'scheduled': 'bg-blue-100 text-blue-800',
        'archived': 'bg-gray-100 text-gray-800',
        'trashed': 'bg-red-100 text-red-800',
        'unlisted': 'bg-gray-200 text-gray-600'
    };

    document.querySelectorAll('.inline-status-update').forEach(select => {
        select.addEventListener('change', function() {
            const postId = this.getAttribute('data-id');
            const newStatus = this.value;
            const oldStatus = this._prevValue || this.options[this.selectedIndex].defaultSelected ? this.value : null;
            const selectEl = this;
            
            // Store original value for reverting
            const origValue = Array.from(this.options).find(o => o.defaultSelected)?.value || this.value;
            
            this.style.opacity = '0.5';
            
            fetch('posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax_action=update_status&post_id=' + postId + '&status=' + newStatus
            })
            .then(response => response.json())
            .then(data => {
                selectEl.style.opacity = '1';
                if (data.success) {
                    showToast('স্ট্যাটাস সফলভাবে আপডেট হয়েছে', 'success');
                    // Remove old status classes
                    Object.values(statusClasses).forEach(cls => {
                        cls.split(' ').forEach(c => selectEl.classList.remove(c));
                    });
                    // Add new status class
                    if (statusClasses[newStatus]) {
                        statusClasses[newStatus].split(' ').forEach(c => selectEl.classList.add(c));
                    }
                } else {
                    showToast(data.message || 'স্ট্যাটাস আপডেট করতে সমস্যা হয়েছে', 'error');
                    // Revert to previous value
                    selectEl.value = origValue;
                }
            })
            .catch(error => {
                selectEl.style.opacity = '1';
                showToast('সার্ভার এরর!', 'error');
                selectEl.value = origValue;
            });
        });
    });

    // Instant Indexing
    document.querySelectorAll('.instant-index-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const postId = this.getAttribute('data-id');
            const originalHtml = this.innerHTML;
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;

            fetch('ajax/instant-index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'post_id=' + postId
            })
            .then(response => response.json())
            .then(data => {
                this.innerHTML = originalHtml;
                this.disabled = false;
                
                if (data.success) {
                    showToast(data.message, 'success', data.url);
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                this.innerHTML = originalHtml;
                this.disabled = false;
                showToast('An error occurred while processing the request.', 'error');
            });
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>

