<?php

namespace HtOauth\Server\ClientModule\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractOauthClientGrantFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $grants)
    {
        $serviceLocator = $grants->getServiceLocator();

        $class = $this->getGrantClass();

        return new $class(
            $serviceLocator->get('ZfrOAuth2\Server\Service\AccessTokenService'),
            $serviceLocator->get('ZfrOAuth2\Server\Service\RefreshTokenService'),
            $serviceLocator->get('Hrevert\OauthClient\Manager\ProviderManager'),
            $serviceLocator->get('Hrevert\OauthClient\Manager\UserProviderManager'),
            $serviceLocator->get('HtLeagueOauthClientModule\Oauth2ClientManager'),
            $serviceLocator->get('HtOauth\Server\ClientModule\Options\ModuleOptions'),
            $serviceLocator->get('ht_oauth_client_doctrine_em')
        );
    }

    abstract protected function getGrantClass();
}
