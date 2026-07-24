<?php
require_once 'config/config.php';
$category = new Category();
$categories = $category->getCategoriesWithCount();
$page_title = 'Terms & Conditions';
ob_start();
component('header', ['categories' => $categories]);
?>
<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-sm border border-gray-100">
        <h1 class="text-4xl font-bold mb-6 text-gray-900 border-b pb-4">Terms and Conditions</h1>
        <div class="prose prose-lg max-w-none text-gray-700">
            <p>Please read the following terms and conditions carefully before using the <strong>Alokpat</strong> website.</p>
            <h2>Acceptance of Terms</h2>
            <p>By accessing or using this website, you agree to be bound by these Terms and Conditions. If you disagree with any part of these terms, please refrain from using our website.</p>
            <h2>Copyright and Intellectual Property</h2>
            <p>All news, articles, images, logos, and other content published on this website are the property of <strong>Alokpat</strong>. Copying, reproducing, or using any content for commercial purposes without our written permission is strictly prohibited and unlawful.</p>
            <h2>User Conduct</h2>
            <p>Please refrain from posting any obscene, defamatory, threatening, or illegal comments in our comments section or social media platforms. We reserve the right to delete any objectionable comments and block offending users without prior notice.</p>
            <h2>Disclaimer</h2>
            <p>While we strive to provide accurate information, we do not guarantee the 100% accuracy, completeness, or timeliness of any news report. The responsibility for verifying information before making any decisions based on news reports lies entirely with you.</p>
            <h2>Changes to Terms</h2>
            <p>We reserve the right to modify or amend these terms and conditions at any time without prior announcement.</p>
            <h2>Contact Information</h2>
            <p>If you have any queries regarding any of our terms, please contact us at <a href="mailto:support@alokpat.in" class="text-blue-600 hover:underline">support@alokpat.in</a>.</p>
        </div>
    </div>
</main>
<?php
component('footer');
$content = ob_get_clean();
require_once 'layouts/main.php';
?>
