<?php
/**
 * Prompts Page
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';
requireAuth();

// Permission check
if (!in_array(getCurrentUser()['role'], ['super_admin', 'admin', 'editor', 'author'])) {
    setFlash('error', 'আপনার এই পৃষ্ঠা দেখার অনুমতি নেই');
    redirect(ADMIN_URL . '/dashboard.php');
}

$page_title = 'এআই প্রম্পট (AI Prompts)';

$news_prompt = <<<EOT
Act as a senior, professional, and highly objective journalist for "Alokpat", a premium Bengali News Portal. Your writing style must be strictly neutral, factual, verified, and free of bias or sensationalism. 

I will provide you with a topic or raw facts. First, internally gather, verify, and organize the information. Then, generate a complete, highly professional news package following these strict rules:

1. 100% ORIGINAL CONTENT: DO NOT copy, scrape, or directly translate from any other source. You must gather the information and write the article entirely in your own words, maintaining original journalistic integrity.
2. WORD COUNT: The main news article MUST be minimum 500 to 700 words. Make it detailed, informative, and engaging.
3. SUBHEADINGS: Use clear, H2-style headlines for sub-sections to break up the long article.
4. ENGLISH KEYWORDS: You MUST seamlessly integrate relevant English keywords throughout the Bengali text (in the headlines, excerpt, main article, and everywhere).
5. MULTIPLE HEADLINES: Provide 4 to 5 different, catchy, and professional news-style headlines for me to choose from.

Format your response exactly like this:

HEADLINE OPTIONS:
1. [Headline 1 with English keyword]
2. [Headline 2 with English keyword]
3. [Headline 3 with English keyword]
4. [Headline 4 with English keyword]
5. [Headline 5 with English keyword]

EXCERPT (Short Summary): 
[Write a 2-3 sentence summary of the news incorporating English keywords. Max 150 characters.]

FULL ARTICLE:
[Write the detailed 500-700 word news article here in Bengali, mixing English keywords naturally. Use H2-style subheadings for different sections. Include a strong Lead paragraph covering Who, What, When, Where, and Why. Maintain a highly objective tone.]

SEO METADATA:
- SEO Title: [A highly searchable title, mixing Bengali with English keywords, max 60 chars]
- SEO Description: [Write the description primarily in Bengali but cleverly mix in English keywords. Make it compelling for Google searchers, max 160 chars]
- SEO Keywords: [5-7 comma-separated English keywords]

IMAGE PROMPT (ALT TEXT):
[Provide a descriptive ALT text in English for the featured image related to this news.]

Here is the topic/raw facts for the news:
[INSERT YOUR RAW NEWS DETAILS / TOPIC HERE]
EOT;

ob_start();
?>
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">এআই প্রম্পট (AI Prompts)</h2>
            <p class="text-sm text-gray-500 mt-1">কন্টেন্ট লেখার জন্য গুরুত্বপূর্ণ AI প্রম্পটগুলো এখান থেকে কপি করুন</p>
        </div>
    </div>

    <!-- News Generation Prompt -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div>
                <h3 class="text-xl font-bold text-gray-800">সংবাদ লেখার প্রম্পট (News Writing Prompt)</h3>
                <p class="text-sm text-gray-500 mt-1">খবর লেখার জন্য এই প্রম্পটটি কপি করে ChatGPT বা Claude-এ পেস্ট করুন।</p>
            </div>
            <button onclick="copyPrompt('newsPrompt')" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-bold shadow-sm flex items-center gap-2">
                <i class="fas fa-copy"></i> কপি করুন
            </button>
        </div>
        <div class="p-6 bg-gray-900 text-gray-100 font-mono text-sm overflow-x-auto">
            <pre id="newsPrompt" class="whitespace-pre-wrap leading-relaxed"><?php echo escape($news_prompt); ?></pre>
        </div>
    </div>
</div>

<script>
function copyPrompt(elementId) {
    const text = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('প্রম্পট কপি করা হয়েছে! (Prompt Copied!)');
    }).catch(err => {
        console.error('Failed to copy text: ', err);
        alert('কপি করতে সমস্যা হয়েছে।');
    });
}
</script>
<?php
$content = ob_get_clean();
require_once 'layouts/admin.php';
?>
