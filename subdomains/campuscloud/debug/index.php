<?php

/*
 * CAMPUSCLOUD ENTERPRISE PLATFORM
 * Operational Actuator: Root Discovery
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

$actuator_index = [
    '_links' => [
        'self' => [
            'href' => '/debug'
        ],
        'environment' => [
            'href' => '/debug/environment.php'
        ],
        'info' => [
            'href' => '/debug/info.php'
        ],
        'status' => [
            'href' => '/debug/status.php'
        ]
    ]
];

echo json_encode($actuator_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
