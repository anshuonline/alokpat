<?php
/**
 * Admin Reports
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

// Only admin and super_admin can access
if (!hasAnyRole(['admin', 'super_admin'])) {
    setFlash('error', 'You do not have permission to view this page');
    redirect(ADMIN_URL . '/dashboard.php');
}

$page_title = 'Reports';

// Handle filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to 1st of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$author_id = $_GET['author_id'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$post_status = $_GET['post_status'] ?? 'published';

// Get all writers/editors for filter dropdown
global $db;
$users = $db->query("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'super_admin', 'editor', 'writer')")->fetchAll();
$categories = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Build query
$where = [];
$params = [];

if (!empty($start_date)) {
    $where[] = "DATE(p.published_at) >= :start_date";
    $params[':start_date'] = $start_date;
}

if (!empty($end_date)) {
    $where[] = "DATE(p.published_at) <= :end_date";
    $params[':end_date'] = $end_date;
}

if (!empty($author_id)) {
    $where[] = "p.author_id = :author_id";
    $params[':author_id'] = $author_id;
}

if (!empty($category_id)) {
    $where[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($post_status) && $post_status != 'all') {
    $where[] = "p.status = :post_status";
    $params[':post_status'] = $post_status;
} else if ($post_status == 'all') {
    // If all is selected, don't filter by status, but only allow reasonable statuses
    $where[] = "p.status IN ('published', 'draft', 'archived')";
} else {
    // Default fallback
    $where[] = "p.status = 'published'";
}

$where_clause = !empty($where) ? implode(' AND ', $where) : "1=1";

// Total posts and views count
$stmt_total = $db->prepare("SELECT COUNT(p.id) as total_posts, SUM(p.view_count) as total_views FROM posts p WHERE $where_clause");
$stmt_total->execute($params);
$totals = $stmt_total->fetch();
$total_posts = $totals['total_posts'] ?? 0;
$total_views = $totals['total_views'] ?? 0;

// Breakdown by author
$stmt_authors = $db->prepare("
    SELECT u.full_name, u.role, COUNT(p.id) as post_count, SUM(p.view_count) as view_count
    FROM posts p 
    JOIN users u ON p.author_id = u.id 
    WHERE $where_clause 
    GROUP BY p.author_id 
    ORDER BY post_count DESC
");
$stmt_authors->execute($params);
$author_breakdown = $stmt_authors->fetchAll();

// Breakdown by category
$stmt_cats = $db->prepare("
    SELECT c.name as category_name, COUNT(p.id) as post_count, SUM(p.view_count) as view_count
    FROM posts p 
    JOIN categories c ON p.category_id = c.id 
    WHERE $where_clause 
    GROUP BY p.category_id 
    ORDER BY post_count DESC
");
$stmt_cats->execute($params);
$category_breakdown = $stmt_cats->fetchAll();

// Generate Detailed Text string
$selected_author_name = "All Authors";
foreach($users as $u) { if($u['id'] == $author_id) $selected_author_name = $u['full_name']; }

$selected_cat_name = "All Categories";
foreach($categories as $c) { if($c['id'] == $category_id) $selected_cat_name = $c['name']; }

ob_start();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-6">
        <h2 class="text-2xl font-black text-gray-800 uppercase tracking-widest">Advanced Reports</h2>
        <button onclick="downloadPDF()" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-bold hover:bg-indigo-700 transition-all shadow-md flex items-center hover:shadow-lg transform hover:-translate-y-0.5">
            <i class="fas fa-download mr-2"></i> Export PDF
        </button>
    </div>
    
    <div id="report-content" class="space-y-6">

    <!-- Report Header (Only visible in PDF) -->
    <div class="hidden pdf-header mb-8 text-center border-b pb-4">
        <h1 class="text-3xl font-black text-gray-900 uppercase tracking-widest mb-2">Alokpat Detailed Report</h1>
        <p class="text-gray-600">Generated on <?php echo date('d M, Y h:i A'); ?></p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-r from-gray-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <form method="GET" action="" class="relative z-10 grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Start Date</label>
                <input type="date" name="start_date" value="<?php echo escape($start_date); ?>" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-gray-700 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">End Date</label>
                <input type="date" name="end_date" value="<?php echo escape($end_date); ?>" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-gray-700 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Author</label>
                <select name="author_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-gray-700 text-sm">
                    <option value="">All Authors</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $author_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo escape($u['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Category</label>
                <select name="category_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-gray-700 text-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $category_id == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo escape($c['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-gray-900 text-white px-4 py-2 rounded-lg font-bold uppercase tracking-wider hover:bg-black transition-colors shadow-sm text-sm">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Details Box -->
    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-indigo-900 text-sm">
        <i class="fas fa-info-circle mr-2 text-indigo-600"></i>
        Showing performance data from <strong><?php echo date('d M Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('d M Y', strtotime($end_date)); ?></strong>. 
        Filtered by Author: <strong><?php echo escape($selected_author_name); ?></strong> and Category: <strong><?php echo escape($selected_cat_name); ?></strong>.
    </div>

    <!-- Summary Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg transform transition-all duration-300 hover:scale-[1.02]">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-blue-100 mb-1 font-semibold uppercase tracking-widest text-xs">Total Posts</p>
                    <p class="text-5xl font-black tracking-tight"><?php echo number_format($total_posts); ?></p>
                </div>
                <div class="h-16 w-16 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20">
                    <i class="fas fa-newspaper text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-r from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg transform transition-all duration-300 hover:scale-[1.02]">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-emerald-100 mb-1 font-semibold uppercase tracking-widest text-xs">Total Views / Reads</p>
                    <p class="text-5xl font-black tracking-tight"><?php echo number_format($total_views); ?></p>
                </div>
                <div class="h-16 w-16 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20">
                    <i class="fas fa-eye text-white text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Breakdown Table (Author) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="fas fa-users mr-2 text-indigo-500"></i> Author Performance</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="py-3 px-5 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs border-b border-gray-100">Author Name</th>
                            <th class="py-3 px-5 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs border-b border-gray-100 text-center">Posts</th>
                            <th class="py-3 px-5 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs text-right border-b border-gray-100">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($author_breakdown)): ?>
                            <tr>
                                <td colspan="3" class="py-12 text-center text-gray-400 font-medium">No author data found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($author_breakdown as $row): ?>
                                <tr class="hover:bg-blue-50/50 transition-colors border-b border-gray-50 last:border-0">
                                    <td class="py-3 px-5 font-bold text-gray-800">
                                        <?php echo escape($row['full_name']); ?>
                                        <div class="text-[10px] text-gray-500 uppercase tracking-widest mt-1"><?php echo escape($row['role']); ?></div>
                                    </td>
                                    <td class="py-3 px-5 text-center font-black text-indigo-600 text-lg bg-indigo-50/30"><?php echo number_format($row['post_count']); ?></td>
                                    <td class="py-3 px-5 text-right font-black text-gray-900 text-lg"><?php echo number_format($row['view_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Breakdown Table (Category) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest"><i class="fas fa-folder-open mr-2 text-emerald-500"></i> Category Popularity</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="py-3 px-5 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs border-b border-gray-100">Category Name</th>
                            <th class="py-3 px-5 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs border-b border-gray-100 text-center">Posts</th>
                            <th class="py-3 px-5 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs text-right border-b border-gray-100">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($category_breakdown)): ?>
                            <tr>
                                <td colspan="3" class="py-12 text-center text-gray-400 font-medium">No category data found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($category_breakdown as $row): ?>
                                <tr class="hover:bg-emerald-50/50 transition-colors border-b border-gray-50 last:border-0">
                                    <td class="py-3 px-5 font-bold text-gray-800"><?php echo escape($row['category_name']); ?></td>
                                    <td class="py-3 px-5 text-center font-black text-emerald-600 text-lg bg-emerald-50/30"><?php echo number_format($row['post_count']); ?></td>
                                    <td class="py-3 px-5 text-right font-black text-gray-900 text-lg"><?php echo number_format($row['view_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div> <!-- End grid -->

    </div> <!-- End report-content wrapper -->
</div>

<!-- Include html2pdf library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<style>
/* PDF Export Mode Styles */
#report-content.pdf-mode {
    background: #fff !important;
    padding: 30px;
    font-family: 'Helvetica', 'Arial', sans-serif !important;
}
#report-content.pdf-mode form {
    display: none !important; /* Hide filters from PDF */
}
#report-content.pdf-mode .pdf-header {
    display: block !important;
}
#report-content.pdf-mode * {
    color: #333 !important;
    box-shadow: none !important;
    text-shadow: none !important;
}
#report-content.pdf-mode .bg-gradient-to-r {
    background: #f8fafc !important; /* Very light gray instead of gradient */
    border: 1px solid #e2e8f0 !important;
}
#report-content.pdf-mode .text-white {
    color: #1a202c !important; /* Dark text instead of white */
}
#report-content.pdf-mode th {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border-bottom: 2px solid #cbd5e1 !important;
}
#report-content.pdf-mode td {
    border-bottom: 1px solid #e2e8f0 !important;
}
#report-content.pdf-mode .bg-indigo-50 {
    background: #fff !important;
    border: 1px solid #000 !important;
    color: #000 !important;
}
#report-content.pdf-mode .text-indigo-600, 
#report-content.pdf-mode .text-emerald-600 {
    color: #000 !important;
}
/* Hide decorative blurs */
#report-content.pdf-mode .blur-2xl, #report-content.pdf-mode .backdrop-blur-md {
    display: none !important;
}
</style>
<script>
function downloadPDF() {
    const element = document.getElementById('report-content');
    
    // Add PDF styling class
    element.classList.add('pdf-mode');
    
    const opt = {
        margin:       [0.4, 0.4, 0.4, 0.4],
        filename:     'alokpat_report_<?php echo date('Y-m-d'); ?>.pdf',
        image:        { type: 'jpeg', quality: 1 },
        html2canvas:  { scale: 2, useCORS: true, letterRendering: true }, // Scale 2 is usually enough for English
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    
    // Generate PDF and open in new tab
    html2pdf().set(opt).from(element).outputPdf('bloburl').then(function(pdfUrl) {
        window.open(pdfUrl, '_blank');
        // Revert styling
        element.classList.remove('pdf-mode');
    });
}
</script>
<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
