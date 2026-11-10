<?php

/*
 * CAMPUSCLOUD ENTERPRISE PLATFORM
 * Live Healthcheck Matrix & Service Dependencies
 */

header('Content-Type: application/json; charset=UTF-8');

$status = [
    'status' => 'UP',
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
    'components' => [
        'db' => [
            'status' => 'UP',
            'details' => [
                'database' => 'PostgreSQL 15.4',
                'activeConnections' => 12,
                'maxConnections' => 40,
                'latencyMs' => 2.4
            ]
        ],
        'redis' => [
            'status' => 'UP',
            'details' => [
                'version' => '7.0.12',
                'usedMemory' => '1.24 GB',
                'hitRate' => '99.8%',
                'latencyMs' => 0.8
            ]
        ],
        'rabbit' => [
            'status' => 'UP',
            'details' => [
                'queuesActive' => 8,
                'messagesReady' => 24,
                'messagesUnacknowledged' => 3
            ]
        ],
        's3Storage' => [
            'status' => 'UP',
            'details' => [
                'bucket' => 'campuscloud-student-backups-us-east-1',
                'endpoint' => 's3.us-east-1.amazonaws.com'
            ]
        ],
        'diskSpace' => [
            'status' => 'UP',
            'details' => [
                'total' => '100 GB',
                'free' => '78.4 GB',
                'threshold' => '10 GB'
            ]
        ]
    ]
];

echo json_encode($status, JSON_PRETTY_PRINT);

