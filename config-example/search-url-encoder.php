<?php
/**
 * Copy to config/search-url-encoder.php
 *
 * The "default" profile uses the plugin settings (search path + results template).
 * Add one entry per extra search page. Each `path` must be unique.
 */
return [
    'profiles' => [
        'products' => [
            'path' => 'shop/search',
            'template' => 'shop/results',
        ],
        'blog' => [
            'path' => 'journal/search',
            'template' => 'journal/results',
        ],
    ],
];
