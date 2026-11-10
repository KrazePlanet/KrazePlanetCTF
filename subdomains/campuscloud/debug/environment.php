<?php

/*
 * CAMPUSCLOUD ENTERPRISE PLATFORM
 * Operational Actuator: Environment Properties
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

$response = [
    'activeProfiles' => [
        'production',
        'cloud',
        'aws-us-east-1'
    ],
    'propertySources' => [
        [
            'name' => 'systemEnvironment',
            'properties' => [
                'APPLICATION_NAME' => [
                    'value' => 'CampusCloud Enterprise LMS'
                ],
                'APPLICATION_ENV' => [
                    'value' => 'production'
                ],
                'APPLICATION_VERSION' => [
                    'value' => '4.8.3-RELEASE'
                ],
                'APPLICATION_BUILD' => [
                    'value' => '2026.08.21-1842'
                ],
                'SERVER_REGION' => [
                    'value' => 'us-east-1a'
                ],
                'SERVER_ROLE' => [
                    'value' => 'gateway-web'
                ],
                'DB_CONNECTION' => [
                    'value' => 'pgsql'
                ],
                'DB_HOST' => [
                    'value' => 'rds-postgres-cluster.internal.campuscloud.test'
                ],
                'DB_PORT' => [
                    'value' => '5432'
                ],
                'DB_DATABASE' => [
                    'value' => 'campuscloud_prod_db'
                ],
                'DB_USERNAME' => [
                    'value' => 'campus_app_svc'
                ],
                'DB_PASSWORD' => [
                    'value' => 'Campu$Cloud_VPC_Secret2026!'
                ],
                'DB_POOL_MIN' => [
                    'value' => '5'
                ],
                'DB_POOL_MAX' => [
                    'value' => '40'
                ],
                'AWS_DEFAULT_REGION' => [
                    'value' => 'us-east-1'
                ],
                'AWS_S3_BUCKET' => [
                    'value' => 'campuscloud-student-backups-us-east-1'
                ],
                'AWS_ACCESS_KEY_ID' => [
                    'value' => 'AKIA49281EXAMPLEKEY26'
                ],
                'AWS_SECRET_ACCESS_KEY' => [
                    'value' => 'v8291aBcD3eFgHiJkLmNoP4qRsTuVwXyZ5'
                ],
                'AWS_S3_ENDPOINT' => [
                    'value' => 'https://s3.us-east-1.amazonaws.com'
                ],
                'REDIS_HOST' => [
                    'value' => 'cache.internal.campuscloud.test'
                ],
                'REDIS_PORT' => [
                    'value' => '6379'
                ],
                'REDIS_PASSWORD' => [
                    'value' => 'RedisClusterAuth2026'
                ],
                'REDIS_URL' => [
                    'value' => 'redis://:RedisClusterAuth2026@cache.internal.campuscloud.test:6379/0'
                ],
                'QUEUE_CONNECTION' => [
                    'value' => 'rabbitmq'
                ],
                'RABBITMQ_HOST' => [
                    'value' => 'queue.internal.campuscloud.test'
                ],
                'RABBITMQ_PORT' => [
                    'value' => '5672'
                ],
                'RABBITMQ_USER' => [
                    'value' => 'campus_worker'
                ],
                'RABBITMQ_PASSWORD' => [
                    'value' => 'VpcRabbitPass2026'
                ],
                'RABBITMQ_URL' => [
                    'value' => 'amqp://campus_worker:VpcRabbitPass2026@queue.internal.campuscloud.test:5672/campus'
                ],
                'SENDGRID_API_KEY' => [
                    'value' => 'SG.9482103aBCdeFG.829104812aBcDeFgHiJkLmNo'
                ],
                'SSO_OIDC_CLIENT_ID' => [
                    'value' => 'campuscloud-prod-oidc'
                ],
                'SSO_OIDC_CLIENT_SECRET' => [
                    'value' => 'sec_oidc_live_9481a7b02c8194'
                ],
                'INTERNAL_API_HOST' => [
                    'value' => 'http://api.internal.campuscloud.test'
                ],
                'INTERNAL_SERVICE_BEARER' => [
                    'value' => 'Bearer campus_internal_svc_token_948210c4'
                ],
                'ENCRYPTION_KEY' => [
                    'value' => 'base64:XG8yYTFkZjkyMTA0ODFiYzQ4MjAxODQ5MWE='
                ]
            ]
        ]
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
