<?php
require_once 'config/config.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION[CSRF_TOKEN_NAME]) {
        $error_msg = "Invalid request.";
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error_msg = "Please fill all required fields.";
        } else {
            global $db;
            try {
                $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':subject', $subject);
                $stmt->bindParam(':message', $message);
                $stmt->execute();
                $success_msg = "Thank you! Your message has been sent successfully.";
            } catch(PDOException $e) {
                error_log("Contact Form Error: " . $e->getMessage());
                $error_msg = "An error occurred while sending your message. Please try again later.";
            }
        }
    }
}

$category = new Category();
$categories = $category->getCategoriesWithCount();
$setting = new Setting();
$site_info = $setting->getSiteInfo();
$page_title = 'Contact Us';
ob_start();
component('header', ['categories' => $categories]);
?>
<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-sm border border-gray-100">
        <h1 class="text-4xl font-bold mb-6 text-gray-900 border-b pb-4">Contact Us</h1>
        
        <?php if (!empty($success_msg)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo escape($success_msg); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo escape($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="prose prose-lg max-w-none text-gray-700">
                <p>Your opinions, complaints, or suggestions are highly valuable to us. You can contact us to provide news-related information or for advertising inquiries.</p>
                
                <h3 class="text-xl font-bold mt-6 mb-2">Office Address</h3>
                <p><?php echo escape($site_info['site_address'] ?? 'Kolkata, India'); ?></p>
                
                <h3 class="text-xl font-bold mt-6 mb-2">Email</h3>
                <p><a href="mailto:<?php echo escape($site_info['site_email'] ?? 'contact@alokpat.com'); ?>"><?php echo escape($site_info['site_email'] ?? 'contact@alokpat.com'); ?></a></p>
                
                <h3 class="text-xl font-bold mt-6 mb-2">Phone</h3>
                <p><?php echo escape($site_info['site_phone'] ?? '+91 00000 00000'); ?></p>
            </div>
            
            <div class="bg-gray-50 p-6 rounded border border-gray-200">
                <h3 class="text-2xl font-bold mb-4">Send a Message</h3>
                <form action="contact.php" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                        <input type="text" name="name" class="w-full px-4 py-2 border rounded focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" class="w-full px-4 py-2 border rounded focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" name="subject" class="w-full px-4 py-2 border rounded focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea rows="4" name="message" class="w-full px-4 py-2 border rounded focus:ring-primary-500 focus:border-primary-500" required></textarea>
                    </div>
                    <button type="submit" class="w-full bg-primary-700 text-white font-bold py-3 px-4 rounded hover:bg-primary-800 transition">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</main>
<?php
component('footer');
$content = ob_get_clean();
require_once 'layouts/main.php';
?>
