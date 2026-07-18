<?php
require_once '../config/config.php';
requireAuth();
requireRole('admin');

global $db;

// Auto-create table if not exists
try {
    $sql = "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $db->exec($sql);
} catch (PDOException $e) {
    error_log("Failed to create contact_messages table: " . $e->getMessage());
}

// Handle Delete Action
if (isset($_POST['delete_message']) && isset($_POST['id']) && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] === $_SESSION[CSRF_TOKEN_NAME]) {
        try {
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = :id");
            $stmt->bindParam(':id', $_POST['id'], PDO::PARAM_INT);
            if ($stmt->execute()) {
                setFlash('success', 'মেসেজটি মুছে ফেলা হয়েছে।');
            } else {
                setFlash('error', 'মেসেজ মুছে ফেলতে সমস্যা হয়েছে।');
            }
        } catch (PDOException $e) {
            setFlash('error', 'ডেটাবেস এরর।');
        }
    }
    redirect(ADMIN_URL . '/contacts.php');
}

// Fetch Messages
$messages = [];
try {
    $stmt = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setFlash('error', 'মেসেজ লোড করতে সমস্যা হয়েছে।');
}

$page_title = 'যোগাযোগ (Contact Messages)';
ob_start();
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Contact Messages</h1>
            <p class="text-gray-600 mt-1">ওয়েবসাইটের কন্টাক্ট ফর্ম থেকে আসা মেসেজ সমূহ</p>
        </div>
    </div>

    <?php displayFlash(); ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-sm text-gray-600 uppercase tracking-wider">
                        <th class="p-4 font-semibold w-16">ID</th>
                        <th class="p-4 font-semibold w-1/4">Name & Email</th>
                        <th class="p-4 font-semibold w-1/4">Subject</th>
                        <th class="p-4 font-semibold">Message</th>
                        <th class="p-4 font-semibold w-32">Date</th>
                        <th class="p-4 font-semibold w-24 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                    <p>কোনো মেসেজ পাওয়া যায়নি।</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-4 text-gray-500">#<?php echo $msg['id']; ?></td>
                                <td class="p-4">
                                    <div class="font-bold text-gray-900"><?php echo escape($msg['name']); ?></div>
                                    <div class="text-xs text-gray-500"><a href="mailto:<?php echo escape($msg['email']); ?>" class="hover:text-primary-600"><?php echo escape($msg['email']); ?></a></div>
                                </td>
                                <td class="p-4 font-medium text-gray-800">
                                    <?php echo escape($msg['subject']); ?>
                                </td>
                                <td class="p-4 text-gray-600">
                                    <div class="max-h-24 overflow-y-auto pr-2 custom-scrollbar">
                                        <?php echo nl2br(escape($msg['message'])); ?>
                                    </div>
                                </td>
                                <td class="p-4 text-xs text-gray-500">
                                    <?php echo date('d M, Y h:i A', strtotime($msg['created_at'])); ?>
                                </td>
                                <td class="p-4 text-center">
                                    <form action="<?php echo ADMIN_URL; ?>/contacts.php" method="POST" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই মেসেজটি মুছে ফেলতে চান?');" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" name="delete_message" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded transition-colors" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1; 
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8; 
}
</style>

<?php
$content = ob_get_clean();
require_once 'layouts/admin.php';
?>
