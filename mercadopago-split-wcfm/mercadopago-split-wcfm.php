<?php
/**
 * Plugin Name: Mercado Pago Split WCFM
 * Version: 1.0.0
 * Author: Split Mercado Pago
 * Text Domain: mercadopago-split-wcfm
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MPSW_VERSION', '1.0.0');
define('MPSW_PLUGIN_FILE', __FILE__);
define('MPSW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MPSW_PLUGIN_URL', plugin_dir_url(__FILE__));

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'MPSW\\';
        $base_dir = MPSW_PLUGIN_DIR . 'includes/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
);

register_activation_hook(
    __FILE__,
    static function (): void {
        $missing = array();

        if (!class_exists('WooCommerce')) {
            $missing[] = 'WooCommerce';
        }

        if (!class_exists('WCFM') && !defined('WCFMmp_VERSION')) {
            $missing[] = 'WCFM Marketplace';
        }

        if (!empty($missing)) {
            update_option('mpsw_missing_dependencies', $missing, false);
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }
);

add_action(
    'admin_notices',
    static function (): void {
        $missing = get_option('mpsw_missing_dependencies', array());
        if (empty($missing)) {
            return;
        }

        delete_option('mpsw_missing_dependencies');
        $message = sprintf(
            esc_html__('Mercado Pago Split WCFM requer os seguintes plugins ativos: %s.', 'mercadopago-split-wcfm'),
            esc_html(implode(', ', $missing))
        );
        echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
    }
);

add_action(
    'plugins_loaded',
    static function (): void {
        if (!class_exists('WooCommerce') || (!class_exists('WCFM') && !defined('WCFMmp_VERSION'))) {
            return;
        }

        require_once MPSW_PLUGIN_DIR . 'mercadopago-split.php';
    }
);
