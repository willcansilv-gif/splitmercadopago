<?php

declare(strict_types=1);

namespace MPSW\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class Logger {
    private static ?self $instance = null;
    private string $log_dir;

    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_dir = trailingslashit($upload_dir['basedir']) . 'mercadopago-split-logs/';

        add_action('admin_post_mpsw_download_logs', array($this, 'handle_download'));
    }

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function info(string $message, array $context = array()): void {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = array()): void {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = array()): void {
        $this->log('ERROR', $message, $context);
    }

    public function log(string $level, string $message, array $context = array()): void {
        if (!get_option('mpsw_logs_enabled')) {
            return;
        }

        $safe_message = $this->interpolate($message, $context);
        $line = sprintf('[%s] [%s] %s' . PHP_EOL, gmdate('Y-m-d H:i:s'), strtoupper($level), $safe_message);

        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }

        $file = $this->log_dir . 'mpsw-' . gmdate('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private function interpolate(string $message, array $context): string {
        $redacted = array('access_token', 'refresh_token', 'public_key', 'token', 'authorization');
        $replacements = array();

        foreach ($context as $key => $value) {
            $safe_value = in_array($key, $redacted, true) ? '[REDACTED]' : $value;
            $replacements['{' . $key . '}'] = is_scalar($safe_value) ? (string) $safe_value : wp_json_encode($safe_value);
        }

        return strtr($message, $replacements);
    }

    public function handle_download(): void {
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
