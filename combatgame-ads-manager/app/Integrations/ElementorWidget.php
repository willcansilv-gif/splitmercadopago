<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Integrations;

final class ElementorWidget
{
    public function register(): void
    {
        add_action('elementor/widgets/register', function ($manager): void {
            if (! class_exists('Elementor\\Widget_Base')) return;
            require_once CGAM_PATH . 'widgets/class-cgam-elementor-widget.php';
            $manager->register(new \CGAM_Elementor_Widget());
        });
    }
}
