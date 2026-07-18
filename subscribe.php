<?php
require_once 'config/config.php';
require_once 'database/Database.php';
require_once 'helpers/functions.php';
require_once 'models/Subscriber.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    
    // Validate email
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $subscriber = new Subscriber();
        $result = $subscriber->subscribe($email);
        
        if ($result === true) {
            setFlash('success', 'সাবস্ক্রাইব করার জন্য ধন্যবাদ!');
        } elseif (is_array($result) && isset($result['error']) && $result['error'] === 'already_subscribed') {
            setFlash('info', 'আপনি ইতিমধ্যে সাবস্ক্রাইব করেছেন!');
        } else {
            setFlash('error', 'কোনো সমস্যা হয়েছে। দয়া করে আবার চেষ্টা করুন।');
        }
    } else {
        setFlash('error', 'দয়া করে সঠিক ইমেইল এড্রেস প্রদান করুন।');
    }
    
    // Redirect back
    $referer = $_SERVER['HTTP_REFERER'] ?? SITE_URL;
    redirect($referer);
} else {
    redirect(SITE_URL);
}
