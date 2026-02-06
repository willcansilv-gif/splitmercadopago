<?php
namespace SMP;

use WC_Payment_Gateway;

if (!defined('ABSPATH')) {
    exit;
}

class Payment_Gateway extends WC_Payment_Gateway
{
    public static function init(): void
    {
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'register_gateway'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function register_gateway(array $gateways): array
    {
        $gateways[] = __CLASS__;
        return $gateways;
    }

    public function __construct()
    {
        $this->id = 'smp_gateway';
        $this->method_title = __('Split Mercado Pago', 'split-mercado-pago');
        $this->method_description = __('Pagamento Mercado Pago com split via application_fee.', 'split-mercado-pago');
        $this->has_fields = true;
        $this->supports = array('products');

        $this->title = __('Mercado Pago (Split)', 'split-mercado-pago');
        $this->enabled = 'yes';
    }

    public static function enqueue_assets(): void
    {
        if (!is_checkout()) {
            return;
        }
        $public_key = get_option('smp_public_key', '');
        if ($public_key === '') {
            return;
        }
        wp_enqueue_script('mercadopago-sdk', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
        wp_enqueue_script('smp-checkout', SMP_PLUGIN_URL . 'assets/checkout.js', array('mercadopago-sdk', 'jquery'), SMP_VERSION, true);
        wp_localize_script('smp-checkout', 'smpCheckout', array(
            'publicKey' => $public_key,
        ));
    }

    public function payment_fields(): void
    {
        echo '<div id="smp-card-form">';
        echo '<p>' . esc_html__('Preencha os dados do cartão para pagar com Mercado Pago.', 'split-mercado-pago') . '</p>';
        echo '<p><label>' . esc_html__('Número do cartão', 'split-mercado-pago') . '</label><input type="text" id="smp_card_number" autocomplete="off"></p>';
        echo '<p><label>' . esc_html__('Nome no cartão', 'split-mercado-pago') . '</label><input type="text" id="smp_cardholder_name" autocomplete="off"></p>';
        echo '<p><label>' . esc_html__('Validade (MM/AA)', 'split-mercado-pago') . '</label><input type="text" id="smp_card_expiration" autocomplete="off"></p>';
        echo '<p><label>' . esc_html__('CVV', 'split-mercado-pago') . '</label><input type="text" id="smp_card_cvv" autocomplete="off"></p>';
        echo '<input type="hidden" name="smp_token" id="smp_token" value="">';
        echo '<input type="hidden" name="smp_payment_method_id" id="smp_payment_method_id" value="">';
        echo '</div>';
    }

    public function validate_fields(): bool
    {
        $token = isset($_POST['smp_token']) ? sanitize_text_field(wp_unslash($_POST['smp_token'])) : '';
        $payment_method = isset($_POST['smp_payment_method_id']) ? sanitize_text_field(wp_unslash($_POST['smp_payment_method_id'])) : '';

        if ($token === '' || $payment_method === '') {
            wc_add_notice(__('Não foi possível gerar o token do cartão.', 'split-mercado-pago'), 'error');
            return false;
        }
        return true;
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return array('result' => 'fail');
        }

        $vendor_id = $this->get_vendor_id_from_order($order);
        if (!$vendor_id) {
            wc_add_notice(__('Não foi possível identificar o vendedor.', 'split-mercado-pago'), 'error');
            return array('result' => 'fail');
        }

        $tokens = OAuth::get_vendor_token($vendor_id);
        if (!$tokens || empty($tokens['access_token'])) {
            wc_add_notice(__('Vendedor não conectado ao Mercado Pago.', 'split-mercado-pago'), 'error');
            return array('result' => 'fail');
        }

        $token = isset($_POST['smp_token']) ? sanitize_text_field(wp_unslash($_POST['smp_token'])) : '';
        $payment_method = isset($_POST['smp_payment_method_id']) ? sanitize_text_field(wp_unslash($_POST['smp_payment_method_id'])) : '';

        $payload = array(
            'transaction_amount' => (float) $order->get_total(),
            'token' => $token,
            'description' => sprintf(__('Pedido #%d', 'split-mercado-pago'), $order->get_id()),
            'payer' => array(
                'email' => $order->get_billing_email(),
            ),
            'installments' => 1,
            'payment_method_id' => $payment_method,
            'application_fee' => $this->calculate_application_fee($order->get_total()),
            'external_reference' => (string) $order->get_id(),
        );

        $logger = Logger::instance();
        $logger->info('Criando pagamento Mercado Pago', array('order_id' => $order->get_id(), 'payload' => $payload));

        $response = wp_remote_post('https://api.mercadopago.com/v1/payments', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $tokens['access_token'],
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        ));

        if (is_wp_error($response)) {
            $logger->error('Erro ao criar pagamento', array('error' => $response->get_error_message()));
            wc_add_notice(__('Erro ao criar pagamento.', 'split-mercado-pago'), 'error');
            return array('result' => 'fail');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body) || empty($body['id'])) {
            $logger->error('Resposta inválida do Mercado Pago', array('response' => $body));
            wc_add_notice(__('Resposta inválida do Mercado Pago.', 'split-mercado-pago'), 'error');
            return array('result' => 'fail');
        }

        update_post_meta($order_id, '_smp_mp_payment_id', sanitize_text_field((string) $body['id']));
        $status = $body['status'] ?? '';

        if ($status === 'approved') {
            $order->payment_complete($body['id']);
        } elseif ($status === 'in_process' || $status === 'pending') {
            $order->update_status('on-hold', __('Pagamento em processamento no Mercado Pago.', 'split-mercado-pago'));
        } else {
            $order->update_status('failed', __('Pagamento rejeitado pelo Mercado Pago.', 'split-mercado-pago'));
        }

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }

    private function get_vendor_id_from_order($order): ?int
    {
        $vendor_ids = array();
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $author_id = (int) get_post_field('post_author', $product_id);
            if ($author_id) {
                $vendor_ids[$author_id] = true;
            }
        }

        if (count($vendor_ids) !== 1) {
            return null;
        }

        return (int) array_key_first($vendor_ids);
    }

    private function calculate_application_fee(float $total): float
    {
        $percent = (float) get_option('smp_commission_percent', 0);
        $fixed = (float) get_option('smp_commission_fixed', 0);
        $fee = ($total * $percent / 100) + $fixed;
        if ($fee < 0) {
            $fee = 0;
        }
        if ($fee > $total) {
            $fee = $total;
        }
        return round($fee, 2);
    }
}
