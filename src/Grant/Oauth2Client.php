<?php
namespace HtOauth\Server\ClientModule\Grant;

use Hrevert\OauthClient\Manager\ProviderManagerInterface;
use Hrevert\OauthClient\Manager\UserProviderManagerInterface;
use Zend\Http\Request as HttpRequest;
use Zend\ServiceManager\ServiceLocatorInterface;
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
use League\OAuth2\Client\Exception\IDPException;
use ZfrOAuth2\Server\Grant\AuthorizationServerAwareInterface;
use ZfrOAuth2\Server\Grant\AuthorizationServerAwareTrait;
use Hrevert\OauthClient\Entity\UserProvider;
use Hrevert\OauthClient\Model\UserInterface;

class Oauth2Client extends AbstractGrant implements AuthorizationServerAwareInterface
{    
    const GRANT_TYPE          = 'oauth2_client';
    const GRANT_RESPONSE_TYPE = null;

    use AuthorizationServerAwareTrait;

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
     */
    public function __construct(
        TokenService $accessTokenService,
        TokenService $refreshTokenService,
        ProviderManagerInterface $providerManager,
        UserProviderManagerInterface $userProviderManager,
        ServiceLocatorInterface $providerClients,
        ModuleOptions $options
    )
    {
        $this->accessTokenService   = $accessTokenService;
        $this->refreshTokenService  = $refreshTokenService;
        $this->providerManager      = $providerManager;
        $this->userProviderManager  = $userProviderManager;
        $this->providerClients      = $providerClients;
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
        $providerName               = $request->getPost('provider');
        $providerAuthorizationCode  = $request->getPost('provider_authorization_code');
        $scope                      = $request->getPost('scope');
        
        if ($providerName === null) {
            throw OAuth2Exception::invalidRequest('Provider name is missing.');
        }

        if ($providerAuthorizationCode === null) {
            throw OAuth2Exception::invalidRequest('Provider access token is missing');
        }

        $provider = $this->providerManager->findByName($providerName);
        if (!$provider) {
            throw OAuth2Exception::invalidRequest(sprintf('Provider %s is not supported', $providerName));
        }

        /** @var \League\OAuth2\Client\Provider\ProviderInterface */
        $providerClient = $this->providerClients->get($providerName);

        // Try to get an access token (using the authorization code grant)
        try {
            /** @var \League\OAuth2\Client\Token\AccessToken  */
            $providerAccessToken = $providerClient->getAccessToken('authorization_code', ['code' => $providerAuthorizationCode]);            
        } catch (IDPException $e) {
            // @todo decide what is the best thing to do here???
            throw OAuth2Exception::invalidRequest(sprintf('Provider authorization code is invalid'));
        }

        /** @var \League\OAuth2\Client\Provider\User */
        $userDetails = $providerClient->getUserDetails($providerAccessToken);
        
        // access token is valid
        $userProvider = $this->userProviderManager->findByProviderUid($userDetails->uid, $provider);

        if (!$userProvider) {
            // access token is valid but the user does not exists
            $createUserCallable = $this->options->getCreateUserCallable();

            // by default, we expect the callable to return instance of "Hrevert\OauthClient\Model\UserProviderInterface"
            // because the developer may have extended the default implementation
            // Alternatively the callable may return user entity directly
            $userProvider = $createUserCallable($userDetails);
            if ($userProvider instanceof UserInterface) {
                $user = $userProvider;
                $userProvider = new UserProvider;
                $userProvider->setUser($user);
            }

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
