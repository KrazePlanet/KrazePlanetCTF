<?php

/*
 * CAMPUSCLOUD ENTERPRISE PLATFORM
 * Operational Actuator: System Build & Architecture
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

$system_info = [
    'app' => [
        'name' => 'CampusCloud Enterprise LMS',
        'version' => '4.8.3-RELEASE',
        'build' => '2026.08.21-1842',
        'commit' => '8a7f920bc4821e',
        'artifact' => 'campuscloud-gateway-service'
    ],
    'runtime' => [
        'php_version' => PHP_VERSION,
        'sapi' => php_sapi_name(),
        'os' => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time') . 's',
        'upload_max_filesize' => ini_get('upload_max_filesize')
    ],
    'network' => [
        'vpc_id' => 'vpc-08a9f21092',
        'subnet_id' => 'subnet-09f2194821',
        'private_ip' => '10.240.4.18',
        'gateway' => '10.240.4.1',
        'dns' => '10.240.0.2',
        'internal_domain' => 'internal.campuscloud.test'
    ],
    'cluster' => [
        'region' => 'us-east-1a',
        'node_id' => 'node04.internal.campuscloud.test',
        'role' => 'production-worker'
    ]
];

echo json_encode($system_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
