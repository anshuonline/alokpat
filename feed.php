<?php
/**
 * RSS 2.0 Feed Generator for Google News Publisher Center
 */
require_once 'config/config.php';
require_once 'database/Database.php';
require_once 'models/Post.php';
require_once 'models/Category.php';
require_once 'helpers/functions.php';

header("Content-Type: application/rss+xml; charset=UTF-8");

$postModel = new Post();
$categoryId = null;
$categoryName = '';

if (!empty($_GET['category'])) {
    $categoryModel = new Category();
    $category = $categoryModel->getBySlug(trim($_GET['category']));
    if ($category) {
        $categoryId = $category['id'];
        $categoryName = ' - ' . $category['name'];
    }
}

// Get the latest 30 published posts
$posts = $postModel->getPublished(30, 0, $categoryId);

$site_name = SITE_NAME ?? 'Alokpat';
$site_desc = DEFAULT_META_DESCRIPTION ?? 'Alokpat - Bengali News Portal';
$site_url = rtrim(SITE_URL, '/');

// Start XML output
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" 
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
     xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title><![CDATA[<?= $site_name ?>]]></title>
    <atom:link href="<?= $site_url ?>/feed.php" rel="self" type="application/rss+xml" />
    <link><?= $site_url ?></link>
    <description><![CDATA[<?= $site_desc ?>]]></description>
    <lastBuildDate><?= date('r') ?></lastBuildDate>
    <language>bn-BD</language>
    <sy:updatePeriod>hourly</sy:updatePeriod>
    <sy:updateFrequency>1</sy:updateFrequency>

    <?php if ($posts): foreach ($posts as $post): 
        $url = url_for_post($post);
        $title = $post['title'] ?? '';
        // Extract plain text excerpt from content or use existing excerpt
        $excerpt = $post['excerpt'] ?? '';
        if (empty($excerpt) && !empty($post['content'])) {
            $excerpt = mb_substr(strip_tags($post['content']), 0, 150) . '...';
        }
        $author = $post['author_name'] ?? 'Alokpat Desk';
        $pubDate = date('r', strtotime($post['published_at'] ?? $post['created_at']));
        
        $image_url = '';
        if (!empty($post['featured_image'])) {
            $image_url = rtrim(SITE_URL, '/') . '/' . ltrim($post['featured_image'], '/');
        }
    ?>
    <item>
        <title><![CDATA[<?= $title ?>]]></title>
        <link><?= $url ?></link>
        <pubDate><?= $pubDate ?></pubDate>
        <dc:creator><![CDATA[<?= $author ?>]]></dc:creator>
        <category><![CDATA[<?= $post['category_name'] ?? 'News' ?>]]></category>
        
        <guid isPermaLink="true"><?= $url ?></guid>
        <description><![CDATA[<?= $excerpt ?>]]></description>
        
        <?php if (!empty($post['content'])): ?>
        <content:encoded><![CDATA[<?= $post['content'] ?>]]></content:encoded>
        <?php endif; ?>

        <?php if ($image_url): ?>
        <media:content url="<?= htmlspecialchars($image_url) ?>" medium="image">
            <media:title type="html"><![CDATA[<?= $title ?>]]></media:title>
        </media:content>
        <?php endif; ?>
    </item>
    <?php endforeach; endif; ?>
</channel>
</rss>
