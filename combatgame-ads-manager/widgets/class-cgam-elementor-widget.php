<?php
declare(strict_types=1);

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class CGAM_Elementor_Widget extends Widget_Base {
    public function get_name(): string { return 'cgam_ads'; }
    public function get_title(): string { return 'CombatGame Ads'; }
    public function get_icon(): string { return 'eicon-banner'; }
    public function get_categories(): array { return ['general']; }
    protected function register_controls(): void {
        $this->start_controls_section('content_section', ['label' => 'Configurações']);
        $this->add_control('slot', ['label'=>'Slot','type'=>Controls_Manager::TEXT]);
        $this->add_control('category', ['label'=>'Categoria','type'=>Controls_Manager::TEXT]);
        $this->add_control('city', ['label'=>'Cidade','type'=>Controls_Manager::TEXT]);
        $this->add_control('size', ['label'=>'Tamanho','type'=>Controls_Manager::TEXT]);
        $this->end_controls_section();
    }
    protected function render(): void {
        $settings = $this->get_settings_for_display();
        echo do_shortcode('[cg_ads slot="' . esc_attr((string)$settings['slot']) . '" category="' . esc_attr((string)$settings['category']) . '" city="' . esc_attr((string)$settings['city']) . '" size="' . esc_attr((string)$settings['size']) . '"]');
    }
}
