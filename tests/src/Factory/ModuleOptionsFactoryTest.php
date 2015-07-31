<?php

namespace HtOauth\Server\ClientModuleTest\Factory;

use HtOauth\Server\ClientModule\Factory\ModuleOptionsFactory;

class ModuleOptionsFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        $serviceLocator = $this->getMock('Zend\ServiceManager\ServiceLocatorInterface');
        $serviceLocator->expects($this->once())
            ->method('get')
            ->with('Config')
            ->will($this->returnValue(['ht_oauth_service_client' => []]));

        $factory = new ModuleOptionsFactory();
        $this->assertInstanceOf('HtOauth\Server\ClientModule\Options\ModuleOptions', $factory->createService($serviceLocator));
    }
}
