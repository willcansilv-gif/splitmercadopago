<?php
$webhook_url = MPSW_Webhook::instance()->get_webhook_url();
$status = get_option('mpsw_webhook_status', 'inactive');
$secret = get_option('mpsw_webhook_secret');
?>
<h2><?php esc_html_e('Etapa 5 — Webhooks', 'mercadopago-split-wcfm'); ?></h2>
<p><?php esc_html_e('Cadastre esta URL no painel do Mercado Pago e valide a assinatura dos eventos.', 'mercadopago-split-wcfm'); ?></p>

<div class="mpsw-webhook-url">
    <code><?php echo esc_html($webhook_url); ?></code>
</div>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mpsw-form">
    <?php wp_nonce_field('mpsw_save_step'); ?>
    <input type="hidden" name="action" value="mpsw_save_step" />
    <input type="hidden" name="step" value="webhook" />
    <label for="mpsw_webhook_secret"><?php esc_html_e('Segredo do webhook', 'mercadopago-split-wcfm'); ?></label>
    <input type="password" id="mpsw_webhook_secret" name="webhook_secret" value="" autocomplete="new-password" required />
    <p class="description"><?php esc_html_e('Copie o segredo configurado no painel do Mercado Pago.', 'mercadopago-split-wcfm'); ?></p>

<p>
    <?php esc_html_e('Status:', 'mercadopago-split-wcfm'); ?>
    <strong><?php echo esc_html($status === 'active' ? __('Ativo', 'mercadopago-split-wcfm') : __('Pendente', 'mercadopago-split-wcfm')); ?></strong>
</p>

    <button class="button button-primary" type="submit">
        <?php esc_html_e('Validar e Continuar', 'mercadopago-split-wcfm'); ?>
    </button>
</form>
