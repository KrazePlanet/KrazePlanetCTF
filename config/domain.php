<?php
// config/domain.php

function startKrazeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getKrazeBaseDomain() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/:\d+$/', '', $host);
    return $host;
}

// Maps a labs.json "access" path to the URL students open.
// Locally (no LAB_BASE_DOMAIN) labs stay at /subdomains/<lab>.
// In production LAB_BASE_DOMAIN=pwnedbox.site turns /subdomains/race_condition
// into https://race-condition.pwnedbox.site/ (underscores are not valid in hostnames).
function krazeLabUrl($access) {
    $base = getenv('LAB_BASE_DOMAIN');
    if (!$base || !preg_match('#^/subdomains/([A-Za-z0-9_-]+)/?(.*)$#', $access, $m)) {
        return $access;
    }
    $host = str_replace('_', '-', strtolower($m[1]));
    $app = parse_url(getenv('APP_URL') ?: 'https://' . $base);
    $port = isset($app['port']) ? ':' . $app['port'] : '';
    return ($app['scheme'] ?? 'https') . '://' . $host . '.' . $base . $port . '/' . $m[2];
}

// Absolute URL of the main site, for links in emails and copyable URLs.
function krazeSiteUrl($path = '/') {
    return rtrim(getenv('APP_URL') ?: 'http://localhost', '/') . $path;
}
