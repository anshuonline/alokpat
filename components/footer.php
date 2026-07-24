<?php
/**
 * Footer Component
 */

$setting = new Setting();
$site_info = $setting->getSiteInfo();
$category = new Category();
$categories = $category->getActive();
?>

<footer class="bg-gray-900 text-white mt-12">
    
    <!-- Main Footer -->
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- About -->
            <div>
                <?php if (!empty($site_info['footer_logo'])): ?>
                    <img src="<?php echo escape($site_info['footer_logo']); ?>" alt="Footer Logo" class="h-16 w-auto mb-6 brightness-0 invert object-contain object-left">
                <?php else: ?>
                    <h3 class="text-3xl font-bold mb-6 text-white tracking-wide">
                        <?php echo escape($site_info['site_name'] ?? 'আলোকপাত'); ?>
                    </h3>
                <?php endif; ?>
                <p class="text-gray-400 leading-relaxed mb-6 text-sm">
                    সবার আগে, সবার কাছে। বস্তুনিষ্ঠ ও নিরপেক্ষ সংবাদ পরিবেশনে আমরা বদ্ধপরিকর। দেশ-বিদেশ, রাজনীতি, অর্থনীতি, বিনোদন থেকে শুরু করে প্রযুক্তি ও লাইফস্টাইলের সর্বশেষ আপডেট পেতে আমাদের সাথেই থাকুন।
                </p>
                <div class="flex space-x-4">
                    <?php if (!empty($site_info['facebook_url'])): ?>
                        <a href="<?php echo escape($site_info['facebook_url']); ?>" target="_blank" class="text-gray-400 hover:text-primary-400 transition">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['twitter_url'])): ?>
                        <a href="<?php echo escape($site_info['twitter_url']); ?>" target="_blank" class="text-gray-400 hover:text-primary-400 transition">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['youtube_url'])): ?>
                        <a href="<?php echo escape($site_info['youtube_url']); ?>" target="_blank" class="text-gray-400 hover:text-red-400 transition">
                            <i class="fab fa-youtube text-xl"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($site_info['instagram_url'])): ?>
                        <a href="<?php echo escape($site_info['instagram_url']); ?>" target="_blank" class="text-gray-400 hover:text-pink-400 transition">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-bold mb-4">দ্রুত লিংক</h4>
                <ul class="space-y-2">
                    <li>
                        <a href="<?php echo SITE_URL; ?>" class="text-gray-400 hover:text-white transition">
                            <i class="fas fa-angle-left mr-2"></i>
                            প্রচ্ছদ
                        </a>
                    </li>
                    <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($category['slug']); ?>" 
                               class="text-gray-400 hover:text-white transition">
                                <i class="fas fa-angle-left mr-2"></i>
                                <?php echo escape($category['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Categories -->
            <div>
                <h4 class="text-lg font-bold mb-4">বিভাগসমূহ</h4>
                <ul class="space-y-2">
                    <?php foreach (array_slice($categories, 5) as $category): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo escape($category['slug']); ?>" 
                               class="text-gray-400 hover:text-white transition">
                                <i class="fas fa-angle-left mr-2"></i>
                                <?php echo escape($category['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Contact -->
            <div>
                <h4 class="text-lg font-bold mb-4">যোগাযোগ</h4>
                <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-envelope mt-1 text-gray-500"></i>
                            <a href="mailto:support@alokpat.in" class="text-gray-400 hover:text-white transition">
                                support@alokpat.in
                            </a>
                        </li>
                    <?php if (!empty($site_info['site_phone'])): ?>
                        <li class="flex items-start">
                            <i class="fas fa-phone mt-1 mr-3 text-primary-400"></i>
                            <a href="tel:<?php echo escape($site_info['site_phone']); ?>" class="text-gray-400 hover:text-white transition">
                                <?php echo escape($site_info['site_phone']); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($site_info['site_address'])): ?>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-primary-400"></i>
                            <span class="text-gray-400"><?php echo escape($site_info['site_address']); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Newsletter Subscription -->
                <div class="mt-8">
                    <h5 class="text-white font-medium mb-3 text-sm uppercase tracking-wide">নিউজলেটার সাবস্ক্রাইব করুন</h5>
                    <form class="flex flex-col gap-2" action="<?php echo SITE_URL; ?>/subscribe.php" method="POST">
                        <input type="email" name="email" placeholder="আপনার ইমেইল এড্রেস" required 
                               class="bg-gray-800 border border-gray-700 text-white px-4 py-2 rounded focus:outline-none focus:border-primary-500 w-full text-sm placeholder-gray-500 transition-colors">
                        <button type="submit" 
                                class="bg-primary-600 hover:bg-primary-500 text-white font-medium px-4 py-2 rounded transition-colors w-full text-sm flex items-center justify-center">
                            <span>সাবস্ক্রাইব</span>
                            <i class="fas fa-paper-plane ml-2 text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Bottom Footer -->
    <div class="border-t border-gray-800">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <p class="text-gray-400 text-sm text-center md:text-left">
                    &copy; <?php echo date('Y'); ?> <?php echo escape($site_info['site_name'] ?? 'Alokpat'); ?>. All Rights Reserved.
                </p>
                <div class="flex flex-wrap gap-x-6 gap-y-2 mt-4 md:mt-0 justify-center md:justify-end">
                    <a href="<?php echo SITE_URL; ?>/about.php" class="text-gray-400 hover:text-white text-sm transition">About Us</a>
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="text-gray-400 hover:text-white text-sm transition">Contact</a>
                    <a href="<?php echo SITE_URL; ?>/privacy.php" class="text-gray-400 hover:text-white text-sm transition">Privacy Policy</a>
                    <a href="<?php echo SITE_URL; ?>/terms.php" class="text-gray-400 hover:text-white text-sm transition">Terms of Use</a>
                    <a href="<?php echo SITE_URL; ?>/disclaimer.php" class="text-gray-400 hover:text-white text-sm transition">Disclaimer</a>
                </div>
            </div>
        </div>
    </div>
    
</footer>
