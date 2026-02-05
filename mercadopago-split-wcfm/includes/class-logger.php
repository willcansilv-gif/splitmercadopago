<?php

if (!defined('ABSPATH')) {
    exit;
}

class MPSW_Logger {
    private static $instance = null;
    private $log_dir;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = trailingslashit($upload_dir['basedir']) . 'mercadopago-split-logs/';
        add_action('admin_post_mpsw_download_logs', array($this, 'handle_download'));
        $this->ensure_log_protection();
    }

    public function log($level, $message, $context = array()) {
        if (!get_option('mpsw_logs_enabled')) {
            return;
        }

        $safe_message = $this->sanitize_message($message, $context);
        $line = sprintf("[%s] [%s] %s\n", gmdate('Y-m-d H:i:s'), strtoupper($level), $safe_message);

        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }

        $file = $this->log_dir . 'mpsw-' . gmdate('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private function sanitize_message($message, $context) {
        $redacted = array('access_token', 'refresh_token', 'public_key', 'token', 'authorization');
        foreach ($context as $key => $value) {
            $context_value = in_array($key, $redacted, true) ? '[REDACTED]' : $value;
            $message = str_replace('{' . $key . '}', $context_value, $message);
        }
        return $message;
    }

    private function ensure_log_protection() {
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }

        $htaccess = $this->log_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }

        $index = $this->log_dir . 'index.html';
        if (!file_exists($index)) {
            file_put_contents($index, '');
        }
    }

    public function handle_download() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Sem permissão.', 'mercadopago-split-wcfm'));
        }

        check_admin_referer('mpsw_download_logs');

        $file = $this->log_dir . 'mpsw-' . gmdate('Y-m-d') . '.log';
        if (!file_exists($file)) {
            wp_die(__('Nenhum log disponível.', 'mercadopago-split-wcfm'));
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        exit;
    }
}
