<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Admin;

use CombatGameAdsManager\Analytics\AnalyticsService;

final class AdminController
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }
    public function menu(): void
    {
        add_menu_page('CombatGame Ads', 'CombatGame Ads', 'manage_options', 'cgam-dashboard', [$this, 'dashboard'], 'dashicons-megaphone', 26);
    }
    public function assets(string $hook): void
    {
        if (! str_contains($hook, 'cgam-dashboard')) return;
        wp_enqueue_style('cgam-admin', CGAM_URL . 'assets/css/admin.css', [], CGAM_VERSION);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.2', true);
        wp_enqueue_script('cgam-admin', CGAM_URL . 'assets/js/admin.js', ['chart-js'], CGAM_VERSION, true);
        wp_localize_script('cgam-admin', 'cgamData', ['rest'=>esc_url_raw(rest_url('cgam/v1/dashboard')), 'nonce'=>wp_create_nonce('wp_rest')]);
    }
    public function dashboard(): void
    {
        $stats = (new AnalyticsService())->dashboardStats();
        include CGAM_PATH . 'admin/views/dashboard.php';
    }
}
