<?php
/**
 * Reusable News Card Component
 * 
 * @package Alokpath
 */

$variant = $variant ?? 'default'; // default, horizontal, magazine-main, magazine-list, classic-list, featured
$theme = $theme ?? 'light'; // light, dark

// Handle case where post is an object
if (is_object($post)) {
    $post = (array) $post;
}

// Theme classes
$bgClass = $theme === 'dark' ? 'bg-gray-900 border border-gray-800' : 'bg-white';
$titleClass = $theme === 'dark' ? 'text-white hover:text-primary-400' : 'text-gray-800 hover:text-primary-600';
$excerptClass = $theme === 'dark' ? 'text-gray-300' : 'text-gray-600';
$metaClass = $theme === 'dark' ? 'text-gray-400' : 'text-gray-500';
$categoryClass = $theme === 'dark' ? 'bg-gray-800 text-primary-400 border border-gray-700' : 'bg-primary-100 text-primary-600';

// Image Logic
$hasImage = !empty($post['featured_image']);
$settingModel = new Setting();
$site_info = $settingModel->getSiteInfo();
$imgSrc = $hasImage ? escape($post['featured_image']) : escape($site_info['site_logo'] ?? '');
$imgClass = $hasImage ? 'object-contain' : 'object-contain p-4 animate-pulse opacity-30 bg-gray-50';

// Badge Logic
$badgeHtml = '';
if (!empty($post['is_breaking'])) {
    $badgeHtml = '<div class="absolute top-2 left-2 z-10"><span class="px-2 py-0.5 sm:px-3 sm:py-1 bg-red-600 text-white text-[10px] sm:text-xs font-bold rounded shadow-lg animate-pulse flex items-center"><span class="w-1.5 h-1.5 rounded-full bg-white mr-1.5"></span>ব্রেকিং</span></div>';
} elseif (!empty($post['is_trending'])) {
    $badgeHtml = '<div class="absolute top-2 left-2 z-10"><span class="px-2 py-0.5 sm:px-3 sm:py-1 bg-orange-500 text-white text-[10px] sm:text-xs font-bold rounded shadow-lg flex items-center"><i class="fas fa-fire mr-1.5"></i>ট্রেন্ডিং</span></div>';
} elseif (!empty($post['is_featured'])) {
    $badgeHtml = '<div class="absolute top-2 left-2 z-10"><span class="px-2 py-0.5 sm:px-3 sm:py-1 bg-primary-600 text-white text-[10px] sm:text-xs font-bold rounded shadow-lg flex items-center"><i class="fas fa-star text-[10px] mr-1.5 text-yellow-300"></i>ফিচার্ড</span></div>';
}
?>

<?php if ($variant === 'magazine-main'): ?>
    <!-- Magazine Main (Large Image Top, Title Below) -->
    <div class="group h-full flex flex-col <?php echo $bgClass; ?> <?php echo $theme === 'dark' ? 'p-4 rounded-lg' : ''; ?>">
        <div class="relative overflow-hidden rounded mb-3 bg-gray-50 flex items-center justify-center aspect-video w-full">
            <a href="<?php echo url_for_post($post); ?>" class="block w-full h-full">
                <img src="<?php echo $imgSrc; ?>" 
                     alt="<?php echo escape($post['title']); ?>" 
                     class="w-full h-full <?php echo $imgClass; ?> object-cover group-hover:scale-105 transition duration-500"
                     loading="lazy">
            </a>
            <?php echo $badgeHtml; ?>
        </div>
        <h3 class="text-xl md:text-2xl font-semibold leading-tight <?php echo $titleClass; ?> mb-2 transition">
            <a href="<?php echo url_for_post($post); ?>">
                <?php echo escape($post['title']); ?>
            </a>
        </h3>
        <?php if (!empty($post['excerpt'])): ?>
            <p class="<?php echo $excerptClass; ?> line-clamp-3">
                <?php echo escape(truncateText($post['excerpt'], 180)); ?>
            </p>
        <?php endif; ?>
    </div>

<?php elseif ($variant === 'magazine-list'): ?>
    <!-- Magazine List (Small Thumbnail Left, Title Right, Dashed Border) -->
    <div class="flex items-center py-4 border-b border-dashed <?php echo $theme === 'dark' ? 'border-gray-700' : 'border-gray-300'; ?> group last:border-0">
        <div class="relative w-28 aspect-video flex-shrink-0 overflow-hidden rounded mr-4 bg-gray-50 flex items-center justify-center">
            <a href="<?php echo url_for_post($post); ?>" class="block w-full h-full">
                <img src="<?php echo $imgSrc; ?>" 
                     alt="<?php echo escape($post['title']); ?>" 
                     class="w-full h-full <?php echo $imgClass; ?> object-cover group-hover:scale-110 transition duration-300"
                     loading="lazy">
            </a>
            <?php echo $badgeHtml; ?>
        </div>
        <div class="flex-1">
            <h3 class="text-base md:text-lg font-semibold leading-snug <?php echo $titleClass; ?> transition line-clamp-2">
                <a href="<?php echo url_for_post($post); ?>">
                    <?php echo escape($post['title']); ?>
                </a>
            </h3>
        </div>
    </div>

