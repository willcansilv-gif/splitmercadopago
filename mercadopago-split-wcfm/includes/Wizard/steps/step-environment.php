<?php

$checks = array(
    'wp_version' => array(
        'label' => __('WordPress mínimo 6.0', 'mercadopago-split-wcfm'),
        'ok' => version_compare(get_bloginfo('version'), '6.0', '>='),
    ),
    'woocommerce' => array(
        'label' => __('WooCommerce ativo', 'mercadopago-split-wcfm'),
        'ok' => class_exists('WooCommerce'),
    ),
    'wcfm' => array(
        'label' => __('WCFM Marketplace ativo', 'mercadopago-split-wcfm'),
        'ok' => class_exists('WCFM') || defined('WCFMmp_VERSION'),
    ),
    'https' => array(
        'label' => __('HTTPS ativo', 'mercadopago-split-wcfm'),
        'ok' => is_ssl(),
    ),
);

$all_ok = true;
foreach ($checks as $check) {
    if (!$check['ok']) {
        $all_ok = false;
        break;
    }
}
?>
<h2><?php esc_html_e('Etapa 1 — Verificação de Ambiente', 'mercadopago-split-wcfm'); ?></h2>
<p><?php esc_html_e('Valide os requisitos mínimos antes de continuar.', 'mercadopago-split-wcfm'); ?></p>
<ul class="mpsw-checklist">
    <?php foreach ($checks as $check) : ?>
        <li class="<?php echo $check['ok'] ? 'is-ok' : 'is-error'; ?>">
            <?php echo esc_html($check['label']); ?>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (!$all_ok) : ?>
    <div class="mpsw-alert is-error">
        <?php esc_html_e('Corrija os itens acima para prosseguir.', 'mercadopago-split-wcfm'); ?>
    </div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('mpsw_save_step'); ?>
    <input type="hidden" name="action" value="mpsw_save_step" />
    <input type="hidden" name="step" value="environment" />
    <button class="button button-primary" <?php disabled(!$all_ok); ?>>
        <?php esc_html_e('Continuar', 'mercadopago-split-wcfm'); ?>
    </button>
</form>
