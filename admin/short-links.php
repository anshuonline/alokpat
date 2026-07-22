<?php
/**
 * Short Links Management Page
 * 
 * @package Alokpat\Admin
 */

require_once '../config/config.php';
require_once '../helpers/functions.php';

// Check if user is logged in
requireAuth();

// Enable debugging for this page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = ADMIN_POSTS_PER_PAGE;
$offset = ($page - 1) * $limit;

$links = [];
$total_links = 0;
$total_pages = 0;
$db_error = null;

try {
    // Fetch short links
    $stmt = $pdo->prepare("
        SELECT s.id, s.short_code, s.clicks, s.created_at, p.title as post_title, p.id as post_id
        FROM short_links s
        JOIN posts p ON s.post_id = p.id
        ORDER BY s.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $links = $stmt->fetchAll();

    // Get total count for pagination
    $totalStmt = $pdo->query("SELECT COUNT(id) FROM short_links");
    $total_links = $totalStmt->fetchColumn();
    $total_pages = ceil($total_links / $limit);
} catch (PDOException $e) {
    // Table probably doesn't exist
    $db_error = $e->getMessage();
}

$page_title = 'Short Links Management';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between border-b-2 border-black pb-4 mb-6">
        <h2 class="text-3xl font-black text-black uppercase tracking-widest flex items-center">
            <i class="fas fa-link mr-3"></i> Short Links
        </h2>
        <div class="text-xs font-bold text-gray-500 uppercase tracking-widest bg-gray-100 px-4 py-2 rounded-full">
            Total Links: <?php echo $total_links; ?>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        
        <?php if ($db_error): ?>
        <div class="p-8 text-center bg-red-50">
            <i class="fas fa-exclamation-triangle text-5xl text-red-400 mb-4"></i>
            <h3 class="text-xl font-bold text-red-800 mb-2">ডাটাবেস ত্রুটি (Database Error)</h3>
            <p class="text-red-600 mb-4">শর্ট লিংক টেবিলটি ডাটাবেসে পাওয়া যায়নি। দয়া করে নিচের SQL কোডটি phpMyAdmin-এ রান করুন:</p>
            <div class="bg-white p-4 rounded-lg border border-red-200 text-left overflow-x-auto text-sm text-gray-800 font-mono">
                CREATE TABLE IF NOT EXISTS short_links (<br>
                    &nbsp;&nbsp;id INT AUTO_INCREMENT PRIMARY KEY,<br>
                    &nbsp;&nbsp;post_id INT NOT NULL,<br>
                    &nbsp;&nbsp;short_code VARCHAR(15) NOT NULL UNIQUE,<br>
                    &nbsp;&nbsp;clicks INT DEFAULT 0,<br>
                    &nbsp;&nbsp;created_at DATETIME DEFAULT CURRENT_TIMESTAMP,<br>
                    &nbsp;&nbsp;FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE<br>
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            </div>
            <p class="text-xs text-red-500 mt-4 text-left">System Error: <?php echo escape($db_error); ?></p>
        </div>
        <?php else: ?>
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-lg font-bold text-gray-800">Generated Links Analytics</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider font-bold">
                        <th class="px-6 py-4">Post Title</th>
                        <th class="px-6 py-4">Short URL</th>
                        <th class="px-6 py-4 text-center">Clicks</th>
                        <th class="px-6 py-4">Created At</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (count($links) > 0): ?>
                        <?php foreach ($links as $link): 
                            $short_url = SITE_URL . '/u' . $link['short_code'];
                        ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900 line-clamp-2" style="font-family: 'Noto Sans Bengali', sans-serif;">
                                        <?php echo escape($link['post_title']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 font-medium">Post ID: <?php echo $link['post_id']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?php echo escape($short_url); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-bold flex items-center bg-blue-50 px-3 py-1 rounded-full w-max">
                                        /u<?php echo escape($link['short_code']); ?>
                                        <i class="fas fa-external-link-alt ml-2 text-xs"></i>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold <?php echo $link['clicks'] > 0 ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-gray-100 text-gray-600 border border-gray-200'; ?>">
                                        <i class="fas fa-chart-line mr-1.5"></i> <?php echo number_format($link['clicks']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-600 whitespace-nowrap">
                                    <?php echo date('M d, Y h:i A', strtotime($link['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="copyToClipboard('<?php echo escape($short_url); ?>')" class="text-gray-400 hover:text-indigo-600 transition bg-gray-50 hover:bg-indigo-50 w-10 h-10 rounded-full flex items-center justify-center border border-gray-200 hover:border-indigo-200" title="Copy Link">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-link text-5xl text-gray-200 mb-4"></i>
                                    <p class="text-xl font-bold text-gray-400">No short links generated yet.</p>
                                    <p class="text-sm mt-2 text-gray-400">Generate social cards to create short links automatically.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                <div class="text-sm font-medium text-gray-500">
                    Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-white hover:border-gray-300 transition text-sm font-bold shadow-sm">Previous</a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-white hover:border-gray-300 transition text-sm font-bold shadow-sm">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php endif; // End of db_error check ?>
    </div>
</div>

<script>
function copyToClipboard(text) {
    const el = document.createElement('textarea');
    el.value = text;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    
    // Simple toast
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-6 py-3 rounded-lg shadow-2xl z-50 transform transition-all duration-300 font-medium flex items-center';
    toast.innerHTML = '<i class="fas fa-check-circle text-green-400 mr-3 text-lg"></i> Link copied to clipboard!';
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(1rem)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<?php 
$content = ob_get_clean();
require_once 'layouts/admin.php'; 
?>
