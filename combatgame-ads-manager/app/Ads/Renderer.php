<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Ads;

use CombatGameAdsManager\Campaigns\CampaignRepository;

final class Renderer
{
    public function register(): void {}
    public function render(array $atts = []): string
    {
        $campaigns = (new CampaignRepository())->active($atts);
        if (! $campaigns) return '<div class="cgam-empty">Sem anúncios disponíveis.</div>';
        $campaign = $campaigns[array_rand($campaigns)];
        $click = home_url('/cgam-click/' . (int)$campaign['id']);
        $title = esc_html($campaign['name']);
        $cta = esc_html($campaign['cta'] ?: 'Saiba mais');
        return "<article class='cgam-ad'><h4>{$title}</h4><a href='" . esc_url($click) . "' class='cgam-cta'>{$cta}</a></article>";
    }
}
