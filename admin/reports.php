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
$where = ["p.status = 'published'"];
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

$where_clause = implode(' AND ', $where);

// Total posts count
$stmt_total = $db->prepare("SELECT COUNT(p.id) as total FROM posts p WHERE $where_clause");
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
    <div class="bg-white border-2 border-gray-800 p-6 shadow-sm">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2 uppercase tracking-widest">শুরুর তারিখ</label>
                <input type="date" name="start_date" value="<?php echo escape($start_date); ?>" class="w-full px-4 py-2 border-2 border-gray-400 rounded-none focus:ring-0 focus:border-black outline-none transition-all font-semibold text-gray-900">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2 uppercase tracking-widest">শেষ তারিখ</label>
                <input type="date" name="end_date" value="<?php echo escape($end_date); ?>" class="w-full px-4 py-2 border-2 border-gray-400 rounded-none focus:ring-0 focus:border-black outline-none transition-all font-semibold text-gray-900">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2 uppercase tracking-widest">লেখক</label>
                <select name="author_id" class="w-full px-4 py-2 border-2 border-gray-400 rounded-none focus:ring-0 focus:border-black outline-none transition-all font-semibold text-gray-900">
                    <option value="">সকল লেখক</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $author_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo escape($u['full_name']); ?> (<?php echo escape($u['role']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-gray-900 text-white px-4 py-2.5 rounded-none font-bold uppercase tracking-widest border-2 border-gray-900 hover:bg-white hover:text-gray-900 transition-colors">
                    <i class="fas fa-filter mr-2"></i> ফিল্টার করুন
                </button>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white border-2 border-gray-800 p-6 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-gray-600 mb-2 font-bold uppercase tracking-widest text-sm">মোট সংবাদ (প্রকাশিত)</p>
                <p class="text-5xl font-black text-gray-900"><?php echo formatNumberBengali($total_posts); ?></p>
            </div>
            <div class="h-20 w-20 bg-gray-100 flex items-center justify-center border-2 border-gray-800">
                <i class="fas fa-chart-line text-gray-900 text-4xl"></i>
            </div>
        </div>
    </div>

    <!-- Breakdown Table -->
    <div class="bg-white border-2 border-gray-800 shadow-sm">
        <div class="p-6 border-b-2 border-gray-800 bg-gray-50">
            <h3 class="text-lg font-black text-gray-900 uppercase tracking-widest">লেখকদের বিস্তারিত রিপোর্ট</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="py-4 px-6 bg-gray-900 font-bold text-white uppercase tracking-widest text-sm border-r border-gray-700 last:border-0">লেখক</th>
                        <th class="py-4 px-6 bg-gray-900 font-bold text-white uppercase tracking-widest text-sm border-r border-gray-700 last:border-0">রোল</th>
                        <th class="py-4 px-6 bg-gray-900 font-bold text-white uppercase tracking-widest text-sm text-right">মোট প্রকাশিত সংবাদ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($author_breakdown)): ?>
                        <tr>
                            <td colspan="3" class="py-8 text-center text-gray-600 font-bold border-b border-gray-300">কোনো ডাটা পাওয়া যায়নি</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($author_breakdown as $row): ?>
                            <tr class="hover:bg-gray-100 transition-colors border-b-2 border-gray-200 last:border-b-0 cursor-pointer">
                                <td class="py-4 px-6 font-bold text-gray-900 border-r-2 border-gray-200"><?php echo escape($row['full_name']); ?></td>
                                <td class="py-4 px-6 text-sm text-gray-600 uppercase tracking-widest font-bold border-r-2 border-gray-200"><?php echo escape($row['role']); ?></td>
                                <td class="py-4 px-6 text-right font-black text-gray-900 text-xl"><?php echo formatNumberBengali($row['post_count']); ?></td>
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
<style>
/* PDF Export Mode Styles (Black & White) */
#report-content.pdf-mode {
    background: #fff;
    padding: 20px;
}
#report-content.pdf-mode form {
    display: none !important; /* Hide filters from PDF */
}
#report-content.pdf-mode * {
    background: transparent !important;
    color: #000 !important;
    box-shadow: none !important;
    text-shadow: none !important;
}
#report-content.pdf-mode .bg-gradient-to-br,
#report-content.pdf-mode .bg-white,
#report-content.pdf-mode .bg-white\/20,
#report-content.pdf-mode .bg-gray-50 {
    background: transparent !important;
    border: 1px solid #000 !important;
}
#report-content.pdf-mode th,
#report-content.pdf-mode td {
    border-bottom: 1px solid #000 !important;
}
#report-content.pdf-mode h3 {
    border-bottom: 2px solid #000 !important;
}
/* Hide background elements */
#report-content.pdf-mode .blur-xl {
    display: none !important;
}
</style>
<script>
function downloadPDF() {
    const element = document.getElementById('report-content');
    
    // Add professional B&W layout class
    element.classList.add('pdf-mode');
    
    const opt = {
        margin:       [0.5, 0.5, 0.5, 0.5],
        filename:     'alokpat_report_<?php echo date('Y-m-d'); ?>.pdf',
        image:        { type: 'jpeg', quality: 1 },
        html2canvas:  { scale: 4, useCORS: true }, // High scale for clear text
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
