<?php

if (!defined('ABSPATH')) {
    exit;
}

class MPSW_Webhook {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('mpsw/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    public function get_webhook_url() {
        return rest_url('mpsw/v1/webhook');
    }

    public function handle_webhook($request) {
        $payload = $request->get_json_params();
        $signature = $request->get_header('x-signature');
        $request_id = $request->get_header('x-request-id');

        if (!$this->is_valid_signature($signature, $request_id, $payload)) {
            return new WP_REST_Response(array('error' => 'invalid_signature'), 401);
        }

        $data_id = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : '';
        $topic = isset($payload['type']) ? sanitize_text_field($payload['type']) : '';
        $action = isset($payload['action']) ? sanitize_text_field($payload['action']) : '';

        if ($data_id && $topic === 'payment') {
            $result = $this->update_order_status($data_id, $action);
            if (is_wp_error($result)) {
                return new WP_REST_Response(array('error' => $result->get_error_message()), 400);
            }
        }

        return new WP_REST_Response(array('received' => true), 200);
    }

    private function is_valid_signature($signature, $request_id, $payload) {
        $secret = get_option('mpsw_webhook_secret');
        if (!$signature || !$secret || !$request_id) {
            return false;
        }

        $parts = array();
        foreach (explode(',', $signature) as $part) {
            $segment = explode('=', trim($part), 2);
            if (count($segment) === 2) {
                $parts[$segment[0]] = $segment[1];
            }
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        $data_id = isset($payload['data']['id']) ? sanitize_text_field($payload['data']['id']) : '';
        $manifest = $parts['ts'] . '.' . $request_id . '.' . $data_id;
        $computed = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($computed, $parts['v1']);
    }

    private function update_order_status($payment_id, $action) {
        $order = $this->get_order_by_payment_id($payment_id);
        if (!$order) {
            return new WP_Error('mpsw_order_not_found', __('Pedido não encontrado.', 'mercadopago-split-wcfm'));
        }

        if ($this->is_duplicate_event($order, $payment_id, $action)) {
            return new WP_Error('mpsw_duplicate', __('Webhook duplicado.', 'mercadopago-split-wcfm'));
        }

        $payment = $this->fetch_payment($payment_id);
        if (is_wp_error($payment)) {
            return $payment;
        }

        $status = isset($payment['status']) ? sanitize_text_field($payment['status']) : '';
        switch ($status) {
            case 'approved':
                $order->update_status('processing', __('Pagamento aprovado Mercado Pago.', 'mercadopago-split-wcfm'));
                break;
            case 'rejected':
                $order->update_status('failed', __('Pagamento recusado Mercado Pago.', 'mercadopago-split-wcfm'));
                break;
            case 'refunded':
                $order->update_status('refunded', __('Pagamento estornado Mercado Pago.', 'mercadopago-split-wcfm'));
                break;
            case 'charged_back':
                $order->update_status('on-hold', __('Chargeback recebido Mercado Pago.', 'mercadopago-split-wcfm'));
                break;
        }

        $this->mark_event_processed($order, $payment_id, $action);
        update_option('mpsw_webhook_status', 'active', false);

        $logger = MPSW_Logger::instance();
        $logger->log('info', 'Webhook processado para pagamento {id}.', array('id' => $payment_id));

        return true;
    }

    private function get_order_by_payment_id($payment_id) {
        $orders = wc_get_orders(array(
            'limit' => 1,
            'meta_key' => '_mpsw_payment_id',
            'meta_value' => $payment_id,
        ));
        return !empty($orders) ? $orders[0] : null;
    }

    private function fetch_payment($payment_id) {
        $oauth = MPSW_OAuth::instance();
        $tokens = $oauth->get_admin_tokens();
        if (empty($tokens['access_token'])) {
            return new WP_Error('mpsw_missing_admin_token', __('Token do marketplace ausente.', 'mercadopago-split-wcfm'));
        }

        if (!empty($tokens['expires_at']) && time() >= (int) $tokens['expires_at'] && !empty($tokens['refresh_token'])) {
            $refreshed = $oauth->refresh_tokens($tokens['refresh_token']);
            if (is_wp_error($refreshed)) {
                return $refreshed;
            }
            $tokens = $refreshed;
            update_option('mpsw_oauth_tokens', $oauth->encrypt_tokens($tokens), false);
        }

        $response = wp_remote_get('https://api.mercadopago.com/v1/payments/' . $payment_id, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $tokens['access_token'],
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300 || empty($body['id'])) {
            $message = isset($body['message']) ? $body['message'] : __('Erro ao consultar pagamento.', 'mercadopago-split-wcfm');
            return new WP_Error('mpsw_payment_lookup', $message);
        }

        return $body;
    }

    private function is_duplicate_event($order, $payment_id, $action) {
        $key = sprintf('mpsw_webhook_%s_%s', $payment_id, $action ?: 'payment');
        return (bool) $order->get_meta($key, true);
    }

    private function mark_event_processed($order, $payment_id, $action) {
        $key = sprintf('mpsw_webhook_%s_%s', $payment_id, $action ?: 'payment');
        $order->update_meta_data($key, 1);
        $order->save();
    }
}
