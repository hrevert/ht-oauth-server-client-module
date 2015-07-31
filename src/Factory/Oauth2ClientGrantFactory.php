<?php

namespace HtOauth\Server\ClientModule\Factory;

class Oauth2ClientGrantFactory extends AbstractOauthClientGrantFactory
{
    protected function getGrantClass()
    {
        return 'HtOauth\Server\ClientModule\Grant\Oauth2Client';
    }
}
