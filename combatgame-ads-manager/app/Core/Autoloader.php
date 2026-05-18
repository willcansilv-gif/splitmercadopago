<?php

declare(strict_types=1);

namespace CombatGameAdsManager\Core;

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(static function (string $class): void {
            if (! str_starts_with($class, 'CombatGameAdsManager\\')) {
                return;
            }
            $path = CGAM_PATH . 'app/' . str_replace(['CombatGameAdsManager\\', '\\'], ['', '/'], $class) . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
        });
    }
}
