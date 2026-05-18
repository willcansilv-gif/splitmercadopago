<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Shortcodes;

use CombatGameAdsManager\Ads\Renderer;

final class ShortcodeManager
{
    public function register(): void
    {
        add_shortcode('cg_ads', [$this, 'ads']);
        add_shortcode('cg_ads_random', [$this, 'ads']);
        add_shortcode('cg_ads_slider', [$this, 'slider']);
        add_shortcode('cg_ads_grid', [$this, 'grid']);
        add_shortcode('cg_ads_sponsors', [$this, 'grid']);
    }
    public function ads(array $atts = []): string { return (new Renderer())->render($atts); }
    public function slider(array $atts = []): string { wp_enqueue_script('swiper-js', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.1.4', true); return '<div class="cgam-swiper swiper"><div class="swiper-wrapper"><div class="swiper-slide">'.(new Renderer())->render($atts).'</div></div></div>'; }
    public function grid(array $atts = []): string { return '<div class="cgam-grid">'.(new Renderer())->render($atts).'</div>'; }
}
