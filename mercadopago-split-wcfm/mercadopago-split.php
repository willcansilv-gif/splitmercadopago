<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('MPSW_PLUGIN_VERSION')) {
    define('MPSW_PLUGIN_VERSION', MPSW_VERSION);
}

require_once MPSW_PLUGIN_DIR . 'includes/class-admin.php';
require_once MPSW_PLUGIN_DIR . 'includes/class-logger.php';
require_once MPSW_PLUGIN_DIR . 'includes/class-oauth.php';
require_once MPSW_PLUGIN_DIR . 'includes/class-split.php';
require_once MPSW_PLUGIN_DIR . 'includes/class-webhook.php';
require_once MPSW_PLUGIN_DIR . 'includes/class-gateway.php';
require_once MPSW_PLUGIN_DIR . 'includes/Wizard/class-wizard.php';

final class MPSW_Plugin {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'on_activation'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function on_activation() {
        if (!get_option('mpsw_wizard_completed')) {
            update_option('mpsw_wizard_redirect', 1, false);
        }
    }

    public function init() {
        load_plugin_textdomain('mercadopago-split-wcfm', false, dirname(plugin_basename(__FILE__)) . '/languages');

        MPSW_Admin::instance();
        MPSW_Logger::instance();
        MPSW_OAuth::instance();
        MPSW_Split::instance();
        MPSW_Webhook::instance();
        MPSW_Wizard::instance();

        add_filter('woocommerce_payment_gateways', array($this, 'register_gateway'));
    }

    public function register_gateway($gateways) {
        $gateways[] = 'MPSW_Gateway';
        return $gateways;
    }
}

MPSW_Plugin::instance();
