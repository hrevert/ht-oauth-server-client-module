<?php

namespace HtOauth\Server\ClientModule\Grant;

use Psr\Http\Message\ServerRequestInterface;
use ZfrOAuth2\Server\Exception\OAuth2Exception;
use Hrevert\OauthClient\Model\ProviderInterface;
use League\OAuth1\Client\Credentials\TokenCredentials;
use HtLeagueOauthClientModule\Model\Oauth1User;

class Oauth1Client extends AbstractOauthClientGrant
{
    const GRANT_TYPE = 'oauth1_client';
    const GRANT_RESPONSE_TYPE = null;

    /**
     * {@inheritdoc}
     */
    protected function findProviderUserFromRequest(ServerRequestInterface $request, ProviderInterface $provider)
    {
        $postParams = $request->getParsedBody();
        $tokenIdentifier = isset($postParams['token_identifier']) ? $postParams['token_identifier'] : null;
        $tokenSecret = isset($postParams['token_secret']) ? $postParams['token_secret'] : null;

        $tokenCredentials = new TokenCredentials();
        $tokenCredentials->setIdentifier($tokenIdentifier);
        $tokenCredentials->setSecret($tokenSecret);

        /** @var \League\OAuth1\Client\Server\Server $providerClient */
        $providerClient = $this->providerClients->get($provider->getName());

        try {
            $user = $providerClient->getUserDetails($tokenCredentials);
        } catch (\Exception $e) {
            throw OAuth2Exception::invalidRequest('Token identifier and token secret are invalid');
        }

        return new Oauth1User($user);
    }
}