<?php elseif ($variant === 'classic-list'): ?>
    <!-- Classic List View (Image Left, Content Right, Dashed Border) -->
    <div class="flex flex-col md:flex-row py-6 border-b border-dashed <?php echo $theme === 'dark' ? 'border-gray-700' : 'border-gray-300'; ?> group last:border-0">
        <div class="relative w-full md:w-[280px] aspect-video flex-shrink-0 overflow-hidden mb-4 md:mb-0 md:mr-6 bg-gray-50 flex items-center justify-center">
            <a href="<?php echo url_for_post($post); ?>" class="block w-full h-full">
                <img src="<?php echo $imgSrc; ?>" 
                     alt="<?php echo escape($post['title']); ?>" 
                     class="w-full h-full <?php echo $imgClass; ?> object-cover group-hover:scale-105 transition duration-500"
                     loading="lazy">
            </a>
            <?php echo $badgeHtml; ?>
        </div>
        <div class="flex-1 flex flex-col justify-center">
            <h3 class="text-xl md:text-2xl font-semibold leading-tight <?php echo $titleClass; ?> mb-2 transition">
                <a href="<?php echo url_for_post($post); ?>">
                    <?php echo escape($post['title']); ?>
                </a>
            </h3>
            <?php if (!empty($post['excerpt'])): ?>
                <p class="<?php echo $excerptClass; ?> mb-3 line-clamp-2">
                    <?php echo escape(truncateText($post['excerpt'], 180)); ?>
                </p>
            <?php endif; ?>
            
            <div class="flex items-center space-x-4 mt-auto">
                <?php if (!empty($post['category_name'])): ?>
                    <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($post['category_slug']); ?>" 
                       class="inline-flex items-center px-3 py-1 bg-primary-600 text-white text-xs font-semibold rounded-full hover:bg-primary-700 transition">
                        <i class="far fa-newspaper mr-1"></i>
                        <?php echo escape($post['category_name']); ?>
                    </a>
                <?php endif; ?>
                <span class="text-sm font-semibold text-gray-700">
                    <?php echo timeAgoBengali($post['published_at'] ?? $post['created_at']); ?>
                </span>
            </div>
            
            <div class="mt-3">
                <a href="<?php echo url_for_post($post); ?>" class="text-gray-400 hover:text-primary-600 text-sm font-semibold flex items-center transition w-fit group-hover:text-primary-500">
                    বিস্তারিত <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>
        </div>
    </div>

