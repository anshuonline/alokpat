<?php
require_once __DIR__ . '/../config/config.php';
requireAuth();

$page_title = 'ইনবক্স (Inbox)';
$user = getCurrentUser();
$db = (new Database())->getConnection();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && verifyCSRFToken($_POST['csrf_token'])) {
    
    try {
        // Action: Delete Message
        if (isset($_POST['action']) && $_POST['action'] === 'delete_message') {
            if (!hasAnyRole(['super_admin', 'admin'])) {
                setFlash('error', 'You do not have permission to delete messages.');
                redirect(ADMIN_URL . '/inbox.php');
            }
            $msg_id = (int)$_POST['message_id'];
            $stmt = $db->prepare("DELETE FROM admin_messages WHERE id = :id AND (sender_id = :uid1 OR receiver_id = :uid2)");
            if ($stmt->execute(['id' => $msg_id, 'uid1' => $user['id'], 'uid2' => $user['id']])) {
                setFlash('success', 'Message deleted successfully!');
            } else {
                setFlash('error', 'Failed to delete message.');
            }
            redirect(ADMIN_URL . '/inbox.php');
        }
        
        // Action: Mark as Read
        if (isset($_POST['action']) && $_POST['action'] === 'mark_read') {
            $msg_id = (int)$_POST['message_id'];
            $stmt = $db->prepare("UPDATE admin_messages SET is_read = 1 WHERE id = :id AND receiver_id = :uid");
            if ($stmt->execute(['id' => $msg_id, 'uid' => $user['id']])) {
                setFlash('success', 'Message marked as read');
            } else {
                setFlash('error', 'Failed to mark message as read');
            }
            redirect(ADMIN_URL . '/inbox.php');
        }

        // Action: Send Message
        if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
            if (hasAnyRole(['super_admin', 'admin'])) {
                $receiver_id = $_POST['receiver_id'];
                $message = trim($_POST['message']);
                
                if (!empty($receiver_id) && !empty($message)) {
                    $stmt = $db->prepare("INSERT INTO admin_messages (sender_id, receiver_id, message) VALUES (:sid, :rid, :msg)");
                    
                    if ($receiver_id === 'all') {
                        $all_users = $db->query("SELECT id FROM users WHERE id != " . (int)$user['id'])->fetchAll();
                        $success = true;
                        foreach ($all_users as $u) {
                            if (!$stmt->execute(['sid' => $user['id'], 'rid' => $u['id'], 'msg' => $message])) {
                                $success = false;
                            }
                        }
                        if ($success) {
                            setFlash('success', 'Message sent to all staff successfully!');
                        } else {
                            setFlash('error', 'Failed to send message to some users.');
                        }
                    } else {
                        if ($stmt->execute([
                            'sid' => $user['id'],
                            'rid' => (int)$receiver_id,
                            'msg' => $message
                        ])) {
                            setFlash('success', 'Message sent successfully!');
                        } else {
                            setFlash('error', 'Failed to send message.');
                        }
                    }
                } else {
                    setFlash('error', 'Please fill all required fields.');
                }
            } else {
                setFlash('error', 'You do not have permission to send messages.');
            }
            redirect(ADMIN_URL . '/inbox.php');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Database Error: ' . escape($e->getMessage()));
        redirect(ADMIN_URL . '/inbox.php');
    }
}

