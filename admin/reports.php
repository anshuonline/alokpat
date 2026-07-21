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
    <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-6">
        <h2 class="text-2xl font-black text-gray-800 uppercase tracking-widest">Reports & Analytics</h2>
        <button onclick="downloadPDF()" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-bold hover:bg-indigo-700 transition-all shadow-md flex items-center hover:shadow-lg transform hover:-translate-y-0.5">
            <i class="fas fa-download mr-2"></i> Export PDF
        </button>
    </div>
    
    <div id="report-content" class="space-y-6">

    <!-- Report Header (Only visible in PDF) -->
    <div class="hidden pdf-header mb-8 text-center border-b pb-4">
        <h1 class="text-3xl font-black text-gray-900 uppercase tracking-widest mb-2">Alokpat Reports</h1>
        <p class="text-gray-600">Generated on <?php echo date('d M, Y'); ?></p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative overflow-hidden group">
        <div class="absolute inset-0 bg-gradient-to-r from-gray-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <form method="GET" action="" class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-5 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Start Date</label>
                <input type="date" name="start_date" value="<?php echo escape($start_date); ?>" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-gray-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">End Date</label>
                <input type="date" name="end_date" value="<?php echo escape($end_date); ?>" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-gray-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">Author</label>
                <select name="author_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-semibold text-gray-700">
                    <option value="">All Authors</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $author_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo escape($u['full_name']); ?> (<?php echo escape($u['role']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="w-full bg-gray-900 text-white px-4 py-2.5 rounded-lg font-bold uppercase tracking-wider hover:bg-black transition-colors shadow-sm">
                    <i class="fas fa-filter mr-2"></i> Filter Data
                </button>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg transform transition-all duration-300 hover:scale-[1.02]">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-blue-100 mb-1 font-semibold uppercase tracking-widest text-xs">Total Published Posts</p>
                    <p class="text-5xl font-black tracking-tight"><?php echo number_format($total_posts); ?></p>
                </div>
                <div class="h-16 w-16 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/20">
                    <i class="fas fa-chart-line text-white text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-widest">Author Performance Breakdown</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="py-4 px-6 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs border-b border-gray-100">Author Name</th>
                        <th class="py-4 px-6 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs border-b border-gray-100">Role</th>
                        <th class="py-4 px-6 bg-white font-bold text-gray-500 uppercase tracking-wider text-xs text-right border-b border-gray-100">Total Posts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($author_breakdown)): ?>
                        <tr>
                            <td colspan="3" class="py-12 text-center text-gray-400 font-medium">No data found for the selected filters</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($author_breakdown as $row): ?>
                            <tr class="hover:bg-blue-50/50 transition-colors border-b border-gray-50 last:border-0">
                                <td class="py-4 px-6 font-bold text-gray-800"><?php echo escape($row['full_name']); ?></td>
                                <td class="py-4 px-6"><span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded text-xs uppercase tracking-widest font-bold"><?php echo escape($row['role']); ?></span></td>
                                <td class="py-4 px-6 text-right font-black text-gray-900 text-xl"><?php echo number_format($row['post_count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

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
        html2canvas:  { scale: 2, useCORS: true, letterRendering: true }, // Scale 2 is usually enough for English, prevents massive files
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
