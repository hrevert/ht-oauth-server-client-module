<?php

namespace HtOauth\Server\ClientModuleTest\Options;

use HtOauth\Server\ClientModule\Options\ModuleOptions;

class ModuleOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testSettersAndGetters()
    {
        $createUserCallable = function () {
            return;
        };

        $options = new ModuleOptions([
            'create_user_callable' => $createUserCallable,
        ]);

        $this->assertEquals($createUserCallable, $options->getCreateUserCallable());
    }

    public function testDefaultCallbackThrowsException()
    {
        $options = new ModuleOptions([]);
        $createUserCallable = $options->getCreateUserCallable();

        $this->setExpectedException('ZfrOAuth2\Server\Exception\OAuth2Exception');
        $createUserCallable();
    }
}
