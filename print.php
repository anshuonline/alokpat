<?php
/**
 * Print Article Page
 * 
 * @package Alokpath
 */
require_once 'config/config.php';
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(SITE_URL);
}

$post = new Post();
$article = $post->getBySlug($slug);

if (!$article) {
    redirect(SITE_URL);
}

$page_title = 'প্রিন্ট: ' . ($article['title'] ?? '');
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo escape($page_title); ?></title>
    <?php component('favicon'); ?>
    <link href="<?php echo SITE_FONT_URL; ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --font-family: <?php echo SITE_FONT_CSS; ?>;
        }
        body {
            font-family: var(--font-family);
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .site-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .site-header img {
            max-width: 250px;
            height: auto;
        }
        .site-header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: 900;
        }
        .article-title {
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 15px;
            text-align: center;
        }
        .article-excerpt {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            text-align: justify;
            font-weight: 500;
        }
        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .featured-image {
            width: 100%;
            height: auto;
            margin-bottom: 20px;
            display: block;
        }
        .article-content {
            font-size: 18px; /* good size for reading on paper */
            line-height: 1.8;
            text-align: justify;
        }
        .article-content img {
            max-width: 100%;
            height: auto;
            margin: 15px 0;
        }
        .article-content p {
            margin-bottom: 15px;
        }
        .print-footer {
            margin-top: 40px;
            border-top: 1px dashed #ccc;
            padding-top: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .print-controls {
            text-align: right;
            margin-bottom: 20px;
        }
        .btn-print {
            padding: 8px 16px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        @media print {
            body {
                padding: 0;
            }
            .print-controls {
                display: none;
            }
            @page {
                margin: 2cm;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        
        <div class="print-controls">
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> পেজটি প্রিন্ট করুন
            </button>
        </div>

        <div class="site-header">
            <?php 
            $setting_model = new Setting();
            $logo = $setting_model->get('site_logo');
            if ($logo): 
            ?>
                <img src="<?php echo escape($logo); ?>" alt="Alokpath Logo">
            <?php else: ?>
                <h1>আলোকপাত</h1>
            <?php endif; ?>
            <p style="margin: 5px 0 0; font-size: 14px; color: #555;">সত্যের সন্ধানে নির্ভীক</p>
        </div>

        <h1 class="article-title"><?php echo escape($article['title']); ?></h1>
        
        <?php 
        $excerpt = $article['meta_description'] ?? $article['excerpt'] ?? '';
        if (!empty($excerpt)): 
        ?>
            <p class="article-excerpt"><?php echo escape($excerpt); ?></p>
        <?php endif; ?>

        <div class="article-meta">
            <div>
                <i class="fas fa-pen-nib"></i> 
                <?php echo escape($article['author_name']); ?>
            </div>
            <div>
                <i class="far fa-calendar-alt"></i> 
                <?php echo formatDateBengali($article['published_at'] ?? $article['created_at']); ?>
            </div>
        </div>

        <?php if (!empty($article['featured_image'])): ?>
            <img src="<?php echo escape($article['featured_image']); ?>" alt="Featured Image" class="featured-image">
        <?php endif; ?>

        <div class="article-content">
            <?php echo $article['content']; ?>
        </div>

        <div class="print-footer">
            <p>&copy; <?php echo formatNumberBengali(date('Y')); ?> আলোকপাত। সর্বস্বত্ব সংরক্ষিত।</p>
            <p><strong>মূল খবর:</strong> <?php echo url_for_post($article); ?></p>
        </div>

    </div>

    <script>
        // Automatically open the print dialog when the page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