<?php elseif ($variant === 'horizontal'): ?>
    <!-- Horizontal Card -->
    <div class="<?php echo $bgClass; ?> rounded-lg shadow-md hover:shadow-xl transition overflow-hidden">
        <div class="flex">
            <?php if (!empty($post['featured_image'])): ?>
                <div class="relative w-40 sm:w-48 aspect-video flex-shrink-0 overflow-hidden">
                    <a href="<?php echo url_for_post($post); ?>" class="block w-full h-full">
                        <img src="<?php echo escape($post['featured_image']); ?>" 
                             alt="<?php echo escape($post['featured_image_alt'] ?? $post['title']); ?>" 
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                             loading="lazy">
                    </a>
                    <?php echo $badgeHtml; ?>
                </div>
            <?php endif; ?>
            <div class="flex-1 p-4">
                <?php if (!empty($post['category_name'])): ?>
                    <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($post['category_slug']); ?>" 
                       class="inline-block px-2 py-1 <?php echo $categoryClass; ?> text-xs font-semibold rounded mb-2 hover:opacity-80 transition">
                        <?php echo escape($post['category_name']); ?>
                    </a>
                <?php endif; ?>
                <h3 class="text-lg font-semibold <?php echo $titleClass; ?> transition line-clamp-2">
                    <a href="<?php echo url_for_post($post); ?>">
                        <?php echo escape($post['title']); ?>
                    </a>
                </h3>
                <?php if (!empty($post['excerpt'])): ?>
                    <p class="<?php echo $excerptClass; ?> text-sm mt-2 line-clamp-2">
                        <?php echo escape(truncateText($post['excerpt'], 120)); ?>
                    </p>
                <?php endif; ?>
                <div class="flex items-center justify-between mt-3 text-xs <?php echo $metaClass; ?>">
                    <span>
                        <i class="fas fa-user mr-1"></i>
                        <?php echo escape($post['author_name'] ?? $post['author'] ?? 'অপরিচিত'); ?>
                    </span>
                    <span>
                        <i class="fas fa-clock mr-1"></i>
                        <?php echo timeAgoBengali($post['published_at'] ?? $post['created_at']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($variant === 'featured'): ?>
    <!-- Featured Card -->
    <div class="relative rounded-xl shadow-xl overflow-hidden group aspect-video">
        <?php if (!empty($post['featured_image'])): ?>
            <a href="<?php echo url_for_post($post); ?>" class="block w-full h-full">
                <img src="<?php echo escape($post['featured_image']); ?>" 
                     alt="<?php echo escape($post['featured_image_alt'] ?? $post['title']); ?>" 
                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                     loading="lazy">
            </a>
        <?php else: ?>
            <div class="w-full h-full bg-gradient-to-br from-primary-500 to-purple-600"></div>
        <?php endif; ?>
        
        <?php echo $badgeHtml; ?>
        
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent opacity-80 group-hover:opacity-100 transition duration-500 pointer-events-none"></div>
        
        <div class="absolute bottom-0 left-0 right-0 p-6 pointer-events-none">
            <?php if (!empty($post['category_name'])): ?>
                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($post['category_slug']); ?>" 
                   class="inline-block px-3 py-1 bg-primary-600 text-white text-sm font-semibold rounded mb-3 hover:bg-primary-700 transition pointer-events-auto">
                    <?php echo escape($post['category_name']); ?>
                </a>
            <?php endif; ?>
            
            <h2 class="text-3xl font-semibold text-white mb-3 hover:text-primary-300 transition pointer-events-auto">
                <a href="<?php echo url_for_post($post); ?>">
                    <?php echo escape($post['title']); ?>
                </a>
            </h2>
            
            <?php if (!empty($post['excerpt'])): ?>
                <p class="text-gray-200 line-clamp-2 mb-4 pointer-events-auto">
                    <?php echo escape(truncateText($post['excerpt'], 150)); ?>
                </p>
            <?php endif; ?>
            
            <div class="flex items-center space-x-4 text-sm text-gray-300 pointer-events-auto">
                <span>
                    <i class="fas fa-user mr-1"></i>
                    <?php echo escape($post['author_name'] ?? $post['author'] ?? 'অপরিচিত'); ?>
                </span>
                <span>
                    <i class="fas fa-clock mr-1"></i>
                    <?php echo timeAgoBengali($post['published_at'] ?? $post['created_at']); ?>
                </span>
                <span>
                    <i class="fas fa-eye mr-1"></i>
                    <?php echo formatNumberBengali($post['view_count']); ?>
                </span>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Default Vertical Card -->
    <div class="<?php echo $bgClass; ?> rounded-lg shadow-md hover:shadow-xl transition overflow-hidden flex flex-col h-full">
        <?php if (!empty($post['featured_image'])): ?>
            <div class="relative overflow-hidden group aspect-video">
                <a href="<?php echo url_for_post($post); ?>" class="block w-full h-full">
                    <img src="<?php echo escape($post['featured_image']); ?>" 
                         alt="<?php echo escape($post['featured_image_alt'] ?? $post['title']); ?>" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                         loading="lazy">
                </a>
                <?php echo $badgeHtml; ?>
            </div>
        <?php else: ?>
            <div class="relative overflow-hidden group aspect-video bg-gray-50 flex items-center justify-center">
                <a href="<?php echo url_for_post($post); ?>" class="block w-full h-full flex items-center justify-center">
                    <img src="<?php echo $imgSrc; ?>" alt="logo" class="h-20 opacity-30 object-contain">
                </a>
                <?php echo $badgeHtml; ?>
            </div>
        <?php endif; ?>
        
        <div class="p-4 flex flex-col flex-1">
            <?php if (!empty($post['category_name'])): ?>
                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($post['category_slug']); ?>" 
                   class="inline-block px-2 py-1 <?php echo $categoryClass; ?> text-xs font-semibold rounded mb-2 hover:opacity-80 transition">
                    <?php echo escape($post['category_name']); ?>
                </a>
            <?php endif; ?>
            
            <h3 class="text-xl font-semibold <?php echo $titleClass; ?> transition line-clamp-2">
                <a href="<?php echo url_for_post($post); ?>">
                    <?php echo escape($post['title']); ?>
                </a>
            </h3>
            
            <?php if (!empty($post['excerpt'])): ?>
                <p class="<?php echo $excerptClass; ?> text-sm mt-2 line-clamp-3">
                    <?php echo escape(truncateText($post['excerpt'], 150)); ?>
                </p>
            <?php endif; ?>
            
            <div class="flex items-center justify-between mt-auto pt-4 border-t <?php echo $theme === 'dark' ? 'border-gray-800' : 'border-gray-100'; ?> text-xs <?php echo $metaClass; ?>">
                <span>
                    <i class="fas fa-user mr-1"></i>
                    <?php echo escape($post['author_name'] ?? $post['author'] ?? 'অপরিচিত'); ?>
                </span>
                <span>
                    <i class="fas fa-clock mr-1"></i>
                    <?php echo timeAgoBengali($post['published_at'] ?? $post['created_at']); ?>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>
