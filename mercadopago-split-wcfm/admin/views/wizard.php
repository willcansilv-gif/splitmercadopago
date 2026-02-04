<?php
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('Sem permissão.', 'mercadopago-split-wcfm'));
}

$wizard = MPSW_Wizard::instance();
?>
<div class="wrap mpsw-wizard">
    <h1><?php esc_html_e('Wizard de Configuração — Mercado Pago Split', 'mercadopago-split-wcfm'); ?></h1>
    <?php $wizard->render_progress(); ?>
    <?php $wizard->render_steps_nav(); ?>
    <div class="mpsw-step">
        <?php $wizard->render_step(); ?>
    </div>
</div>
