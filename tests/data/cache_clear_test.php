<?php

/**
 * Test data for cache clear functionality
 * This file contains sample data structures for testing cache operations
 */

return [
    'simple_data' => [
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3',
    ],

    'complex_data' => [
        'users' => [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com'],
        ],
        'meta' => [
            'total' => 3,
            'page' => 1,
            'per_page' => 10,
            'last_updated' => '2024-01-15 10:30:00',
        ],
        'config' => [
            'cache_ttl' => 3600,
            'enable_compression' => true,
            'max_size' => 1024 * 1024, // 1MB
        ],
    ],

    'nested_data' => [
        'level1' => [
            'level2' => [
                'level3' => [
                    'level4' => [
                        'deep_value' => 'found_me',
                        'array_data' => [1, 2, 3, 4, 5],
                        'boolean_data' => true,
                        'null_data' => null,
                    ]
                ]
            ]
        ]
    ],

    'api_responses' => [
        'success' => [
            'status' => 'success',
            'code' => 200,
            'data' => [
                'message' => 'Operation completed successfully',
                'timestamp' => time(),
            ]
        ],
        'error' => [
            'status' => 'error',
            'code' => 400,
            'data' => [
                'message' => 'Bad request',
                'errors' => [
                    'field1' => ['Field is required'],
                    'field2' => ['Invalid format'],
                ]
            ]
        ],
    ],

    'cache_keys' => [
        'user_profile_1',
        'user_profile_2',
        'user_profile_3',
        'api_response_users',
        'api_response_posts',
        'config_cache',
        'session_data_abc123',
        'session_data_def456',
        'temp_data_xyz789',
    ],

    'ttl_test_scenarios' => [
        'short_ttl' => [
            'data' => 'expires_quickly',
            'ttl' => 1, // 1 second
        ],
        'medium_ttl' => [
            'data' => 'expires_medium',
            'ttl' => 60, // 1 minute
        ],
        'long_ttl' => [
            'data' => 'expires_slowly',
            'ttl' => 3600, // 1 hour
        ],
    ],

    'edge_cases' => [
        'empty_string' => '',
        'empty_array' => [],
        'zero' => 0,
        'false' => false,
        'null' => null,
        'unicode_string' => '🚀 Unicode test: 中文',
        'special_chars' => "Special chars: !@#$%^&*()",
    ],
];