<?php

if (!defined('ABSPATH')) {
    exit;
}

class MPSW_Wizard {
    private static $instance = null;
    private $steps = array();

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->steps = array(
            'environment' => __('Ambiente', 'mercadopago-split-wcfm'),
            'oauth' => __('OAuth', 'mercadopago-split-wcfm'),
            'commission' => __('Comissão', 'mercadopago-split-wcfm'),
            'wcfm' => __('Vendedores', 'mercadopago-split-wcfm'),
            'webhook' => __('Webhooks', 'mercadopago-split-wcfm'),
            'logs' => __('Logs', 'mercadopago-split-wcfm'),
            'finish' => __('Finalizar', 'mercadopago-split-wcfm'),
        );

        add_action('admin_init', array($this, 'maybe_redirect_to_wizard'));
        add_action('admin_post_mpsw_save_step', array($this, 'handle_save_step'));
    }

    public function get_steps() {
        return $this->steps;
    }

    public function get_current_step() {
        $step = isset($_GET['step']) ? sanitize_key(wp_unslash($_GET['step'])) : 'environment';
        if (!array_key_exists($step, $this->steps)) {
            $step = 'environment';
        }
        return $step;
    }

    public function maybe_redirect_to_wizard() {
        if (!is_admin()) {
            return;
        }

        if (get_option('mpsw_wizard_redirect')) {
            delete_option('mpsw_wizard_redirect');
            wp_safe_redirect(admin_url('admin.php?page=mpsw-wizard'));
            exit;
        }
    }

    public function handle_save_step() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Sem permissão.', 'mercadopago-split-wcfm'));
        }

        check_admin_referer('mpsw_save_step');

        $step = isset($_POST['step']) ? sanitize_key(wp_unslash($_POST['step'])) : 'environment';
        if (!$this->is_step_allowed($step)) {
            wp_safe_redirect(admin_url('admin.php?page=mpsw-wizard&step=' . $this->get_current_step() . '&error=validation'));
            exit;
        }
        $next_step = $this->get_next_step($step);

        switch ($step) {
            case 'oauth':
                $client_id = isset($_POST['client_id']) ? sanitize_text_field(wp_unslash($_POST['client_id'])) : '';
                $client_secret = isset($_POST['client_secret']) ? sanitize_text_field(wp_unslash($_POST['client_secret'])) : '';
                if ($client_id) {
                    update_option('mpsw_client_id', $client_id, false);
                }
                if ($client_secret) {
                    update_option('mpsw_client_secret', $client_secret, false);
                }
                break;
            case 'commission':
                $percent = isset($_POST['commission_percent']) ? floatval(wp_unslash($_POST['commission_percent'])) : 0;
                $fixed = isset($_POST['commission_fixed']) ? floatval(wp_unslash($_POST['commission_fixed'])) : 0;
                update_option('mpsw_commission_percent', $percent, false);
                update_option('mpsw_commission_fixed', $fixed, false);
                break;
            case 'webhook':
                $secret = isset($_POST['webhook_secret']) ? sanitize_text_field(wp_unslash($_POST['webhook_secret'])) : '';
                if ($secret) {
                    update_option('mpsw_webhook_secret', $secret, false);
                }
                update_option('mpsw_webhook_status', 'pending', false);
                break;
            case 'logs':
                $logs_enabled = isset($_POST['logs_enabled']) ? 1 : 0;
                $debug_enabled = isset($_POST['debug_enabled']) ? 1 : 0;
                update_option('mpsw_logs_enabled', $logs_enabled, false);
                update_option('mpsw_debug_enabled', $debug_enabled, false);
                break;
            case 'finish':
                update_option('mpsw_wizard_completed', 1, false);
                $gateway_settings = get_option('woocommerce_mpsw_gateway_settings', array());
                $gateway_settings['enabled'] = 'yes';
                update_option('woocommerce_mpsw_gateway_settings', $gateway_settings, false);
                break;
        }

        wp_safe_redirect(admin_url('admin.php?page=mpsw-wizard&step=' . $next_step));
        exit;
    }

    private function get_next_step($current) {
        $keys = array_keys($this->steps);
        $index = array_search($current, $keys, true);
        if ($index === false || !isset($keys[$index + 1])) {
            return 'finish';
        }
        return $keys[$index + 1];
    }

    private function is_step_allowed($step) {
        if ($step === 'environment') {
            return true;
        }

        if ($step === 'oauth') {
            return get_option('mpsw_client_id') && get_option('mpsw_client_secret');
        }

        if ($step === 'commission') {
            return (bool) get_option('mpsw_oauth_connected');
        }

        if ($step === 'wcfm') {
            return get_option('mpsw_commission_percent') !== false;
        }

        if ($step === 'webhook') {
            return true;
        }

        if ($step === 'logs') {
            return true;
        }

        if ($step === 'finish') {
            return (bool) get_option('mpsw_oauth_connected');
        }

        return false;
    }

    public function render_progress() {
        $current = $this->get_current_step();
        $steps = $this->get_steps();
        $keys = array_keys($steps);
        $current_index = array_search($current, $keys, true);
        $progress = ($current_index + 1) / count($steps) * 100;

        echo '<div class="mpsw-progress">';
        echo '<div class="mpsw-progress__bar" style="width:' . esc_attr($progress) . '%"></div>';
        echo '</div>';
    }

    public function render_steps_nav() {
        $current = $this->get_current_step();
        echo '<ol class="mpsw-steps">';
        foreach ($this->steps as $key => $label) {
            $class = $key === $current ? 'is-active' : '';
            echo '<li class="' . esc_attr($class) . '">' . esc_html($label) . '</li>';
        }
        echo '</ol>';
    }

    public function render_step() {
        $step = $this->get_current_step();
        $file = MPSW_PLUGIN_DIR . 'includes/Wizard/steps/step-' . $step . '.php';
        if (file_exists($file)) {
            include $file;
        }
    }
}
