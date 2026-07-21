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
    setFlash('error', 'এই পেজটি দেখার অনুমতি আপনার নেই');
    redirect(ADMIN_URL . '/dashboard.php');
}

$page_title = 'রিপোর্টস';

// Handle filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to 1st of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$author_id = $_GET['author_id'] ?? '';

// Get all writers/editors for filter dropdown
global $db;
$users = $db->query("SELECT id, full_name, role FROM users WHERE role IN ('admin', 'super_admin', 'editor', 'writer')")->fetchAll();

// Build query
$where = ["status = 'published'"];
$params = [];

if (!empty($start_date)) {
    $where[] = "DATE(published_at) >= :start_date";
    $params[':start_date'] = $start_date;
}

if (!empty($end_date)) {
    $where[] = "DATE(published_at) <= :end_date";
    $params[':end_date'] = $end_date;
}

if (!empty($author_id)) {
    $where[] = "author_id = :author_id";
    $params[':author_id'] = $author_id;
}

$where_clause = implode(' AND ', $where);

// Total posts count
$stmt_total = $db->prepare("SELECT COUNT(id) as total FROM posts WHERE $where_clause");
$stmt_total->execute($params);
$total_posts = $stmt_total->fetch()['total'] ?? 0;

// Breakdown by author
$stmt_authors = $db->prepare("
    SELECT u.full_name, u.role, COUNT(p.id) as post_count 
    FROM posts p 
    JOIN users u ON p.author_id = u.id 
    WHERE $where_clause 
    GROUP BY p.author_id 
    ORDER BY post_count DESC
");
$stmt_authors->execute($params);
$author_breakdown = $stmt_authors->fetchAll();

ob_start();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between border-b-2 border-black pb-4 mb-6">
        <h2 class="text-3xl font-black text-black uppercase tracking-widest">রিপোর্টস</h2>
        <button onclick="downloadPDF()" class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-red-700 transition-colors shadow-sm flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> PDF ডাউনলোড
        </button>
    </div>
    
    <div id="report-content" class="space-y-6">

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-r from-gray-50 to-gray-100 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <form method="GET" action="" class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">শুরুর তারিখ</label>
                <input type="date" name="start_date" value="<?php echo escape($start_date); ?>" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-0 focus:border-black outline-none transition-all font-semibold text-gray-700">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">শেষ তারিখ</label>
                <input type="date" name="end_date" value="<?php echo escape($end_date); ?>" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-0 focus:border-black outline-none transition-all font-semibold text-gray-700">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">লেখক</label>
                <select name="author_id" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-0 focus:border-black outline-none transition-all font-semibold text-gray-700">
                    <option value="">সকল লেখক</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $author_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo escape($u['full_name']); ?> (<?php echo escape($u['role']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-black text-white px-4 py-2.5 rounded-lg font-black uppercase tracking-wider hover:bg-gray-800 transition-colors shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <i class="fas fa-filter mr-2"></i> ফিল্টার করুন
                </button>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-[0_8px_30px_rgba(99,102,241,0.4)] hover:-translate-y-1 transition-transform duration-300 group">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-indigo-100 mb-1 font-medium uppercase tracking-wider text-sm">মোট সংবাদ (প্রকাশিত)</p>
                    <p class="text-5xl font-black drop-shadow-sm"><?php echo formatNumberBengali($total_posts); ?></p>
                </div>
                <div class="h-20 w-20 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20 group-hover:rotate-12 transition-transform duration-300">
                    <i class="fas fa-chart-line text-white text-4xl drop-shadow-md"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 overflow-hidden">
        <h3 class="text-lg font-black text-black mb-4 uppercase tracking-widest border-b border-gray-100 pb-2">লেখকদের বিস্তারিত রিপোর্ট</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="py-4 px-6 bg-gray-50 font-black text-gray-700 border-b-2 border-gray-200 uppercase tracking-wider text-sm">লেখক</th>
                        <th class="py-4 px-6 bg-gray-50 font-black text-gray-700 border-b-2 border-gray-200 uppercase tracking-wider text-sm">রোল</th>
                        <th class="py-4 px-6 bg-gray-50 font-black text-gray-700 border-b-2 border-gray-200 uppercase tracking-wider text-sm text-right">মোট প্রকাশিত সংবাদ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($author_breakdown)): ?>
                        <tr>
                            <td colspan="3" class="py-8 text-center text-gray-500 font-medium">কোনো ডাটা পাওয়া যায়নি</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($author_breakdown as $row): ?>
                            <tr class="hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0 group cursor-pointer">
                                <td class="py-4 px-6 font-bold text-gray-800 group-hover:text-black transition-colors"><?php echo escape($row['full_name']); ?></td>
                                <td class="py-4 px-6 text-xs text-gray-500 uppercase tracking-widest font-bold"><?php echo escape($row['role']); ?></td>
                                <td class="py-4 px-6 text-right font-black text-black text-xl"><?php echo formatNumberBengali($row['post_count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div> <!-- End Breakdown Table -->

    </div> <!-- End report-content wrapper -->
</div>

<!-- Include html2pdf library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    const element = document.getElementById('report-content');
    const opt = {
        margin:       [0.5, 0.5, 0.5, 0.5],
        filename:     'alokpat_report_<?php echo date('Y-m-d'); ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    
    // Temporarily hide filter button/form for cleaner PDF if desired, or keep it.
    html2pdf().set(opt).from(element).save();
}
</script>

<?php
$content = ob_get_clean();
include 'layouts/admin.php';
?>
