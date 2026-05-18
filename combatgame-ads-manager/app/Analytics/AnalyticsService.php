<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Analytics;

final class AnalyticsService
{
    public function dashboardStats(): array
    {
        global $wpdb;
        $today = gmdate('Y-m-d');
        $impressions = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}cgam_impressions WHERE DATE(seen_at)=%s", $today));
        $clicks = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}cgam_clicks WHERE DATE(clicked_at)=%s", $today));
        $active = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cgam_campaigns WHERE status='active'");
        $paused = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cgam_campaigns WHERE status='paused'");
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        return compact('impressions', 'clicks', 'active', 'paused', 'ctr');
    }
}
