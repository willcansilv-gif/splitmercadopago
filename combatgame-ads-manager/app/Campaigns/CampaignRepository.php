<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Campaigns;

final class CampaignRepository
{
    public function active(array $filters = []): array
    {
        global $wpdb;
        $where = "WHERE status='active'";
        $params = [];
        foreach (['category','city','state','game_type'] as $f) {
            if (! empty($filters[$f])) { $where .= " AND {$f}=%s"; $params[] = sanitize_text_field((string)$filters[$f]); }
        }
        $sql = "SELECT * FROM {$wpdb->prefix}cgam_campaigns {$where} ORDER BY priority DESC, rotation_weight DESC LIMIT 20";
        return $params ? $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);
    }
}