// Fetch Inbox Messages
$messages = [];
try {
    $stmt = $db->prepare("
        SELECT m.*, u.full_name as sender_name, u.avatar as sender_avatar 
        FROM admin_messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.receiver_id = :uid 
        ORDER BY m.created_at DESC
    ");
    $stmt->execute(['uid' => $user['id']]);
    $messages = $stmt->fetchAll();
} catch (Exception $e) {
    // Table may not exist yet
}

// Fetch Users for sending message and Sent Messages (if admin)
$users = [];
$sent_messages = [];
$can_send = hasAnyRole(['super_admin', 'admin']);
if ($can_send) {
    $user_stmt = $db->query("SELECT id, full_name, role FROM users WHERE id != " . (int)$user['id'] . " ORDER BY full_name ASC");
    $users = $user_stmt->fetchAll();
    
    try {
        $sent_stmt = $db->prepare("
            SELECT m.*, u.full_name as receiver_name, u.avatar as receiver_avatar 
            FROM admin_messages m 
            JOIN users u ON m.receiver_id = u.id 
            WHERE m.sender_id = :uid 
            ORDER BY m.created_at DESC
        ");
        $sent_stmt->execute(['uid' => $user['id']]);
        $sent_messages = $sent_stmt->fetchAll();
    } catch (Exception $e) {
        // Table may not exist yet
    }
}

// Build UI
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">ইনবক্স (Inbox)</h1>
        <p class="text-sm text-gray-500 mt-1">অ্যাডমিন থেকে প্রাপ্ত মেসেজগুলো এখানে দেখতে পাবেন।</p>
    </div>
    <?php if ($can_send): ?>
    <button onclick="document.getElementById('composeModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow-sm font-medium transition-colors flex items-center">
        <i class="fas fa-paper-plane mr-2"></i> নতুন মেসেজ পাঠান
    </button>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <?php if ($can_send): ?>
    <div class="border-b border-gray-200">
        <nav class="flex -mb-px" aria-label="Tabs">
            <button onclick="switchTab('inbox')" id="tab-inbox" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm border-indigo-500 text-indigo-600 bg-indigo-50/30 transition-colors">
                <i class="fas fa-inbox mr-2"></i> প্রাপ্ত মেসেজ (Inbox)
            </button>
            <button onclick="switchTab('sent')" id="tab-sent" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors">
                <i class="fas fa-paper-plane mr-2"></i> প্রেরিত (Sent)
            </button>
        </nav>
    </div>
    <?php endif; ?>

    <!-- INBOX CONTENT -->
    <div id="content-inbox" class="block">
        <?php if (count($messages) > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($messages as $msg): ?>
                    <li class="p-6 hover:bg-gray-50 transition-colors <?php echo $msg['is_read'] ? 'bg-white' : 'bg-blue-50/50'; ?>">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mr-4">
                                <?php 
                                $avatar = $msg['sender_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($msg['sender_name']).'&background=random';
                                if (strpos($avatar, 'http') !== 0) $avatar = SITE_URL . '/' . ltrim($avatar, '/');
                                ?>
                                <img src="<?php echo escape($avatar); ?>" alt="Sender" class="w-12 h-12 rounded-full border border-gray-300 shadow-sm object-cover">
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-gray-900">
                                        <?php echo escape($msg['sender_name']); ?>
                                    </h3>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-xs text-gray-500">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                        <?php if (hasAnyRole(['super_admin', 'admin'])): ?>
                                        <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete_message">
                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-600 transition-colors" title="Delete Message">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm text-gray-700 leading-relaxed whitespace-pre-wrap font-medium"><?php echo escape($msg['message']); ?></div>
                                
                                <?php if (!$msg['is_read']): ?>
                                    <div class="mt-4">
                                        <form method="POST" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-full transition-colors inline-flex items-center">
                                                <i class="fas fa-check-double mr-1.5"></i> Mark as Read
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-inbox text-5xl mb-4 text-gray-300"></i>
                <p class="text-lg font-medium">আপনার ইনবক্সে কোনো মেসেজ নেই</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- SENT CONTENT -->
    <?php if ($can_send): ?>
    <div id="content-sent" class="hidden">
        <?php if (count($sent_messages) > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($sent_messages as $msg): ?>
                    <li class="p-6 bg-white hover:bg-gray-50 transition-colors">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 mr-4 relative">
                                <?php 
                                $avatar = $msg['receiver_avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($msg['receiver_name']).'&background=random';
                                if (strpos($avatar, 'http') !== 0) $avatar = SITE_URL . '/' . ltrim($avatar, '/');
                                ?>
                                <img src="<?php echo escape($avatar); ?>" alt="Receiver" class="w-12 h-12 rounded-full border border-gray-300 shadow-sm object-cover">
                                
                                <?php if ($msg['is_read']): ?>
                                    <div class="absolute -bottom-1 -right-1 bg-green-500 text-white text-[9px] w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm" title="Read">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="absolute -bottom-1 -right-1 bg-gray-400 text-white text-[9px] w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm" title="Unread">
                                        <i class="fas fa-check"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-gray-900">
                                        <span class="text-gray-500 font-normal">To:</span> <?php echo escape($msg['receiver_name']); ?>
                                    </h3>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-xs text-gray-500">
                                            <i class="far fa-clock mr-1"></i>
                                            <?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?>
                                        </div>
                                        <?php if (hasAnyRole(['super_admin', 'admin'])): ?>
                                        <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete_message">
                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="text-red-400 hover:text-red-600 transition-colors" title="Delete Message">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-2 text-sm text-gray-700 leading-relaxed whitespace-pre-wrap"><?php echo escape($msg['message']); ?></div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-paper-plane text-5xl mb-4 text-gray-300"></i>
                <p class="text-lg font-medium">আপনি এখনও কোনো মেসেজ পাঠাননি</p>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function switchTab(tab) {
    if (tab === 'inbox') {
        document.getElementById('content-inbox').classList.remove('hidden');
        document.getElementById('content-inbox').classList.add('block');
        document.getElementById('content-sent').classList.remove('block');
        document.getElementById('content-sent').classList.add('hidden');
        
        document.getElementById('tab-inbox').className = "w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm border-indigo-500 text-indigo-600 bg-indigo-50/30 transition-colors";
        document.getElementById('tab-sent').className = "w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors";
    } else {
        document.getElementById('content-inbox').classList.remove('block');
        document.getElementById('content-inbox').classList.add('hidden');
        document.getElementById('content-sent').classList.remove('hidden');
        document.getElementById('content-sent').classList.add('block');
        
        document.getElementById('tab-sent').className = "w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm border-indigo-500 text-indigo-600 bg-indigo-50/30 transition-colors";
        document.getElementById('tab-inbox').className = "w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors";
    }
}
</script>

<?php if ($can_send): ?>
<!-- Compose Modal -->
<div id="composeModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm overflow-y-auto">
    <div class="min-h-screen px-4 text-center">
        <span class="inline-block h-screen align-middle" aria-hidden="true">&#8203;</span>
        <div class="inline-block w-full max-w-lg p-6 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl relative">
            
            <button type="button" onclick="document.getElementById('composeModal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>

            <h3 class="text-xl font-bold text-gray-900 mb-6">নতুন মেসেজ পাঠান</h3>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="send_message">
                
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">প্রাপক (Receiver)</label>
                    <select name="receiver_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                        <option value="">স্টাফ নির্বাচন করুন...</option>
                        <option value="all" class="font-bold text-indigo-600">সবাইকে পাঠান (All Staff)</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>">
                                <?php echo escape($u['full_name']); ?> (<?php echo ucfirst(str_replace('_', ' ', $u['role'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">মেসেজ (Message)</label>
                    <textarea name="message" rows="5" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all resize-none placeholder-gray-400" placeholder="আপনার মেসেজ এখানে লিখুন..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('composeModal').classList.add('hidden')" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        বাতিল করুন
                    </button>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm inline-flex items-center">
                        <i class="fas fa-paper-plane mr-2"></i> পাঠান
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/admin.php';
?>
