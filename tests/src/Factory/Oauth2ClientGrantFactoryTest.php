<?php

namespace HtOauth\Server\ClientModuleTest\Factory;

use HtOauth\Server\ClientModule\Factory\Oauth2ClientGrantFactory;

class Oauth2ClientGrantFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $accessTokenService = $this->getMockBuilder('ZfrOAuth2\Server\Service\TokenService')
            ->disableOriginalConstructor()
            ->getMock();

        $refreshTokenService = $this->getMockBuilder('ZfrOAuth2\Server\Service\TokenService')
            ->disableOriginalConstructor()
            ->getMock();

        $providerManager = $this->getMock('Hrevert\OauthClient\Manager\ProviderManagerInterface');
        $userProviderManager = $this->getMock('Hrevert\OauthClient\Manager\UserProviderManagerInterface');
        $providerClients = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');

        $authorizationServer = $this->getMockBuilder('ZfrOAuth2\Server\AuthorizationServer')
            ->disableOriginalConstructor()
            ->getMock();

        $options = $this->getMockBuilder('HtOauth\Server\ClientModule\Options\ModuleOptions')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');

        $serviceLocator->expects($this->at(0))
            ->method('get')
            ->with('ZfrOAuth2\Server\Service\AccessTokenService')
            ->will($this->returnValue($accessTokenService));

        $serviceLocator->expects($this->at(1))
            ->method('get')
            ->with('ZfrOAuth2\Server\Service\RefreshTokenService')
            ->will($this->returnValue($refreshTokenService));

        $serviceLocator->expects($this->at(2))
            ->method('get')
            ->with('Hrevert\OauthClient\Manager\ProviderManager')
            ->will($this->returnValue($providerManager));

        $serviceLocator->expects($this->at(3))
            ->method('get')
            ->with('Hrevert\OauthClient\Manager\UserProviderManager')
            ->will($this->returnValue($userProviderManager));

        $serviceLocator->expects($this->at(4))
            ->method('get')
            ->with('HtLeagueOauthClientModule\Oauth2ClientManager')
            ->will($this->returnValue($providerClients));

        $serviceLocator->expects($this->at(5))
            ->method('get')
            ->with('HtOauth\Server\ClientModule\Options\ModuleOptions')
            ->will($this->returnValue($options));

        $serviceLocator->expects($this->at(6))
            ->method('get')
            ->with('ht_oauth_client_doctrine_em')
            ->will($this->returnValue($objectManager));

        $grants = $this->getMock('Zend\ServiceManager\AbstractPluginManager');
        $grants->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($serviceLocator));

        $factory = new Oauth2ClientGrantFactory();
        $this->assertInstanceOf('HtOauth\Server\ClientModule\Grant\Oauth2Client', $factory->createService($grants));
    }
}
