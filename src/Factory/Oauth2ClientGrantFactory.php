<?php
namespace HtOauth\Server\ClientModule\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use HtOauth\Server\ClientModule\Grant\Oauth2Client;

interface Oauth2ClientGrantFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $grants)
    {
        $grants = $serviceLocator->getServiceLocator();

        return new Oauth2Client(
            $serviceLocator->get('ZfrOAuth2\Server\Service\AccessTokenService'),
            $serviceLocator->get('ZfrOAuth2\Server\Service\RefreshTokenService'),
            $serviceLocator->get('Hrevert\OauthClient\Manager\ProviderManager'),
            $serviceLocator->get('Hrevert\OauthClient\Manager\UserProviderManager'),
            $serviceLocator->get('HtLeagueOauthClientModule\Oauth2ClientManager'),
            $serviceLocator->get('ZfrOAuth2\Server\AuthorizationServer'),
            $serviceLocator->get('HtOauth\Server\ClientModule\Options\ModuleOptions'),
        );
    }
}
