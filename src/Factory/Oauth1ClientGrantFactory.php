<?php
namespace HtOauth\Server\ClientModule\Factory;

class Oauth1ClientGrantFactory extends AbstractOauthClientGrantFactory
{
    protected function getGrantClass()
    {
        return 'HtOauth\Server\ClientModule\Grant\Oauth1Client';
    }
}
