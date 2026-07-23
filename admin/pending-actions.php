<?php
/**
 * Pending Actions Dashboard
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();
requirePermission('publish_posts'); // Only users who can publish can approve/reject

$db = (new Database())->getConnection();
$post_model = new Post();

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $id = (int)$_POST['id'];
    
    if ($type === 'edit') {
        // Pending Edit
        $pending_post = $post_model->getById($id);
        if ($pending_post && $pending_post['status'] === 'pending_review' && !empty($pending_post['parent_id'])) {
            if ($action === 'approve') {
                // Apply pending data to original post
                $parent_id = $pending_post['parent_id'];
                
                $update_data = $pending_post;
                unset($update_data['id']);
                unset($update_data['parent_id']);
                $update_data['status'] = 'published'; // Publish it again
                // Remove the -rev-[time] suffix that was added to avoid duplicate slug constraint
                $update_data['slug'] = preg_replace('/-rev-\d+$/', '', $pending_post['slug']);
                
                if ($post_model->update($parent_id, $update_data)) {
                    // Delete the pending revision
                    $post_model->forceDelete($id);
                    setFlash('success', 'এডিটটি অনুমোদিত হয়েছে এবং লাইভ হয়েছে।');
                } else {
                    setFlash('error', 'এডিট অনুমোদন করতে সমস্যা হয়েছে।');
                }
            } elseif ($action === 'reject') {
                // Delete the pending revision
                $post_model->forceDelete($id);
                setFlash('success', 'এডিটটি বাতিল করা হয়েছে।');
            }
        }
    } elseif ($type === 'delete') {
        // Pending Delete
        $target_post = $post_model->getById($id);
        if ($target_post && $target_post['status'] === 'pending_delete') {
            if ($action === 'approve') {
                // Trash it
                if ($post_model->delete($id)) {
                    setFlash('success', 'ডিলিট অনুরোধ অনুমোদিত হয়েছে (ট্র্যাশে পাঠানো হয়েছে)।');
                }
            } elseif ($action === 'reject') {
                // Restore it to published (or previous status, assuming published for now)
                $stmt = $db->prepare("UPDATE posts SET status = 'published' WHERE id = ?");
                if ($stmt->execute([$id])) {
                    setFlash('success', 'ডিলিট অনুরোধ বাতিল করা হয়েছে (পোস্ট আবার লাইভ)।');
                }
            }
        }
    }
    
    redirect(ADMIN_URL . '/pending-actions.php');
}

// Fetch Pending Edits (Posts with status = pending_review AND parent_id IS NOT NULL)
$stmt = $db->query("
    SELECT p.*, 
           u.full_name as updater_name,
           orig.title as original_title
    FROM posts p 
    LEFT JOIN users u ON p.updated_by = u.id 
    LEFT JOIN posts orig ON p.parent_id = orig.id
    WHERE p.status = 'pending_review' AND p.parent_id IS NOT NULL
    ORDER BY p.created_at DESC
");
$pending_edits = $stmt->fetchAll();

// Fetch Pending Deletes (Posts with status = pending_delete)
$stmt2 = $db->query("
    SELECT p.*, 
           u.full_name as updater_name
    FROM posts p 
    LEFT JOIN users u ON p.updated_by = u.id 
    WHERE p.status = 'pending_delete'
    ORDER BY p.updated_at DESC
");
$pending_deletes = $stmt2->fetchAll();

$page_title = 'পেন্ডিং অ্যাকশন (Pending Actions)';
ob_start();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-tasks text-yellow-500 mr-2"></i>
                পেন্ডিং অ্যাকশন
            </h2>
            <p class="text-gray-500 mt-1">রাইটারদের এডিট ও ডিলিট অনুরোধ সমূহ যাচাই করুন</p>
        </div>
    </div>

    <!-- Pending Edits -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-blue-50 px-6 py-4 border-b border-gray-200">
            <h3 class="font-bold text-blue-800 text-lg flex items-center">
                <i class="fas fa-edit mr-2"></i> পেন্ডিং এডিট (Pending Edits)
                <span class="ml-2 bg-blue-200 text-blue-800 text-xs px-2 py-1 rounded-full"><?php echo count($pending_edits); ?></span>
            </h3>
        </div>
        
        <?php if (empty($pending_edits)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-check-circle text-4xl mb-3 text-green-300"></i>
                <p>কোনো পেন্ডিং এডিট নেই</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 text-sm">
                        <tr>
                            <th class="px-6 py-3">রাইটার</th>
                            <th class="px-6 py-3">অরিজিনাল পোস্ট</th>
                            <th class="px-6 py-3">নতুন টাইটেল (যদি পরিবর্তন হয়)</th>
                            <th class="px-6 py-3">সময়</th>
                            <th class="px-6 py-3 text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($pending_edits as $edit): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-800">
                                <?php echo escape($edit['updater_name'] ?? 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <a href="<?php echo ADMIN_URL; ?>/post-edit.php?id=<?php echo $edit['parent_id']; ?>" target="_blank" class="text-blue-600 hover:underline">
                                    #<?php echo $edit['parent_id']; ?> - <?php echo escape($edit['original_title']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <?php echo escape($edit['title']); ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                <?php echo formatDateBengali($edit['created_at']); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="<?php echo ADMIN_URL; ?>/post-edit.php?id=<?php echo $edit['id']; ?>" target="_blank" class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-xs font-semibold">
                                        প্রিভিউ (Preview)
                                    </a>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="type" value="edit">
                                        <input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
                                        <button type="submit" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs font-semibold" onclick="return confirm('অ্যাপ্রুভ করতে চান?')">
                                            অ্যাপ্রুভ
                                        </button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="type" value="edit">
                                        <input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
                                        <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs font-semibold" onclick="return confirm('বাতিল করতে চান?')">
                                            বাতিল
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pending Deletes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8">
        <div class="bg-red-50 px-6 py-4 border-b border-gray-200">
            <h3 class="font-bold text-red-800 text-lg flex items-center">
                <i class="fas fa-trash-alt mr-2"></i> পেন্ডিং ডিলিট (Pending Deletes)
                <span class="ml-2 bg-red-200 text-red-800 text-xs px-2 py-1 rounded-full"><?php echo count($pending_deletes); ?></span>
            </h3>
        </div>
        
        <?php if (empty($pending_deletes)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-check-circle text-4xl mb-3 text-green-300"></i>
                <p>কোনো পেন্ডিং ডিলিট নেই</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 text-sm">
                        <tr>
                            <th class="px-6 py-3">রিকোয়েস্ট করেছে</th>
                            <th class="px-6 py-3">পোস্ট টাইটেল</th>
                            <th class="px-6 py-3">সময়</th>
                            <th class="px-6 py-3 text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($pending_deletes as $del): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-800">
                                <?php echo escape($del['updater_name'] ?? 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <a href="<?php echo ADMIN_URL; ?>/post-edit.php?id=<?php echo $del['id']; ?>" target="_blank" class="text-blue-600 hover:underline">
                                    #<?php echo $del['id']; ?> - <?php echo escape($del['title']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                <?php echo formatDateBengali($del['updated_at']); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="type" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $del['id']; ?>">
                                        <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs font-semibold" onclick="return confirm('সত্যিই ডিলিট (ট্র্যাশ) করতে চান?')">
                                            ডিলিট অ্যাপ্রুভ
                                        </button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="type" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $del['id']; ?>">
                                        <button type="submit" class="px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600 text-xs font-semibold" onclick="return confirm('ডিলিট বাতিল করতে চান?')">
                                            বাতিল (Restore)
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'layouts/admin.php';
?>
