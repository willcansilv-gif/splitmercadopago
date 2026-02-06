<?php
namespace SMP;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public static function init(): void
    {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_post_smp_download_log', array(__CLASS__, 'download_log'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    }

    public static function register_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Split Mercado Pago', 'split-mercado-pago'),
            __('Split Mercado Pago', 'split-mercado-pago'),
            'manage_woocommerce',
            'split-mercado-pago',
            array(__CLASS__, 'render_settings')
        );
    }

    public static function register_settings(): void
    {
        register_setting('smp_settings', 'smp_client_id', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('smp_settings', 'smp_client_secret', array('sanitize_callback' => array(__CLASS__, 'sanitize_client_secret')));
        register_setting('smp_settings', 'smp_public_key', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('smp_settings', 'smp_commission_percent', array('sanitize_callback' => 'floatval'));
        register_setting('smp_settings', 'smp_commission_fixed', array('sanitize_callback' => 'floatval'));
        register_setting('smp_settings', 'smp_sandbox_mode', array('sanitize_callback' => array(__CLASS__, 'sanitize_checkbox')));
        register_setting('smp_settings', 'smp_logs_enabled', array('sanitize_callback' => array(__CLASS__, 'sanitize_checkbox')));
    }

    public static function sanitize_checkbox($value): string
    {
        return $value === 'yes' ? 'yes' : 'no';
    }

    public static function sanitize_client_secret($value): string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return (string) get_option('smp_client_secret', '');
        }
        return sanitize_text_field($value);
    }

    public static function render_settings(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Acesso negado.', 'split-mercado-pago'));
        }

        $redirect_uri = OAuth::get_redirect_uri();
        $vendors = get_users(array(
            'role__in' => array('vendor', 'seller'),
            'fields' => array('ID', 'display_name', 'user_email'),
        ));
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Split Mercado Pago', 'split-mercado-pago'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smp_settings'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="smp_client_id">Client ID</label></th>
                        <td><input name="smp_client_id" id="smp_client_id" type="text" class="regular-text" value="<?php echo esc_attr(get_option('smp_client_id', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smp_client_secret">Client Secret</label></th>
                        <td><input name="smp_client_secret" id="smp_client_secret" type="password" class="regular-text" value="" autocomplete="new-password"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smp_public_key">Public Key</label></th>
                        <td><input name="smp_public_key" id="smp_public_key" type="text" class="regular-text" value="<?php echo esc_attr(get_option('smp_public_key', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smp_commission_percent"><?php echo esc_html__('Comissão (%)', 'split-mercado-pago'); ?></label></th>
                        <td><input name="smp_commission_percent" id="smp_commission_percent" type="number" step="0.01" class="small-text" value="<?php echo esc_attr(get_option('smp_commission_percent', '0')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="smp_commission_fixed"><?php echo esc_html__('Comissão fixa', 'split-mercado-pago'); ?></label></th>
                        <td><input name="smp_commission_fixed" id="smp_commission_fixed" type="number" step="0.01" class="small-text" value="<?php echo esc_attr(get_option('smp_commission_fixed', '0')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row">Sandbox</th>
                        <td>
                            <label>
                                <input type="checkbox" name="smp_sandbox_mode" value="yes" <?php checked(get_option('smp_sandbox_mode', 'no'), 'yes'); ?>>
                                <?php echo esc_html__('Ativar modo sandbox', 'split-mercado-pago'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Logs</th>
                        <td>
                            <label>
                                <input type="checkbox" name="smp_logs_enabled" value="yes" <?php checked(get_option('smp_logs_enabled', 'no'), 'yes'); ?>>
                                <?php echo esc_html__('Ativar logs', 'split-mercado-pago'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Redirect URI</th>
                        <td><input type="text" class="regular-text" value="<?php echo esc_attr($redirect_uri); ?>" readonly></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2><?php echo esc_html__('Status OAuth dos vendedores', 'split-mercado-pago'); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Vendedor', 'split-mercado-pago'); ?></th>
                        <th><?php echo esc_html__('Status', 'split-mercado-pago'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendors)) : ?>
                        <tr><td colspan="2"><?php echo esc_html__('Nenhum vendedor encontrado.', 'split-mercado-pago'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($vendors as $vendor) : ?>
                            <?php $connected = get_user_meta($vendor->ID, 'smp_mp_connected', true) === 'yes'; ?>
                            <tr>
                                <td><?php echo esc_html($vendor->display_name); ?></td>
                                <td><?php echo $connected ? esc_html__('Conectado', 'split-mercado-pago') : esc_html__('Não conectado', 'split-mercado-pago'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <h2><?php echo esc_html__('Logs', 'split-mercado-pago'); ?></h2>
            <p><?php echo esc_html__('Baixe o log do dia para auditoria.', 'split-mercado-pago'); ?></p>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=smp_download_log'), 'smp_download_log')); ?>">
                <?php echo esc_html__('Baixar log', 'split-mercado-pago'); ?>
            </a>
        </div>
        <?php
    }

    public static function download_log(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Acesso negado.', 'split-mercado-pago'));
        }
        check_admin_referer('smp_download_log');

        $file = Logger::instance()->get_log_file();
        if (!file_exists($file)) {
            wp_die(esc_html__('Arquivo de log não encontrado.', 'split-mercado-pago'));
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="split-mercado-pago-log.txt"');
        readfile($file);
        exit;
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'woocommerce_page_split-mercado-pago') {
            return;
        }
        wp_enqueue_style('smp-admin', SMP_PLUGIN_URL . 'assets/admin.css', array(), SMP_VERSION);
    }
}
