<?php
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('Sem permissão.', 'mercadopago-split-wcfm'));
}

$upload_dir = wp_upload_dir();
$log_dir = trailingslashit($upload_dir['basedir']) . 'mercadopago-split-logs/';
$log_file = $log_dir . 'mpsw-' . gmdate('Y-m-d') . '.log';
?>
<div class="wrap">
    <h1><?php esc_html_e('Logs — Mercado Pago Split', 'mercadopago-split-wcfm'); ?></h1>
    <p><?php esc_html_e('Os logs são armazenados em /wp-content/uploads/mercadopago-split-logs/.', 'mercadopago-split-wcfm'); ?></p>

    <?php if (file_exists($log_file)) : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('mpsw_download_logs'); ?>
            <input type="hidden" name="action" value="mpsw_download_logs" />
            <button class="button button-primary" type="submit">
                <?php esc_html_e('Baixar log de hoje', 'mercadopago-split-wcfm'); ?>
            </button>
        </form>
    <?php else : ?>
        <div class="notice notice-info"><p><?php esc_html_e('Nenhum log encontrado hoje.', 'mercadopago-split-wcfm'); ?></p></div>
    <?php endif; ?>
</div>
