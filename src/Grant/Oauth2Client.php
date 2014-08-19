<?php
namespace HtOauth\Server\ClientModule\Grant;

use Hrevert\OauthClient\Manager\ProviderManagerInterface;
use League\OAuth2\Client\Token\AccessToken as ProviderAccessToken;
use Zend\Http\Request as HttpRequest;
use Zend\ServiceLocator\ServiceLocatorInterface;
use ZfrOAuth2\Server\AuthorizationServer;
use ZfrOAuth2\Server\Entity\AccessToken;
use ZfrOAuth2\Server\Entity\Client;
use ZfrOAuth2\Server\Entity\RefreshToken;
use ZfrOAuth2\Server\Entity\TokenOwnerInterface;
use ZfrOAuth2\Server\Exception\OAuth2Exception;
use ZfrOAuth2\Server\Grant\AbstractGrant;
use ZfrOAuth2\Server\Grant\RefreshTokenGrant;
use ZfrOAuth2\Server\Service\TokenService;
use HtOauth\Server\ClientModule\Options\ModuleOptions;

class Oauth2Client extends AbstractGrant
{
    /**
     * @var ProviderManagerInterface
     */
    protected $providerManager;

    /**
     * @var UserProviderManagerInterface
     */
    protected $userProviderManager;

    /**
     * @var ServiceLocatorInterface
     */
    protected $providerClients;

    /**
     * @var AuthorizationServer
     */
    protected $authorizationServer;

    /**
     * @var ModuleOptions
     */
    protected $options;

    /**
     * Constructor
     *
     * @param TokenService $accessTokenService
     * @param TokenService $refreshTokenService
     * @param ProviderManagerInterface $providerManager
     * @param UserProviderManagerInterface $userProviderManager
     * @param ServiceLocatorInterface $providerClients
     * @param AuthorizationServer $authorizationServer
     */
    public function __construct(
        TokenService $accessTokenService,
        TokenService $refreshTokenService,
        ProviderManagerInterface $providerManager,
        UserProviderManagerInterface $userProviderManager,
        ServiceLocatorInterface $providerClients,
        AuthorizationServer $authorizationServer,
        ModuleOptions $options
    )
    {
        $this->accessTokenService   = $accessTokenService;
        $this->refreshTokenService  = $refreshTokenService;
        $this->providerManager      = $providerManager;
        $this->userProviderManager  = $userProviderManager;
        $this->providerClients      = $providerClients;
        $this->authorizationServer  = $authorizationServer;
        $this->options              = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function createAuthorizationResponse(HttpRequest $request, Client $client, TokenOwnerInterface $owner = null)
    {
        throw OAuth2Exception::invalidRequest('Oauth2Client grant does not support authorization');
    }

    /**
     * {@inheritDoc}
     */    
    public function createTokenResponse(HttpRequest $request, Client $client = null, TokenOwnerInterface $owner = null)
    {
        $providerName        = $request->getPost('provider');
        $providerAccessToken = $request->getPost('provider_access_token');
        $scope               = $request->getPost('scope');
        
        if ($providerName === null) {
            throw OAuth2Exception::invalidRequest('Provider name is missing.');
        }

        if ($providerAccessToken === null) {
            throw OAuth2Exception::invalidRequest('Provider access token is missing.');
        }

        $providerAccessToken = new ProviderAccessToken(['access_token' => $providerAccessToken]);

        $provider = $this->providerManager->findByName($providerName);
        if (!$provider) {
            throw OAuth2Exception::invalidRequest(sprintf('Provider %s is not supported.', $providerName));
        }

        /** @var \League\OAuth2\Client\Provider\ProviderInterface */
        $providerClient = $this->providerClients->get($providerName);

        try{
            $userDetails = $providerClient->getUserDetails($providerAccessToken);    
        } catch(\GuzzleHttp\Exception\ClientException $e) {
            throw OAuth2Exception::invalidRequest(sprintf('Invalid Access Token!'));
        }
        
        // access token is valid

        $userProvider = $this->userProviderManager->findByProviderUid($userDetails->uid, $provider);

        if (!$userProvider) {
            // access token is valid but the user does not exists
            $createUserCallable = $this->options->getCreateUserCallable();

            /** @var \Hrevert\OauthClient\Model\UserProviderInterface */
            $userProvider = $createUserCallable($userDetails);
            $userProvider->setProviderUid($userDetails->uid);
            $userProvider->setProvider($provider);
        }

        // Everything is okey, we can start tokens generation!
        $accessToken = new AccessToken();

        /** @var TokenOwnerInterface */
        $owner = $userProvider->getUser();

        $this->populateToken($accessToken, $client, $owner, $scope);
        $accessToken = $this->accessTokenService->createToken($accessToken);

        // Before generating a refresh token, we must make sure the authorization server supports this grant
        $refreshToken = null;

        if ($this->authorizationServer->hasGrant(RefreshTokenGrant::GRANT_TYPE)) {
            $refreshToken = new RefreshToken();

            $this->populateToken($refreshToken, $client, $owner, $scope);
            $refreshToken = $this->refreshTokenService->createToken($refreshToken);
        }

        return $this->prepareTokenResponse($accessToken, $refreshToken);
    }

    /**
     * {@inheritDoc}
     */
    public function allowPublicClients()
    {
        return true;
    }
}
