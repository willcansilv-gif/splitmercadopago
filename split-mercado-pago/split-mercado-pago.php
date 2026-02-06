<?php
/**
 * Plugin Name: Split Mercado Pago WooCommerce
 * Description: Integra Mercado Pago Split Payments ao WooCommerce com split real via application_fee.
 * Version: 1.0.0
 * Author: Split Mercado Pago
 * Text Domain: split-mercado-pago
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SMP_VERSION', '1.0.0');
define('SMP_PLUGIN_FILE', __FILE__);
define('SMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMP_PLUGIN_URL', plugin_dir_url(__FILE__));

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'SMP\\';
        $base_dir = SMP_PLUGIN_DIR . 'includes/';
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
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            update_option('smp_missing_wc', '1', false);
        }
    }
);

add_action(
    'admin_notices',
    static function (): void {
        if (get_option('smp_missing_wc') !== '1') {
            return;
        }
        delete_option('smp_missing_wc');
        echo '<div class="notice notice-error"><p>' . esc_html__('Split Mercado Pago requer WooCommerce ativo.', 'split-mercado-pago') . '</p></div>';
    }
);

add_action(
    'plugins_loaded',
    static function (): void {
        if (!class_exists('WooCommerce')) {
            return;
        }

        SMP\Logger::init();
        SMP\Admin::init();
        SMP\OAuth::init();
        SMP\Payment_Gateway::init();
    }
);
