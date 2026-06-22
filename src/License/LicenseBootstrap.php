<?php
namespace App\License;

class LicenseBootstrap
{
    private static array $coreRoutes = ['ledger','ledger-','plan-','debt','debt-','reimburse','reimburse-','settings','settings-','login','logout','register','changelog','nav','nav-','license-activate'];

    public static function check(): array
    {
        LicenseClient::init();
        $trialDays = LicenseClient::getTrialDays();
        $isActivated = LicenseClient::isActivated();
        $isExpired = !$isActivated && $trialDays <= 0;
        $info = LicenseClient::getInfo();

        return ['trial_days' => $trialDays, 'activated' => $isActivated, 'expired' => $isExpired, 'info' => $info];
    }

    public static function isRestricted(string $route): bool
    {
        foreach (self::$coreRoutes as $prefix) {
            if (str_starts_with($route, $prefix)) return false;
        }
        return true;
    }

    public static function getCoreRoutes(): array { return self::$coreRoutes; }
}
