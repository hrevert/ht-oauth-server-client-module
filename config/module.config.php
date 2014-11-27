<?php

return [
    'zfr_oauth2_server' => [
        'grant_manager' => [
            'factories' => [
                'HtOauth\Server\ClientModule\Grant\Oauth2Client' => 'HtOauth\Server\ClientModule\Factory\Oauth2ClientGrantFactory',
                'HtOauth\Server\ClientModule\Grant\Oauth1Client' => 'HtOauth\Server\ClientModule\Factory\Oauth1ClientGrantFactory',
            ]
        ]
    ],
    'ht_oauth_service_client' => [],
];
