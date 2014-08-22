HtOauth\Server\ClientModule
======================
A Zend Framework 2 module which provides custom grant for [zfr-oauth2-server](https://github.com/zf-fr/zfr-oauth2-server) to authenticate users via third party applications like facebook, google etc.

## Installation
* Add `"hrevert/ht-oauth-server-client-module": "dev-master"` to composer.json and run `php composer.phar update`.
* Enabled the following modules in `config/application.config.php`.
```php
'modules' => array(
    'ZfrOAuth2Module\Server',
    'HtLeagueOauthClientModule',
    'HtOauthClientModule', 
    'HtOauth\Server\ClientModule',
),
```

## Configuring the module
#### Setting the User class
User class implements the ZfrOAuth2\Server\Entity\TokenOwnerInterface interface `Hrevert\OauthClient\Model\UserInterface`. Then, you need to modify the Doctrine mapping to associate this interface with your own user class.

```php
return [
    'doctrine' => [
        'entity_resolver' => [
            'orm_default' => [
                'Hrevert\OauthClient\Model\UserInterface' => 'Application\Entity\User'
            ]
        ]
    ]
]
```

#### Provider configuration
You need to define the credentials like client id, client secret and other configuration. Read [this](https://github.com/hrevert/HtLeagueOauthClientModule/tree/0.0.1) for these configuration.

#### Adding grant types
```php
return [
    'zfr_oauth2_server' => [
        'grants' => [
            // .. other grants,
            'HtOauth\Server\ClientModule\Grant\Oauth2Client',
        ]
    ]
]
```

#### Enabling providers
Enable providers by adding records to the table `oauth_provider`.

#### Autocreating user
When a new user tries to log in, s/he is not allowed to log in by default.

To automatically create a new user, you need to specify a callable for creating a user.

```php

return [
    'ht_oauth_service_client' => [
        'create_user_callable' => function(\League\OAuth2\Client\Entity\User $userDetails) {
            $user = ......;
            
            $userProvider = new \Hrevert\OauthClient\Entity\UserProvider();
            $userProvider->setUser($user);
            
            return $userProvider; 

            // or just

            $user = ......;

            return $user;
        }
    ]
];
```
