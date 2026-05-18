<?php
/**
 * Plugin Name: CombatGame Ads Manager PRO
 * Description: Enterprise ad manager for CombatGame ecosystem.
 * Version: 1.0.0
 * Author: William Candido
 * Text Domain: combatgame-ads-manager
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('CGAM_VERSION', '1.0.0');
define('CGAM_PATH', plugin_dir_path(__FILE__));
define('CGAM_URL', plugin_dir_url(__FILE__));

require_once CGAM_PATH . 'app/Core/Autoloader.php';
CombatGameAdsManager\Core\Autoloader::register();

register_activation_hook(__FILE__, static function (): void {
    (new CombatGameAdsManager\Database\Schema())->create();
});

register_uninstall_hook(__FILE__, 'cgam_uninstall_cleanup');

function cgam_uninstall_cleanup(): void {
    require CGAM_PATH . 'uninstall.php';
}

add_action('plugins_loaded', static function (): void {
    $app = new CombatGameAdsManager\Core\Application();
    $app->boot();
});
