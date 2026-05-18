<?php
declare(strict_types=1);
namespace CombatGameAdsManager\Core;

use CombatGameAdsManager\Admin\AdminController;
use CombatGameAdsManager\Ads\Renderer;
use CombatGameAdsManager\Api\RestRoutes;
use CombatGameAdsManager\Shortcodes\ShortcodeManager;
use CombatGameAdsManager\Tracking\TrackingRouter;
use CombatGameAdsManager\Integrations\ElementorWidget;

final class Application
{
    public function boot(): void
    {
        (new AdminController())->register();
        (new Renderer())->register();
        (new ShortcodeManager())->register();
        (new TrackingRouter())->register();
        (new RestRoutes())->register();
        (new ElementorWidget())->register();
    }
}
