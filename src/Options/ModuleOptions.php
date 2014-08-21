<?php
namespace HtOauth\Server\ClientModule\Options;

use Zend\Stdlib\AbstractOptions;
use ZfrOAuth2\Server\Exception\OAuth2Exception;

class ModuleOptions extends AbstractOptions
{
    /**
     * @var callable
     */
    protected $createUserCallable;

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options)
    {
        $this->createUserCallable = function () {
            // By default if a new user tries to sign in, he is not allowed to sign in
            throw OAuth2Exception::accessDenied('You are not authorized to log in to the system.');
        };

        $this->setFromArray($options);
    }

    /**
     * Sets createUserCallable
     *
     * @param callable $createUserCallable
     * @return self
     */
    public function setCreateUserCallable(callable $createUserCallable)
    {
        $this->createUserCallable = $createUserCallable;

        return $this;
    }

    /**
     * Gets createUserCallable
     *
     * @return callable
     */
    public function getCreateUserCallable()
    {
        return $this->createUserCallable;
    }
}
