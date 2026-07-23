<?php
/**
 * Subscribers Management Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
$subscriberModel = new Subscriber();

// Handle Delete
if (isset($_GET['delete'])) {
    requireCSRF();
    $id = (int)$_GET['delete'];
    
    if ($subscriberModel->delete($id)) {
        setFlash('success', 'সাবস্ক্রাইবার মুছে ফেলা হয়েছে');
    } else {
        setFlash('error', 'সাবস্ক্রাইবার মুছতে সমস্যা হয়েছে');
    }
    redirect('subscribers.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ADMIN_POSTS_PER_PAGE ?? 20;
$offset = ($page - 1) * $limit;

// Filter
$filter = $_GET['filter'] ?? 'all';
$valid_filters = ['all', 'weekly', 'monthly'];
if (!in_array($filter, $valid_filters)) {
    $filter = 'all';
}

// Get Data
$total = $subscriberModel->getTotalCount($filter);
$subscribers = $subscriberModel->getSubscribers($filter, $limit, $offset);
$total_pages = ceil($total / $limit);

$page_title = 'নিউজলেটার সাবস্ক্রাইবার';
ob_start();
?>

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <h1 class="text-2xl font-bold text-gray-800">নিউজলেটার সাবস্ক্রাইবার (<?php echo $total; ?>)</h1>
    
    <!-- Filters -->
    <div class="flex bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
        <a href="?filter=all" class="px-4 py-2 text-sm font-medium <?php echo $filter === 'all' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-50'; ?>">
            সব (All)
        </a>
        <a href="?filter=weekly" class="px-4 py-2 text-sm font-medium border-l border-gray-200 <?php echo $filter === 'weekly' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-50'; ?>">
            এই সপ্তাহ (Weekly)
        </a>
        <a href="?filter=monthly" class="px-4 py-2 text-sm font-medium border-l border-gray-200 <?php echo $filter === 'monthly' ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-50'; ?>">
            এই মাস (Monthly)
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-700">
                <tr>
                    <th class="px-6 py-4 font-semibold">আইডি</th>
                    <th class="px-6 py-4 font-semibold">ইমেইল</th>
                    <th class="px-6 py-4 font-semibold">স্ট্যাটাস</th>
                    <th class="px-6 py-4 font-semibold">সাবস্ক্রাইব করার তারিখ</th>
                    <th class="px-6 py-4 font-semibold text-right">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            কোনো সাবস্ক্রাইবার পাওয়া যায়নি।
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscribers as $sub): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900">#<?php echo $sub['id']; ?></td>
                            <td class="px-6 py-4">
                                <a href="mailto:<?php echo escape($sub['email']); ?>" class="text-primary-600 hover:underline">
                                    <?php echo escape($sub['email']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($sub['status'] === 'active'): ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        সক্রিয়
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        আনসাবস্ক্রাইবড
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                <?php echo date('d M Y, h:i A', strtotime($sub['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="?delete=<?php echo $sub['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                   onclick="return confirm('আপনি কি নিশ্চিত যে এটি মুছতে চান?')"
                                   class="text-red-500 hover:text-red-700 transition" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <span class="text-sm text-gray-600">
                মোট <?php echo $total; ?> টির মধ্যে <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total); ?> দেখানো হচ্ছে
            </span>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 text-sm">আগে</a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50 text-sm">পরে</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
