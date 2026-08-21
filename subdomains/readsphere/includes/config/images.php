<?php
/**
 * Configuration des images et avatars
 */

return [
    'max_upload_size' => 5 * 1024 * 1024, // 5MB
    'allowed_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ],
    'default_quality' => 85,
    
    'avatars' => [
        'max_width' => 500,
        'max_height' => 500,
        'quality' => 80,
        'default' => [
            'path' => '/assets/images/default-avatar.png',
            'width' => 200,
            'height' => 200,
            'bg_color' => '#6B7280',
            'text_color' => '#FFFFFF',
            'font_size' => 80,
            'initials' => '?'
        ]
    ],
    
    'thumbnails' => [
        'small' => [
            'width' => 150,
            'height' => 150,
            'crop' => true
        ],
        'medium' => [
            'width' => 400,
            'height' => 400,
            'crop' => false
        ],
        'large' => [
            'width' => 800,
            'height' => 800,
            'crop' => false
        ]
    ]
];
