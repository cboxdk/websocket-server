<?php

return [
    'domain' => null,
    'path' => '/docs',
    'middleware' => ['web'],
    'url' => '/openapi.yaml',
    'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference',

    'configuration' => [
        'theme' => 'purple',
        'layout' => 'modern',
        'proxyUrl' => null, // Disable proxy - direct requests
        'showSidebar' => true,
        'hideModels' => false,
        'hideDownloadButton' => true,
        'hideTestRequestButton' => false,
        'hideSearch' => false,
        'darkMode' => true,
        'forceDarkModeState' => 'dark',
        'hideDarkModeToggle' => false,
        'searchHotKey' => 'k',
        'metaData' => [
            'title' => 'Cbox WebSocket Server API',
        ],
        'favicon' => '/assets/cbox-icon.png',
        'hiddenClients' => [],
        'defaultHttpClient' => [
            'targetId' => 'shell',
            'clientKey' => 'curl',
        ],
        'withDefaultFonts' => true,
        'defaultOpenAllTags' => false,
    ],
];
