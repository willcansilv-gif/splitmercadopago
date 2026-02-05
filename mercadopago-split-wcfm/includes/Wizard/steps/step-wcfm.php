<?php
$vendors = get_users(array(
    'role__in' => array('wcfm_vendor', 'vendor'),
    'number' => 50,
));
?>
<h2><?php esc_html_e('Etapa 4 — Integração com WCFM', 'mercadopago-split-wcfm'); ?></h2>
<p><?php esc_html_e('Conecte os vendedores via OAuth. Pagamentos são bloqueados se o vendedor não estiver conectado.', 'mercadopago-split-wcfm'); ?></p>

<table class="widefat striped">
    <thead>
        <tr>
            <th><?php esc_html_e('Vendedor', 'mercadopago-split-wcfm'); ?></th>
            <th><?php esc_html_e('Status', 'mercadopago-split-wcfm'); ?></th>
            <th><?php esc_html_e('Ação', 'mercadopago-split-wcfm'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($vendors)) : ?>
            <tr>
                <td colspan="3"><?php esc_html_e('Nenhum vendedor encontrado.', 'mercadopago-split-wcfm'); ?></td>
            </tr>
        <?php else : ?>
            <?php foreach ($vendors as $vendor) : ?>
                <?php
                $connected = get_user_meta($vendor->ID, 'mpsw_vendor_connected', true);
                $nonce = wp_create_nonce('mpsw_vendor_oauth_' . $vendor->ID);
                $state = $nonce . ':' . $vendor->ID;
                $connect_url = MPSW_OAuth::instance()->get_vendor_authorize_url($vendor->ID, $state);
                ?>
                <tr>
                    <td><?php echo esc_html($vendor->display_name); ?></td>
                    <td>
                        <?php if ($connected) : ?>
                            <span class="mpsw-badge is-success"><?php esc_html_e('Conectado', 'mercadopago-split-wcfm'); ?></span>
                        <?php else : ?>
                            <span class="mpsw-badge is-warning"><?php esc_html_e('Não conectado', 'mercadopago-split-wcfm'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$connected) : ?>
                            <a class="button" href="<?php echo esc_url($connect_url); ?>">
                                <?php esc_html_e('Conectar Conta Mercado Pago', 'mercadopago-split-wcfm'); ?>
                            </a>
                        <?php else : ?>
                            <?php esc_html_e('OK', 'mercadopago-split-wcfm'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('mpsw_save_step'); ?>
    <input type="hidden" name="action" value="mpsw_save_step" />
    <input type="hidden" name="step" value="wcfm" />
    <button class="button button-primary" type="submit">
        <?php esc_html_e('Continuar', 'mercadopago-split-wcfm'); ?>
    </button>
</form>
