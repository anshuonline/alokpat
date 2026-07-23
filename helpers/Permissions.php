<?php
/**
 * Permissions Definitions
 * 
 * Defines all available granular permissions in the RBAC system.
 */

class Permissions {
    /**
     * Get all available permissions structured by groups
     * 
     * @return array
     */
    public static function getAll() {
        return [
            'Content' => [
                'create_posts' => 'পোস্ট তৈরি করা (Create Posts)',
                'edit_own_posts' => 'নিজের পোস্ট সম্পাদনা করা (Edit Own Posts)',
                'edit_others_posts' => 'অন্যদের পোস্ট সম্পাদনা করা (Edit Others Posts)',
                'delete_posts' => 'পোস্ট মুছুন (Delete Posts)',
                'publish_posts' => 'পোস্ট প্রকাশ করা (Publish Posts)',
            ],
            'Management' => [
                'manage_categories' => 'ক্যাটাগরি পরিচালনা (Manage Categories)',
                'manage_tags' => 'ট্যাগ পরিচালনা (Manage Tags)',
                'manage_media' => 'মিডিয়া পরিচালনা (Manage Media)',
                'manage_menus' => 'মেনু পরিচালনা (Manage Menus)',
            ],
            'Administration' => [
                'manage_users' => 'ব্যবহারকারী পরিচালনা (Manage Users)',
                'manage_roles' => 'ভূমিকা/রোল পরিচালনা (Manage Roles)',
                'manage_seo' => 'এসইও সেটিংস (Manage SEO)',
                'manage_subscribers' => 'সাবস্ক্রাইবার পরিচালনা (Manage Subscribers)',
                'view_reports' => 'রিপোর্ট দেখা (View Reports)',
                'manage_contacts' => 'যোগাযোগ বার্তা দেখা (Manage Contacts)',
            ],
            'Settings' => [
                'manage_settings' => 'সাইট সেটিংস (Site Settings)',
                'manage_homepage' => 'হোমপেজ সেটিংস (Homepage Settings)',
                'manage_ads' => 'বিজ্ঞাপন পরিচালনা (Manage Ads)',
                'manage_database' => 'ডাটাবেস পরিচালনা (Manage Database)',
                'manage_optimize' => 'সিস্টেম অপ্টিমাইজ (System Optimize)',
            ]
        ];
    }
}
