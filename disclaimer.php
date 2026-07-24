<?php
require_once 'config/config.php';
$category = new Category();
$categories = $category->getCategoriesWithCount();
$page_title = 'Disclaimer';
ob_start();
component('header', ['categories' => $categories]);
?>
<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-sm border border-gray-100">
        <h1 class="text-4xl font-bold mb-6 text-gray-900 border-b pb-4">Disclaimer</h1>
        <div class="prose prose-lg max-w-none text-gray-700">
            <p>All information published on <strong>Alokpat</strong> is provided in good faith and for general informational and public awareness purposes only. While we strive to ensure the accuracy of the information, we make no warranties about the completeness, reliability, and accuracy of this information.</p>
            <h2>Accuracy of Information</h2>
            <p>The reports, opinions, or analyses published on our site strictly reflect the personal or professional views of the respective authors or journalists. The Alokpat authority shall not be held liable for any losses or damages in connection with the use of our website.</p>
            <h2>External Links</h2>
            <p>From our website, you can visit other websites by following hyperlinks to such external sites (including advertisements or references). While we strive to provide only quality links, we have no control over the content and nature of these sites. Entering these websites is solely at your own risk.</p>
            <h2>Medical, Legal & Professional Advice</h2>
            <p>Content published in our health, economy, or legal sections is strictly for informational purposes. It is not a substitute for professional advice. Always consult a relevant expert before making any crucial decisions based on our content.</p>
            <p>By using our website, you hereby consent to our disclaimer and agree to its terms.</p>
            <h2>Contact</h2>
            <p>For any questions regarding this disclaimer, please email us at <a href="mailto:support@alokpat.in" class="text-blue-600 hover:underline">support@alokpat.in</a>.</p>
        </div>
    </div>
</main>
<?php
component('footer');
$content = ob_get_clean();
require_once 'layouts/main.php';
?>
