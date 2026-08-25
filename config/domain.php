<?php
// config/domain.php - Centralized Domain Configuration & Auto-Detection
// Users can customize their domain in config/domain.txt or via APP_DOMAIN environment variable

if (!function_exists('getKrazeConfiguredDomain')) {
    function getKrazeConfiguredDomain() {
        static $cachedDomain = null;
        if ($cachedDomain !== null) {
            return $cachedDomain;
        }

        $envDomain = getenv('APP_DOMAIN');
        if ($envDomain && trim($envDomain) !== '') {
            $cachedDomain = trim($envDomain);
            return $cachedDomain;
        }

        $domainTxtFile = __DIR__ . '/domain.txt';
        if (file_exists($domainTxtFile)) {
            $fileContent = trim(file_get_contents($domainTxtFile));
            if (!empty($fileContent)) {
                $cachedDomain = $fileContent;
                return $cachedDomain;
            }
        }

        $cachedDomain = 'kzlabs.in';
        return $cachedDomain;
    }
}

if (!function_exists('getKrazeBaseDomain')) {
    function getKrazeBaseDomain($overrideHost = null) {
        $httpHost = strtolower($overrideHost ?: ($_SERVER['HTTP_HOST'] ?? ''));
        $hostNoPort = preg_replace('/:\d+$/', '', $httpHost);
        $configured = getKrazeConfiguredDomain();

        if (empty($hostNoPort)) {
            return $configured;
        }

        // Check local development domains
        if (strpos($hostNoPort, 'localhost') !== false) {
            return 'localhost';
        }
        if (strpos($hostNoPort, 'localtest.me') !== false) {
            return 'localtest.me';
        }
        if (strpos($hostNoPort, '127.0.0.1') !== false || strpos($hostNoPort, 'nip.io') !== false) {
            return '127.0.0.1.nip.io';
        }

        // Check configured domain match
        if (strpos($hostNoPort, $configured) !== false) {
            return $configured;
        }

        // Dynamic fallback: extract primary domain from request
        $parts = explode('.', $hostNoPort);
        if (count($parts) >= 2) {
            return implode('.', array_slice($parts, -2));
        }

        return $hostNoPort;
    }
}

if (!function_exists('startKrazeSession')) {
    function startKrazeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $baseDomain = getKrazeBaseDomain();
            if ($baseDomain !== 'localhost' && !filter_var($baseDomain, FILTER_VALIDATE_IP)) {
                @ini_set('session.cookie_domain', '.' . ltrim($baseDomain, '.'));
            }
            @session_start();
        }
    }
}