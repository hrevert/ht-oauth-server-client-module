<?php

return [
    'zfr_oauth2_server' => [
        'grant_manager' => [
            'factories' => [
                'oauth2_client' => 'HtOauth\Server\ClientModule\Factory\Oauth2ClientGrantFactory',
            ]
        ]
    ]
];
