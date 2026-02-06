<?php
namespace SMP;

if (!defined('ABSPATH')) {
    exit;
}

class Logger
{
    private static ?self $instance = null;
    private string $log_dir;

    private function __construct()
    {
        $upload_dir = wp_upload_dir();
        $this->log_dir = trailingslashit($upload_dir['basedir']) . 'split-mercado-pago-logs/';
        $this->ensure_log_dir();
    }

    public static function init(): void
    {
        self::instance();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log(string $level, string $message, array $context = array()): void
    {
        $enabled = get_option('smp_logs_enabled', 'no');
        if ($enabled !== 'yes') {
            return;
        }

        $line = sprintf(
            "[%s] [%s] %s %s\n",
            gmdate('c'),
            strtoupper($level),
            $message,
            $this->sanitize_context($context)
        );

        $file = $this->log_dir . 'log-' . gmdate('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = array()): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = array()): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = array()): void
    {
        $this->log('error', $message, $context);
    }

    public function get_log_file(): string
    {
        return $this->log_dir . 'log-' . gmdate('Y-m-d') . '.log';
    }

    private function sanitize_context(array $context): string
    {
        $redact_keys = array('access_token', 'refresh_token', 'token', 'authorization', 'client_secret');
        $scrubbed = $this->redact_recursive($context, $redact_keys);
        $encoded = wp_json_encode($scrubbed);
        return $encoded ? $encoded : '';
    }

    private function redact_recursive($value, array $keys)
    {
        if (is_array($value)) {
            $clean = array();
            foreach ($value as $key => $item) {
                if (is_string($key) && in_array(strtolower($key), $keys, true)) {
                    $clean[$key] = '[redacted]';
                } else {
                    $clean[$key] = $this->redact_recursive($item, $keys);
                }
            }
            return $clean;
        }
        if (is_string($value)) {
            return $value;
        }
        return $value;
    }

    private function ensure_log_dir(): void
    {
        if (!is_dir($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }

        $index_file = $this->log_dir . 'index.html';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '');
        }

        $htaccess = $this->log_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }
}
