<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Database;

final class Schema
{
    public function create(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $sql = [];
        $sql[] = "CREATE TABLE {$wpdb->prefix}cgam_campaigns (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(190) NOT NULL,advertiser VARCHAR(190) NOT NULL,target_url TEXT NOT NULL,cta VARCHAR(190),description TEXT,notes TEXT,status VARCHAR(30) NOT NULL DEFAULT 'active',priority INT NOT NULL DEFAULT 0,rotation_weight INT NOT NULL DEFAULT 1,start_date DATETIME NULL,end_date DATETIME NULL,impression_limit BIGINT NULL,click_limit BIGINT NULL,category VARCHAR(100),city VARCHAR(100),state VARCHAR(100),related_field VARCHAR(100),game_type VARCHAR(100),tags TEXT,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,KEY idx_status(status),KEY idx_dates(start_date,end_date)) $charset";
        $sql[] = "CREATE TABLE {$wpdb->prefix}cgam_banners (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,campaign_id BIGINT UNSIGNED NOT NULL,media_id BIGINT UNSIGNED NULL,size VARCHAR(30),device VARCHAR(20),image_url TEXT NOT NULL,webp_url TEXT NULL,alt_text VARCHAR(255),is_fallback TINYINT(1) DEFAULT 0,created_at DATETIME NOT NULL,KEY idx_campaign(campaign_id),KEY idx_device(device)) $charset";
        $sql[] = "CREATE TABLE {$wpdb->prefix}cgam_impressions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,campaign_id BIGINT UNSIGNED NOT NULL,banner_id BIGINT UNSIGNED NULL,seen_at DATETIME NOT NULL,device VARCHAR(20),page_url TEXT,origin VARCHAR(190),ip_hash VARCHAR(64),KEY idx_campaign(campaign_id),KEY idx_seen_at(seen_at)) $charset";
        $sql[] = "CREATE TABLE {$wpdb->prefix}cgam_clicks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,campaign_id BIGINT UNSIGNED NOT NULL,banner_id BIGINT UNSIGNED NULL,clicked_at DATETIME NOT NULL,device VARCHAR(20),ip_hash VARCHAR(64),referrer TEXT,KEY idx_campaign(campaign_id),KEY idx_clicked_at(clicked_at)) $charset";
        $sql[] = "CREATE TABLE {$wpdb->prefix}cgam_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,level VARCHAR(20),message TEXT,context LONGTEXT,created_at DATETIME NOT NULL,KEY idx_level(level),KEY idx_created_at(created_at)) $charset";
        $sql[] = "CREATE TABLE {$wpdb->prefix}cgam_settings (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,option_key VARCHAR(190) UNIQUE,option_value LONGTEXT,updated_at DATETIME NOT NULL) $charset";
        $sql[] = "CREATE TABLE {$wpdb->prefix}cgam_rotations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,slot VARCHAR(100),campaign_id BIGINT UNSIGNED NOT NULL,weight INT NOT NULL DEFAULT 1,last_served_at DATETIME NULL,KEY idx_slot(slot)) $charset";
        foreach ($sql as $statement) { dbDelta($statement); }
    }
}
