<?php
namespace HtOauth\Server\ClientModule\Model;

use ZfrOAuth2\Server\Entity\TokenOwnerInterface;
use Hrevert\OauthClient\Model\UserInterface;

interface TokenOwnerProviderInterface extends UserInterface
{
    /**
     * Gets tokenOwner
     *
     * @return TokenOwnerInterface
     */
    public function getTokenOwner();
}
