<?php

if (!defined('ABSPATH')) {
    exit;
}

class MPSW_Split {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function calculate_split($total, $commission_percent, $commission_fixed = 0) {
        $total = floatval($total);
        $commission_percent = floatval($commission_percent);
        $commission_fixed = floatval($commission_fixed);

        $commission_value = ($total * ($commission_percent / 100)) + $commission_fixed;
        $commission_value = max(0, min($commission_value, $total));

        return array(
            'marketplace' => round($commission_value, 2),
            'vendor' => round($total - $commission_value, 2),
        );
    }

    public function calculate_application_fee($total) {
        $percent = floatval(get_option('mpsw_commission_percent', 0));
        $fixed = floatval(get_option('mpsw_commission_fixed', 0));
        $split = $this->calculate_split($total, $percent, $fixed);
        return $split['marketplace'];
    }
}
