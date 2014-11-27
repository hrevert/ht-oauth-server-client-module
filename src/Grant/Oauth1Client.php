<?php
namespace HtOauth\Server\ClientModule\Grant;

use Zend\Http\Request as HttpRequest;
use ZfrOAuth2\Server\Exception\OAuth2Exception;
use Hrevert\OauthClient\Model\ProviderInterface;
use League\OAuth1\Client\Credentials\TokenCredentials;
use HtLeagueOauthClientModule\Model\Oauth1User;

class Oauth1Client extends AbstractOauthClientGrant
{
    const GRANT_TYPE          = 'oauth1_client';
    const GRANT_RESPONSE_TYPE = null;

    /**
     * {@inheritdoc}
     */
    protected function findProviderUserFromRequest(HttpRequest $request, ProviderInterface $provider)
    {
        $tokenIdentifier  = $request->getPost('token_identifier');
        $tokenSecret      = $request->getPost('token_secret');

        $tokenCredentials = new TokenCredentials;
        $tokenCredentials->setIdentifier($tokenIdentifier);
        $tokenCredentials->setSecret($tokenSecret);

        /** @var \League\OAuth1\Client\Server\Server $providerClient */
        $providerClient = $this->providerClients->get($provider->getName());

        try{
            $user = $providerClient->getUserDetails($tokenCredentials);
        } catch (\Exception $e) {
            throw OAuth2Exception::invalidRequest('Token identifier and token secret are invalid');
        }


        return new Oauth1User($user);
    }
}
