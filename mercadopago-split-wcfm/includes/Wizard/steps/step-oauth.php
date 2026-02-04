<?php
$client_id = get_option('mpsw_client_id');
$client_secret = get_option('mpsw_client_secret');
$connected = get_option('mpsw_oauth_connected');
$state = wp_create_nonce('mpsw_oauth_state');
$authorize_url = '';
if ($client_id) {
    $authorize_url = MPSW_OAuth::instance()->get_authorize_url($state);
}
?>
<h2><?php esc_html_e('Etapa 2 — Conexão com Mercado Pago (OAuth)', 'mercadopago-split-wcfm'); ?></h2>
<p><?php esc_html_e('Conecte sua conta Mercado Pago usando o fluxo oficial OAuth. Tokens são capturados automaticamente.', 'mercadopago-split-wcfm'); ?></p>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mpsw-form">
    <?php wp_nonce_field('mpsw_save_step'); ?>
    <input type="hidden" name="action" value="mpsw_save_step" />
    <input type="hidden" name="step" value="oauth" />
    <label for="mpsw_client_id"><?php esc_html_e('Client ID da aplicação', 'mercadopago-split-wcfm'); ?></label>
    <input type="text" id="mpsw_client_id" name="client_id" value="<?php echo esc_attr($client_id); ?>" required />
    <p class="description"><?php esc_html_e('O Client ID vem do painel de aplicações do Mercado Pago.', 'mercadopago-split-wcfm'); ?></p>
    <label for="mpsw_client_secret"><?php esc_html_e('Client Secret da aplicação', 'mercadopago-split-wcfm'); ?></label>
    <input type="password" id="mpsw_client_secret" name="client_secret" value="" autocomplete="new-password" required />
    <p class="description"><?php esc_html_e('O Client Secret vem do painel de aplicações do Mercado Pago.', 'mercadopago-split-wcfm'); ?></p>
    <button class="button" type="submit"><?php esc_html_e('Salvar Client ID', 'mercadopago-split-wcfm'); ?></button>
</form>

<div class="mpsw-oauth">
    <?php if ($connected) : ?>
        <div class="mpsw-alert is-success"><?php esc_html_e('Conectado com sucesso.', 'mercadopago-split-wcfm'); ?></div>
    <?php else : ?>
        <a class="button button-primary button-hero" href="<?php echo esc_url($authorize_url); ?>" <?php if (!$client_id) echo 'disabled'; ?>>
            <?php esc_html_e('Conectar com Mercado Pago', 'mercadopago-split-wcfm'); ?>
        </a>
        <?php if (!$client_id) : ?>
            <p class="description"><?php esc_html_e('Informe o Client ID para habilitar o botão.', 'mercadopago-split-wcfm'); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('mpsw_save_step'); ?>
    <input type="hidden" name="action" value="mpsw_save_step" />
    <input type="hidden" name="step" value="oauth" />
    <button class="button button-primary" <?php disabled(!$connected); ?>>
        <?php esc_html_e('Continuar', 'mercadopago-split-wcfm'); ?>
    </button>
</form>
