<?php
namespace SMP;

if (!defined('ABSPATH')) {
    exit;
}

class OAuth
{
    public static function init(): void
    {
        add_action('show_user_profile', array(__CLASS__, 'render_vendor_section'));
        add_action('edit_user_profile', array(__CLASS__, 'render_vendor_section'));
        add_action('admin_post_smp_vendor_disconnect', array(__CLASS__, 'disconnect_vendor'));
        add_action('init', array(__CLASS__, 'handle_callback'));
    }

    public static function render_vendor_section(
        $user
    ): void {
        if (!self::user_is_vendor($user)) {
            return;
        }
        $connected = get_user_meta($user->ID, 'smp_mp_connected', true) === 'yes';
        $auth_url = self::get_authorization_url($user->ID);
        ?>
        <h2><?php echo esc_html__('Mercado Pago', 'split-mercado-pago'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><?php echo esc_html__('Status', 'split-mercado-pago'); ?></th>
                <td>
                    <?php if ($connected) : ?>
                        <span><?php echo esc_html__('Conectado', 'split-mercado-pago'); ?></span>
                        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smp_vendor_disconnect&user_id=' . $user->ID), 'smp_vendor_disconnect_' . $user->ID)); ?>">
                            <?php echo esc_html__('Desconectar', 'split-mercado-pago'); ?>
                        </a>
                    <?php else : ?>
                        <a class="button button-primary" href="<?php echo esc_url($auth_url); ?>">
                            <?php echo esc_html__('Conectar Mercado Pago', 'split-mercado-pago'); ?>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function disconnect_vendor(): void
    {
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        if (!current_user_can('edit_user', $user_id)) {
            wp_die(esc_html__('Acesso negado.', 'split-mercado-pago'));
        }
        check_admin_referer('smp_vendor_disconnect_' . $user_id);
        delete_user_meta($user_id, 'smp_mp_tokens');
        delete_user_meta($user_id, 'smp_mp_user_id');
        update_user_meta($user_id, 'smp_mp_connected', 'no');
        wp_safe_redirect(get_edit_user_link($user_id));
        exit;
    }

    public static function get_redirect_uri(): string
    {
        return site_url('?mp_oauth=callback');
    }

    public static function get_authorization_url(int $user_id): string
    {
        $client_id = get_option('smp_client_id', '');
        $state = self::build_state($user_id);

        $params = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => self::get_redirect_uri(),
            'state' => $state,
        );

        return 'https://auth.mercadopago.com.br/authorization?' . http_build_query($params, '', '&');
    }

    private static function build_state(int $user_id): string
    {
        $nonce = wp_create_nonce('smp_oauth_' . $user_id);
        return $user_id . ':' . $nonce;
    }

    private static function parse_state(string $state): array
    {
        $parts = explode(':', $state);
        if (count($parts) !== 2) {
            return array(0, '');
        }
        return array((int) $parts[0], $parts[1]);
    }

    public static function handle_callback(): void
    {
        if (!isset($_GET['mp_oauth'])) {
            return;
        }

        if (sanitize_text_field(wp_unslash($_GET['mp_oauth'])) !== 'callback') {
            return;
        }

        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
        $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
        $error = isset($_GET['error']) ? sanitize_text_field(wp_unslash($_GET['error'])) : '';

        if ($error) {
            wp_die(esc_html__('Erro OAuth: ', 'split-mercado-pago') . esc_html($error));
        }

        if ($code === '' || $state === '') {
            wp_die(esc_html__('Callback OAuth inválido.', 'split-mercado-pago'));
        }

        list($user_id, $nonce) = self::parse_state($state);
        if ($user_id <= 0 || !wp_verify_nonce($nonce, 'smp_oauth_' . $user_id)) {
            wp_die(esc_html__('Estado OAuth inválido.', 'split-mercado-pago'));
        }

        if (!self::user_is_vendor(get_user_by('id', $user_id))) {
            wp_die(esc_html__('Usuário inválido para OAuth.', 'split-mercado-pago'));
        }

        $tokens = self::exchange_code($code);
        if (is_wp_error($tokens)) {
            wp_die(esc_html($tokens->get_error_message()));
        }

        update_user_meta($user_id, 'smp_mp_tokens', self::encrypt(wp_json_encode($tokens)));
        update_user_meta($user_id, 'smp_mp_user_id', sanitize_text_field((string) $tokens['user_id']));
        update_user_meta($user_id, 'smp_mp_connected', 'yes');

        wp_safe_redirect(get_edit_user_link($user_id));
        exit;
    }

    public static function get_vendor_token(int $user_id): ?array
    {
        $stored = get_user_meta($user_id, 'smp_mp_tokens', true);
        if (!$stored) {
            return null;
        }
        $decoded = json_decode(self::decrypt($stored), true);
        if (!is_array($decoded)) {
            return null;
        }
        if (!empty($decoded['expires_in']) && !empty($decoded['created_at'])) {
            $expires_at = (int) $decoded['created_at'] + (int) $decoded['expires_in'] - 60;
            if (time() >= $expires_at) {
                $refreshed = self::refresh_token($decoded['refresh_token']);
                if (!is_wp_error($refreshed)) {
                    $decoded = $refreshed;
                    update_user_meta($user_id, 'smp_mp_tokens', self::encrypt(wp_json_encode($decoded)));
                }
            }
        }
        return $decoded;
    }

    private static function exchange_code(string $code)
    {
        $client_id = get_option('smp_client_id', '');
        $client_secret = get_option('smp_client_secret', '');

        if ($client_id === '' || $client_secret === '') {
            return new \WP_Error('smp_oauth', __('Client ID/Secret não configurados.', 'split-mercado-pago'));
        }

        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => array(
                'grant_type' => 'authorization_code',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => self::get_redirect_uri(),
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $payload = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($payload) || empty($payload['access_token'])) {
            return new \WP_Error('smp_oauth', __('Falha ao obter token OAuth.', 'split-mercado-pago'));
        }

        $payload['created_at'] = time();
        return $payload;
    }

    private static function refresh_token(string $refresh_token)
    {
        $client_id = get_option('smp_client_id', '');
        $client_secret = get_option('smp_client_secret', '');

        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => array(
                'grant_type' => 'refresh_token',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $payload = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($payload) || empty($payload['access_token'])) {
            return new \WP_Error('smp_oauth', __('Falha ao atualizar token OAuth.', 'split-mercado-pago'));
        }

        $payload['created_at'] = time();
        return $payload;
    }

    private static function encrypt(string $value): string
    {
        $key = hash('sha256', wp_salt('auth'));
        $iv = substr(hash('sha256', wp_salt('secure_auth')), 0, 16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        return $encrypted ? $encrypted : '';
    }

    private static function decrypt(string $value): string
    {
        $key = hash('sha256', wp_salt('auth'));
        $iv = substr(hash('sha256', wp_salt('secure_auth')), 0, 16);
        $decrypted = openssl_decrypt($value, 'AES-256-CBC', $key, 0, $iv);
        return $decrypted ? $decrypted : '';
    }

    private static function user_is_vendor($user): bool
    {
        if (!$user instanceof \WP_User) {
            return false;
        }
        return in_array('vendor', (array) $user->roles, true) || in_array('seller', (array) $user->roles, true);
    }
}
