HtOauth\Server\ClientModule
======================
A Zend Framework 2 module which provides custom grant for [zfr-oauth2-server](https://github.com/zf-fr/zfr-oauth2-server) to authenticate users via third party applications like facebook, google etc.

[![Master Branch Build Status](https://api.travis-ci.org/hrevert/ht-oauth-server-client-module.png?branch=master)](http://travis-ci.org/hrevert/ht-oauth-server-client-module)
[![Latest Stable Version](https://poser.pugx.org/hrevert/ht-oauth-server-client-module/version.svg)](https://packagist.org/packages/hrevert/ht-oauth-server-client-module) 
[![Latest Unstable Version](https://poser.pugx.org/hrevert/ht-oauth-server-client-module/v/unstable.svg)](//packagist.org/packages/hrevert/ht-oauth-server-client-module) [![Total Downloads](https://poser.pugx.org/hrevert/ht-oauth-server-client-module/downloads.svg)](https://packagist.org/packages/hrevert/ht-oauth-server-client-module)

### What's with the name?
The module provides a grant for a oauth2 server and it is also a client for oauth2 servers of facebook, google etc. So, it is named as server as well as client.

## Installation
* Add `"hrevert/ht-oauth-server-client-module": "0.3.*"` to composer.json and run `php composer.phar update`.
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
User class must implement `Hrevert\OauthClient\Model\UserInterface`. Then, you need to modify the Doctrine mapping to associate this interface with your own user class.

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
You need to define the credentials like client id, client secret and other configuration. Read [this](https://github.com/hrevert/HtLeagueOauthClientModule/tree/0.2.0) for these configuration.

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
        'create_user_callable' => function(\HtLeagueOauthClientModule\Model\UserInterface $userDetails) {
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

## How It Works
### Login with OAuth 2.0
1. **Client:** Client sends a `POST` request to the server at */oauth/token* with oauth2 authorization code or access token.
2. **Server:** With  *authorization code*, *authorization code* is exchanged for *provider access token*.
3. **Server:** User information is retrived using the *provider access token* from **Step 2**.
4. **Server:** Look up the user by the unique *provider id*. If user already exists, grab 
the existing user, otherwise create a new user account.
5. **Server:** Reply with a *new access token*.


