<?php

if (!defined('ABSPATH')) {
    exit;
}

class MPSW_Admin {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('plugin_action_links_' . plugin_basename(MPSW_PLUGIN_DIR . 'mercadopago-split.php'), array($this, 'action_links'));
    }

    public function register_menu() {
        add_menu_page(
            __('Mercado Pago Split', 'mercadopago-split-wcfm'),
            __('MP Split', 'mercadopago-split-wcfm'),
            'manage_woocommerce',
            'mpsw-wizard',
            array($this, 'render_wizard'),
            'dashicons-money-alt'
        );

        add_submenu_page(
            'mpsw-wizard',
            __('Wizard de Configuração', 'mercadopago-split-wcfm'),
            __('Configurar Plugin', 'mercadopago-split-wcfm'),
            'manage_woocommerce',
            'mpsw-wizard',
            array($this, 'render_wizard')
        );

        add_submenu_page(
            'mpsw-wizard',
            __('Logs', 'mercadopago-split-wcfm'),
            __('Logs', 'mercadopago-split-wcfm'),
            'manage_woocommerce',
            'mpsw-logs',
            array($this, 'render_logs')
        );
    }

    public function action_links($links) {
        $links[] = '<a href="' . esc_url(admin_url('admin.php?page=mpsw-wizard')) . '">' . esc_html__('Configurar Plugin', 'mercadopago-split-wcfm') . '</a>';
        return $links;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'mpsw-wizard') === false) {
            return;
        }

        wp_enqueue_style('mpsw-admin', MPSW_PLUGIN_URL . 'admin/assets/admin.css', array(), MPSW_VERSION);
        wp_enqueue_script('mpsw-admin', MPSW_PLUGIN_URL . 'admin/assets/admin.js', array('jquery'), MPSW_VERSION, true);
        wp_localize_script('mpsw-admin', 'mpswAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mpsw_admin_nonce'),
        ));
    }

    public function render_wizard() {
        include MPSW_PLUGIN_DIR . 'admin/views/wizard.php';
    }

    public function render_logs() {
        include MPSW_PLUGIN_DIR . 'admin/views/logs.php';
    }
}
