<?php
require_once 'config/config.php';
$category = new Category();
$categories = $category->getCategoriesWithCount();
$page_title = '404 - Page Not Found';
ob_start();
component('header', ['categories' => $categories]);
?>
<main class="container mx-auto px-4 py-16 flex flex-col items-center justify-center min-h-[60vh]">
    <div class="max-w-xl mx-auto bg-white p-10 rounded-2xl shadow-lg border border-gray-100 text-center">
        <h1 class="text-9xl font-black text-primary-100 mb-4 animate-pulse">404</h1>
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Oops! Page Not Found</h2>
        <p class="text-gray-600 mb-8 text-lg">
            The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
        </p>
        <a href="<?php echo SITE_URL; ?>" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 transition-colors duration-300">
            <i class="fas fa-home mr-2"></i> Back to Homepage
        </a>
    </div>
</main>
<?php
component('footer');
$content = ob_get_clean();
require_once 'layouts/main.php';
?>
