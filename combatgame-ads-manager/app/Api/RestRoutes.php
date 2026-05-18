<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Api;

use CombatGameAdsManager\Analytics\AnalyticsService;

final class RestRoutes
{
    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            register_rest_route('cgam/v1', '/dashboard', ['methods'=>'GET','callback'=>[$this,'dashboard'],'permission_callback'=>fn()=>current_user_can('manage_options')]);
        });
    }
    public function dashboard(): \WP_REST_Response { return new \WP_REST_Response((new AnalyticsService())->dashboardStats()); }
}
