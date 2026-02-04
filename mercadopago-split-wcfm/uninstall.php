<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('mpsw_client_id');
delete_option('mpsw_client_secret');
delete_option('mpsw_oauth_tokens');
delete_option('mpsw_oauth_connected');
delete_option('mpsw_commission_percent');
delete_option('mpsw_commission_fixed');
delete_option('mpsw_webhook_secret');
delete_option('mpsw_webhook_status');
$logs_enabled = get_option('mpsw_logs_enabled');
delete_option('mpsw_logs_enabled');
delete_option('mpsw_debug_enabled');
delete_option('mpsw_wizard_completed');
delete_option('mpsw_wizard_redirect');

$users = get_users(array(
    'meta_key' => 'mpsw_vendor_connected',
    'fields' => 'ID',
));

foreach ($users as $user_id) {
    delete_user_meta($user_id, 'mpsw_vendor_connected');
    delete_user_meta($user_id, 'mpsw_vendor_tokens');
}

if ($logs_enabled) {
    $upload_dir = wp_upload_dir();
    $log_dir = trailingslashit($upload_dir['basedir']) . 'mercadopago-split-logs/';
    if (is_dir($log_dir)) {
        $files = glob($log_dir . '*');
        if (is_array($files)) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
