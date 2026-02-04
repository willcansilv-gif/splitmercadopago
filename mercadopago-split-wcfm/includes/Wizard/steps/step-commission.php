<?php
$percent = get_option('mpsw_commission_percent', 0);
$fixed = get_option('mpsw_commission_fixed', 0);
$split = MPSW_Split::instance()->calculate_split(100, $percent, $fixed);
?>
<h2><?php esc_html_e('Etapa 3 — Configuração do Marketplace', 'mercadopago-split-wcfm'); ?></h2>
<p><?php esc_html_e('Defina a comissão do administrador e simule o split em tempo real.', 'mercadopago-split-wcfm'); ?></p>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mpsw-form" data-split-preview>
    <?php wp_nonce_field('mpsw_save_step'); ?>
    <input type="hidden" name="action" value="mpsw_save_step" />
    <input type="hidden" name="step" value="commission" />
    <label for="commission_percent"><?php esc_html_e('Percentual de comissão (%)', 'mercadopago-split-wcfm'); ?></label>
    <input type="number" step="0.01" min="0" max="100" id="commission_percent" name="commission_percent" value="<?php echo esc_attr($percent); ?>" required />

    <label for="commission_fixed"><?php esc_html_e('Taxa fixa (opcional)', 'mercadopago-split-wcfm'); ?></label>
    <input type="number" step="0.01" min="0" id="commission_fixed" name="commission_fixed" value="<?php echo esc_attr($fixed); ?>" />

    <div class="mpsw-split-preview">
        <h4><?php esc_html_e('Simulação para R$ 100,00', 'mercadopago-split-wcfm'); ?></h4>
        <p><?php printf(esc_html__('Marketplace: R$ %s', 'mercadopago-split-wcfm'), number_format($split['marketplace'], 2, ',', '.')); ?></p>
        <p><?php printf(esc_html__('Vendedor: R$ %s', 'mercadopago-split-wcfm'), number_format($split['vendor'], 2, ',', '.')); ?></p>
    </div>

    <button class="button button-primary" type="submit">
        <?php esc_html_e('Continuar', 'mercadopago-split-wcfm'); ?>
    </button>
</form>
