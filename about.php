<?php
require_once 'config/config.php';
$category = new Category();
$categories = $category->getCategoriesWithCount();
$page_title = 'About Us';
ob_start();
component('header', ['categories' => $categories]);
?>
<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-sm border border-gray-100">
        <h1 class="text-4xl font-bold mb-6 text-gray-900 border-b pb-4">About Us</h1>
        <div class="prose prose-lg max-w-none text-gray-700">
            <p>Welcome to <strong>Alokpat</strong>. We are a leading digital news platform committed to delivering objective, independent, and impartial news.</p>
            <p>Our goal is to bring you the latest national and international news, politics, economy, entertainment, technology, and lifestyle updates as quickly and reliably as possible.</p>
            <h2>Our Mission</h2>
            <p>To raise awareness across all levels of society by uncovering the truth and presenting accurate information. We are determined to work while maintaining the highest ethical standards of journalism.</p>
            <h2>Why Are We Different?</h2>
            <ul>
                <li><strong>Impartiality:</strong> We publish factual stories without bias toward any party or group.</li>
                <li><strong>Accuracy:</strong> Verifying facts before publication is our primary responsibility.</li>
                <li><strong>Speed:</strong> We are always vigilant to deliver breaking news and crucial updates to you first.</li>
            </ul>
            <p>Thank you for staying with us.</p>
        </div>
    </div>
</main>
<?php
component('footer');
$content = ob_get_clean();
require_once 'layouts/main.php';
?>
