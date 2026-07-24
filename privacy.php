<?php
require_once 'config/config.php';
$category = new Category();
$categories = $category->getCategoriesWithCount();
$page_title = 'Privacy Policy';
ob_start();
component('header', ['categories' => $categories]);
?>
<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-sm border border-gray-100">
        <h1 class="text-4xl font-bold mb-6 text-gray-900 border-b pb-4">Privacy Policy</h1>
        <div class="prose prose-lg max-w-none text-gray-700">
            <p>Welcome to the Privacy Policy for <strong>Alokpat</strong>. The security of your personal information is of the utmost importance to us.</p>
            <h2>Information Collection</h2>
            <p>When you visit our site, we collect standard log information including your IP address, browser type, time of visit, and basic analytical data through cookies. This helps us improve the quality of our website and services.</p>
            <h2>Cookies and Web Beacons</h2>
            <p>Our website uses cookies. Cookies are small text files stored on your device. Third-party vendors, including Google AdSense, use cookies (such as the DART cookie) to serve ads based on a user's prior visits to our website or other websites on the internet.</p>
            <h2>Advertising (Google AdSense)</h2>
            <p>We serve Google AdSense advertisements on our website. Google's use of advertising cookies enables it and its partners to serve ads based on your visit to our site and/or other sites on the Internet. You may opt out of personalized advertising by visiting Google's Ads Settings.</p>
            <h2>Data Protection</h2>
            <p>We ensure the highest level of security for the data we collect. Under no circumstances do we sell or share your personal information with third parties (unless required by law).</p>
            <h2>Changes to the Privacy Policy</h2>
            <p>We reserve the right to modify or update this Privacy Policy at any time. Any changes will be posted and updated on this page immediately.</p>
            <p>If you have any questions, please <a href="<?php echo SITE_URL; ?>/contact.php" class="text-blue-600 hover:underline">Contact Us</a> or email us directly at <a href="mailto:support@alokpat.in" class="text-blue-600 hover:underline">support@alokpat.in</a>.</p>
        </div>
    </div>
</main>
<?php
component('footer');
$content = ob_get_clean();
require_once 'layouts/main.php';
?>
