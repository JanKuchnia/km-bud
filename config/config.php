<?php
/**
 * KM-BUD Configuration
 * XAMPP local / Hostinger production
 */

return [
    'db' => [
        'host'    => 'localhost',
        'name'    => 'kmbud',
        'user'    => 'root',        // XAMPP default
        'pass'    => '',            // XAMPP default (empty)
        'charset' => 'utf8mb4',
    ],
    'admin' => [
        // Generated with: password_hash('admin123', PASSWORD_BCRYPT)
        // Change this after first login!
        'password_hash' => '$2y$12$EuyiFdhcQHQ6UYzradlMmuGdxpbBf1.usst3vLtugQ0VzeIp8c2Iq',
    ],
    'tinypng' => [
        'api_key' => '0Zyzzv1z4CYLWP96YTyfQzfpdYZNn0PD',
    ],
    'google_places' => [
        'api_key'  => 'AIzaSyBQajUsVxRQLvlwLmGgPI-c-ebLqVm7LfM', // Set your Google Places API Key here to enable auto-import
        'place_id' => 'ChIJ6XciKERpFkcRoFN8VQxqdsU', // KM-BUD Place ID
    ],
    'upload' => [
        'max_size'      => 10 * 1024 * 1024,  // 10MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'quality'       => 85,
        'max_dimension' => 2048,
    ],
];
