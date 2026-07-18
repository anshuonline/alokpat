<?php
require_once 'config/config.php';
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
                <form action="#" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                        <input type="text" class="w-full px-4 py-2 border rounded focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" class="w-full px-4 py-2 border rounded focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" class="w-full px-4 py-2 border rounded focus:ring-primary-500 focus:border-primary-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea rows="4" class="w-full px-4 py-2 border rounded focus:ring-primary-500 focus:border-primary-500" required></textarea>
                    </div>
                    <button type="button" onclick="alert('Thank you! Your message has been sent.')" class="w-full bg-primary-700 text-white font-bold py-3 px-4 rounded hover:bg-primary-800 transition">Send Message</button>
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
