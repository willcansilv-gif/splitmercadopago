<?php

if (!defined('ABSPATH')) {
    exit;
}

class MPSW_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'mpsw_gateway';
        $this->method_title = __('Mercado Pago Split', 'mercadopago-split-wcfm');
        $this->method_description = __('Pagamentos com split automático para marketplaces WCFM.', 'mercadopago-split-wcfm');
        $this->has_fields = true;

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Ativar/Desativar', 'mercadopago-split-wcfm'),
                'type' => 'checkbox',
                'label' => __('Ativar Mercado Pago Split', 'mercadopago-split-wcfm'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Título', 'mercadopago-split-wcfm'),
                'type' => 'text',
                'default' => __('Mercado Pago Split', 'mercadopago-split-wcfm'),
            ),
        );
    }

    public function payment_fields() {
        echo '<fieldset>';
        echo '<p>' . esc_html__('Use o token do cartão e o método de pagamento conforme o checkout do Mercado Pago.', 'mercadopago-split-wcfm') . '</p>';
        echo '<p class="form-row form-row-wide">';
        echo '<label for="mpsw_card_token">' . esc_html__('Token do cartão', 'mercadopago-split-wcfm') . '</label>';
        echo '<input id="mpsw_card_token" name="mpsw_card_token" type="text" />';
        echo '</p>';
        echo '<p class="form-row form-row-wide">';
        echo '<label for="mpsw_payment_method">' . esc_html__('Método de pagamento', 'mercadopago-split-wcfm') . '</label>';
        echo '<input id="mpsw_payment_method" name="mpsw_payment_method" type="text" />';
        echo '</p>';
        echo '</fieldset>';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            wc_add_notice(__('Pedido inválido.', 'mercadopago-split-wcfm'), 'error');
            return array('result' => 'fail');
        }

        $token = isset($_POST['mpsw_card_token']) ? sanitize_text_field(wp_unslash($_POST['mpsw_card_token'])) : '';
        $payment_method = isset($_POST['mpsw_payment_method']) ? sanitize_text_field(wp_unslash($_POST['mpsw_payment_method'])) : '';
        if (!$token || !$payment_method) {
            wc_add_notice(__('Token ou método de pagamento ausente.', 'mercadopago-split-wcfm'), 'error');
            return array('result' => 'fail');
        }

        $suborders = $this->get_suborders($order_id);
        if (!empty($suborders)) {
            foreach ($suborders as $suborder_id) {
                $suborder = wc_get_order($suborder_id);
                $result = $this->create_payment_for_order($suborder, $token, $payment_method);
                if (is_wp_error($result)) {
                    wc_add_notice($result->get_error_message(), 'error');
                    return array('result' => 'fail');
                }
            }
        } else {
            $vendor_id = $this->get_vendor_id_from_order($order);
            if (!$vendor_id) {
                wc_add_notice(__('Não foi possível identificar o vendedor.', 'mercadopago-split-wcfm'), 'error');
                return array('result' => 'fail');
            }

            if ($this->order_has_multiple_vendors($order)) {
                wc_add_notice(__('Pedidos com múltiplos vendedores exigem subpedidos.', 'mercadopago-split-wcfm'), 'error');
                return array('result' => 'fail');
            }

            $result = $this->create_payment_for_order($order, $token, $payment_method);
            if (is_wp_error($result)) {
                wc_add_notice($result->get_error_message(), 'error');
                return array('result' => 'fail');
            }
        }

        $order->update_status('on-hold', __('Aguardando pagamento Mercado Pago.', 'mercadopago-split-wcfm'));
        wc_reduce_stock_levels($order_id);
        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }

    private function create_payment_for_order($order, $token, $payment_method) {
        $vendor_id = $this->get_vendor_id_from_order($order);
        if (!$vendor_id) {
            return new WP_Error('mpsw_vendor_missing', __('Vendedor não encontrado.', 'mercadopago-split-wcfm'));
        }

        $oauth = MPSW_OAuth::instance();
        $tokens = $oauth->get_vendor_tokens($vendor_id);
        if (empty($tokens['access_token'])) {
            return new WP_Error('mpsw_vendor_disconnected', __('Vendedor não conectado ao Mercado Pago.', 'mercadopago-split-wcfm'));
        }

        if (!empty($tokens['expires_at']) && time() >= (int) $tokens['expires_at'] && !empty($tokens['refresh_token'])) {
            $refreshed = $oauth->refresh_tokens($tokens['refresh_token']);
            if (is_wp_error($refreshed)) {
                return new WP_Error('mpsw_refresh_failed', $refreshed->get_error_message());
            }
            $tokens = $refreshed;
            update_user_meta($vendor_id, 'mpsw_vendor_tokens', $oauth->encrypt_tokens($tokens));
        }

        $total = (float) $order->get_total();
        $application_fee = MPSW_Split::instance()->calculate_application_fee($total);

        $payload = array(
            'transaction_amount' => $total,
            'token' => $token,
            'description' => sprintf('Pedido %s', $order->get_order_number()),
            'payer' => array(
                'email' => $order->get_billing_email(),
            ),
            'installments' => 1,
            'payment_method_id' => $payment_method,
            'application_fee' => $application_fee,
        );

        $response = wp_remote_post('https://api.mercadopago.com/v1/payments', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $tokens['access_token'],
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300 || empty($body['id'])) {
            $message = isset($body['message']) ? $body['message'] : __('Erro ao criar pagamento.', 'mercadopago-split-wcfm');
            return new WP_Error('mpsw_payment_failed', $message);
        }

        $order->update_meta_data('_mpsw_payment_id', $body['id']);
        $order->save();

        return $body;
    }

    private function get_suborders($order_id) {
        if (function_exists('wcfmmp_get_sub_orders')) {
            return wcfmmp_get_sub_orders($order_id);
        }
        return array();
    }

    private function get_vendor_id_from_order($order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if (function_exists('wcfm_get_vendor_id_by_post')) {
                return (int) wcfm_get_vendor_id_by_post($product_id);
            }
            $product = wc_get_product($product_id);
            if ($product) {
                return (int) $product->get_post_data()->post_author;
            }
        }
        return 0;
    }

    private function order_has_multiple_vendors($order) {
        $vendors = array();
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $vendor_id = function_exists('wcfm_get_vendor_id_by_post') ? (int) wcfm_get_vendor_id_by_post($product_id) : 0;
            if (!$vendor_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $vendor_id = (int) $product->get_post_data()->post_author;
                }
            }
            if ($vendor_id) {
                $vendors[$vendor_id] = true;
            }
        }
        return count($vendors) > 1;
    }
}
