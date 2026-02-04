<?php
$logs_enabled = get_option('mpsw_logs_enabled');
$debug_enabled = get_option('mpsw_debug_enabled');
?>
<h2><?php esc_html_e('Etapa 6 — Logs e Debug', 'mercadopago-split-wcfm'); ?></h2>
<p><?php esc_html_e('Controle os registros e o nível de depuração. Tokens nunca são exibidos.', 'mercadopago-split-wcfm'); ?></p>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mpsw-form">
    <?php wp_nonce_field('mpsw_save_step'); ?>
    <input type="hidden" name="action" value="mpsw_save_step" />
    <input type="hidden" name="step" value="logs" />

    <label>
        <input type="checkbox" name="logs_enabled" value="1" <?php checked($logs_enabled, 1); ?> />
        <?php esc_html_e('Ativar Logs (armazenado em /wp-content/uploads/mercadopago-split-logs/)', 'mercadopago-split-wcfm'); ?>
    </label>
    <p class="description"><?php esc_html_e('Recomendado para auditoria de operações financeiras.', 'mercadopago-split-wcfm'); ?></p>

    <label>
        <input type="checkbox" name="debug_enabled" value="1" <?php checked($debug_enabled, 1); ?> />
        <?php esc_html_e('Ativar Debug (mais detalhes no log)', 'mercadopago-split-wcfm'); ?>
    </label>
    <p class="description"><?php esc_html_e('Use apenas durante testes, pois aumenta o volume de logs.', 'mercadopago-split-wcfm'); ?></p>

    <button class="button button-primary" type="submit">
        <?php esc_html_e('Continuar', 'mercadopago-split-wcfm'); ?>
    </button>
</form>
