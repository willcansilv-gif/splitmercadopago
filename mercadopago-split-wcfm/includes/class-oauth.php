<?php

if (!defined('ABSPATH')) {
    exit;
}

class MPSW_OAuth {
    private static $instance = null;

    private $auth_base = 'https://auth.mercadopago.com/authorization';
    private $token_base = 'https://api.mercadopago.com/oauth/token';

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_post_mpsw_oauth_callback', array($this, 'handle_callback'));
        add_action('admin_post_mpsw_vendor_oauth_callback', array($this, 'handle_vendor_callback'));
    }

    public function get_authorize_url($state) {
        $client_id = get_option('mpsw_client_id');
        $redirect = $this->get_redirect_url();

        $params = array(
            'client_id' => $client_id,
            'response_type' => 'code',
            'state' => $state,
            'redirect_uri' => $redirect,
        );

        return add_query_arg($params, $this->auth_base);
    }

    public function get_redirect_url() {
        return admin_url('admin-post.php?action=mpsw_oauth_callback');
    }

    public function handle_callback() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Sem permissão.', 'mercadopago-split-wcfm'));
        }

        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        if (!$code) {
            wp_die(__('Código OAuth ausente.', 'mercadopago-split-wcfm'));
        }

        if (!$state || !wp_verify_nonce($state, 'mpsw_oauth_state')) {
            wp_die(__('Estado OAuth inválido.', 'mercadopago-split-wcfm'));
        }

        $tokens = $this->exchange_code_for_tokens($code, $this->get_redirect_url());
        if (is_wp_error($tokens)) {
            wp_die(esc_html($tokens->get_error_message()));
        }

        update_option('mpsw_oauth_tokens', $this->encrypt_tokens($tokens), false);
        update_option('mpsw_oauth_connected', 1, false);

        wp_safe_redirect(admin_url('admin.php?page=mpsw-wizard&step=oauth&connected=1'));
        exit;
    }

    public function get_vendor_authorize_url($vendor_id, $state) {
        $client_id = get_option('mpsw_client_id');
        $redirect = admin_url('admin-post.php?action=mpsw_vendor_oauth_callback');

        $params = array(
            'client_id' => $client_id,
            'response_type' => 'code',
            'state' => $state,
            'redirect_uri' => $redirect,
        );

        return add_query_arg($params, $this->auth_base);
    }

    public function handle_vendor_callback() {
        if (!is_user_logged_in()) {
            wp_die(__('Sem permissão.', 'mercadopago-split-wcfm'));
        }

        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        if (!$code || !$state) {
            wp_die(__('Dados OAuth ausentes.', 'mercadopago-split-wcfm'));
        }

        $state_parts = explode(':', $state);
        if (count($state_parts) !== 2) {
            wp_die(__('Estado OAuth inválido.', 'mercadopago-split-wcfm'));
        }

        $nonce = $state_parts[0];
        $vendor_id = absint($state_parts[1]);
        if (!$vendor_id || !wp_verify_nonce($nonce, 'mpsw_vendor_oauth_' . $vendor_id)) {
            wp_die(__('Estado OAuth inválido.', 'mercadopago-split-wcfm'));
        }

        if (get_current_user_id() !== $vendor_id && !current_user_can('manage_woocommerce')) {
            wp_die(__('Sem permissão.', 'mercadopago-split-wcfm'));
        }

        $redirect = admin_url('admin-post.php?action=mpsw_vendor_oauth_callback');
        $tokens = $this->exchange_code_for_tokens($code, $redirect);
        if (is_wp_error($tokens)) {
            wp_die(esc_html($tokens->get_error_message()));
        }

        update_user_meta($vendor_id, 'mpsw_vendor_connected', 1);
        update_user_meta($vendor_id, 'mpsw_vendor_tokens', $this->encrypt_tokens($tokens));

        wp_safe_redirect(admin_url('admin.php?page=mpsw-wizard&step=wcfm&vendor_connected=1'));
        exit;
    }

    public function get_admin_tokens() {
        $encrypted = get_option('mpsw_oauth_tokens');
        return $this->decrypt_tokens($encrypted);
    }

    public function get_vendor_tokens($vendor_id) {
        $encrypted = get_user_meta($vendor_id, 'mpsw_vendor_tokens', true);
        return $this->decrypt_tokens($encrypted);
    }

    public function refresh_tokens($refresh_token) {
        $client_id = get_option('mpsw_client_id');
        $client_secret = get_option('mpsw_client_secret');

        $response = wp_remote_post($this->token_base, array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => array(
                'grant_type' => 'refresh_token',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
            ),
        ));

        return $this->handle_token_response($response);
    }

    private function exchange_code_for_tokens($code, $redirect_uri) {
        $client_id = get_option('mpsw_client_id');
        $client_secret = get_option('mpsw_client_secret');

        if (!$client_id || !$client_secret) {
            return new WP_Error('mpsw_missing_client', __('Client ID/Secret ausente.', 'mercadopago-split-wcfm'));
        }

        $response = wp_remote_post($this->token_base, array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => array(
                'grant_type' => 'authorization_code',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri,
            ),
        ));

        return $this->handle_token_response($response);
    }

    private function handle_token_response($response) {
        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300 || empty($body['access_token'])) {
            $message = isset($body['message']) ? $body['message'] : __('Erro ao obter tokens.', 'mercadopago-split-wcfm');
            return new WP_Error('mpsw_oauth_error', $message);
        }

        $expires_in = isset($body['expires_in']) ? (int) $body['expires_in'] : 0;
        $body['expires_at'] = $expires_in ? (time() + $expires_in) : 0;

        return $body;
    }

    public function encrypt_tokens($tokens) {
        if (function_exists('wp_encrypt')) {
            return wp_encrypt(wp_json_encode($tokens));
        }

        $key = hash('sha256', wp_salt('auth'));
        $iv = substr(hash('sha256', wp_salt('secure_auth')), 0, 16);
        return base64_encode(openssl_encrypt(wp_json_encode($tokens), 'AES-256-CBC', $key, 0, $iv));
    }

    public function decrypt_tokens($payload) {
        if (!$payload) {
            return array();
        }

        if (function_exists('wp_decrypt')) {
            $decoded = wp_decrypt($payload);
        } else {
            $key = hash('sha256', wp_salt('auth'));
            $iv = substr(hash('sha256', wp_salt('secure_auth')), 0, 16);
            $decoded = openssl_decrypt(base64_decode($payload), 'AES-256-CBC', $key, 0, $iv);
        }

        $data = json_decode($decoded, true);
        return is_array($data) ? $data : array();
    }
}
