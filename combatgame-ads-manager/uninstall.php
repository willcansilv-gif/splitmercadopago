<?php
declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$tables = ['cgam_campaigns', 'cgam_banners', 'cgam_impressions', 'cgam_clicks', 'cgam_logs', 'cgam_settings', 'cgam_rotations'];
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
}
delete_option('cgam_settings');
