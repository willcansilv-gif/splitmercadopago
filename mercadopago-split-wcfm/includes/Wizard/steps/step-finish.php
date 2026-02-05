<?php
$oauth_ok = get_option('mpsw_oauth_connected');
$commission_ok = get_option('mpsw_commission_percent') !== false;
$vendors_ok = true;
$vendors = get_users(array(
    'role__in' => array('wcfm_vendor', 'vendor'),
    'number' => 50,
));
foreach ($vendors as $vendor) {
    if (!get_user_meta($vendor->ID, 'mpsw_vendor_connected', true)) {
        $vendors_ok = false;
        break;
    }
}
$webhook_ok = get_option('mpsw_webhook_status') === 'active';
?>
<h2><?php esc_html_e('Etapa 7 — Finalização', 'mercadopago-split-wcfm'); ?></h2>
<p><?php esc_html_e('Revise o checklist final e ative os pagamentos.', 'mercadopago-split-wcfm'); ?></p>

<ul class="mpsw-checklist">
    <li class="<?php echo $oauth_ok ? 'is-ok' : 'is-error'; ?>"><?php esc_html_e('OAuth OK', 'mercadopago-split-wcfm'); ?></li>
    <li class="<?php echo $commission_ok ? 'is-ok' : 'is-error'; ?>"><?php esc_html_e('Split configurado', 'mercadopago-split-wcfm'); ?></li>
    <li class="<?php echo $vendors_ok ? 'is-ok' : 'is-error'; ?>"><?php esc_html_e('Vendedores OK', 'mercadopago-split-wcfm'); ?></li>
    <li class="<?php echo $webhook_ok ? 'is-ok' : 'is-error'; ?>"><?php esc_html_e('Webhook OK', 'mercadopago-split-wcfm'); ?></li>
</ul>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('mpsw_save_step'); ?>
    <input type="hidden" name="action" value="mpsw_save_step" />
    <input type="hidden" name="step" value="finish" />
    <button class="button button-primary" type="submit" <?php disabled(!$oauth_ok || !$commission_ok || !$webhook_ok); ?>>
        <?php esc_html_e('Concluir Configuração e Ativar Pagamentos', 'mercadopago-split-wcfm'); ?>
    </button>
</form>
