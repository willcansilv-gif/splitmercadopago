<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Tracking;

final class TrackingRouter
{
    public function register(): void
    {
        add_action('init', [$this, 'rewrite']);
        add_action('template_redirect', [$this, 'handle']);
    }
    public function rewrite(): void { add_rewrite_rule('^cgam-click/([0-9]+)/?', 'index.php?cgam_click=$matches[1]', 'top'); add_rewrite_tag('%cgam_click%', '([0-9]+)'); }
    public function handle(): void
    {
        $id = (int) get_query_var('cgam_click'); if (! $id) return;
        global $wpdb;
        $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cgam_campaigns WHERE id=%d", $id), ARRAY_A);
        if (! $campaign) wp_die('Invalid campaign', '', ['response'=>404]);
        $ipHash = hash('sha256', wp_privacy_anonymize_ip((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')));
        $wpdb->insert("{$wpdb->prefix}cgam_clicks", ['campaign_id'=>$id, 'clicked_at'=>current_time('mysql', true), 'device'=>wp_is_mobile() ? 'mobile':'desktop', 'ip_hash'=>$ipHash, 'referrer'=>sanitize_text_field((string)wp_get_referer())], ['%d','%s','%s','%s','%s']);
        wp_safe_redirect(esc_url_raw((string)$campaign['target_url'])); exit;
    }
}
