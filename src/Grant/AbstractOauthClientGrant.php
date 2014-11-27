<?php
namespace HtOauth\Server\ClientModule\Grant;

use Hrevert\OauthClient\Manager\ProviderManagerInterface;
use Hrevert\OauthClient\Manager\UserProviderManagerInterface;
use Zend\Http\Request as HttpRequest;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfrOAuth2\Server\Entity\AccessToken;
use ZfrOAuth2\Server\Entity\Client;
use ZfrOAuth2\Server\Entity\RefreshToken;
use ZfrOAuth2\Server\Entity\TokenOwnerInterface;
use ZfrOAuth2\Server\Exception\OAuth2Exception;
use ZfrOAuth2\Server\Grant\AbstractGrant;
use ZfrOAuth2\Server\Grant\RefreshTokenGrant;
use ZfrOAuth2\Server\Service\TokenService;
use HtOauth\Server\ClientModule\Options\ModuleOptions;
use ZfrOAuth2\Server\Grant\AuthorizationServerAwareInterface;
use ZfrOAuth2\Server\Grant\AuthorizationServerAwareTrait;
use Hrevert\OauthClient\Entity\UserProvider;
use Hrevert\OauthClient\Model\UserInterface;
use Hrevert\OauthClient\Model\ProviderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use HtLeagueOauthClientModule\Model\UserInterface as ProviderUser;

abstract class AbstractOauthClientGrant extends AbstractGrant implements AuthorizationServerAwareInterface
{
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
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param TokenService $accessTokenService
     * @param TokenService $refreshTokenService
     * @param ProviderManagerInterface $providerManager
     * @param UserProviderManagerInterface $userProviderManager
     * @param ServiceLocatorInterface $providerClients
     * @param ModuleOptions $options
     * @param ObjectManager $objectManager
     */
    public function __construct(
        TokenService $accessTokenService,
        TokenService $refreshTokenService,
        ProviderManagerInterface $providerManager,
        UserProviderManagerInterface $userProviderManager,
        ServiceLocatorInterface $providerClients,
        ModuleOptions $options,
        ObjectManager $objectManager
    )
    {
        $this->accessTokenService   = $accessTokenService;
        $this->refreshTokenService  = $refreshTokenService;
        $this->providerManager      = $providerManager;
        $this->userProviderManager  = $userProviderManager;
        $this->providerClients      = $providerClients;
        $this->options              = $options;
        $this->objectManager        = $objectManager;
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
        $providerName   = $request->getPost('provider');
        $scope          = $request->getPost('scope');

        if ($providerName === null) {
            throw OAuth2Exception::invalidRequest('Provider name is missing.');
        }

        $provider = $this->providerManager->findByName($providerName);
        if (!$provider || !$this->providerClients->has($providerName)) {
            throw OAuth2Exception::invalidRequest(sprintf('Provider %s is not supported', $providerName));
        }

        $providerUser = $this->findProviderUserFromRequest($request, $provider);

        // access token is valid
        $userProvider = $this->userProviderManager->findByProviderUid($providerUser->getId(), $provider);

        if (!$userProvider) {
            // access token is valid but the user does not exists
            $createUserCallable = $this->options->getCreateUserCallable();

            // by default, we expect the callable to return instance of "Hrevert\OauthClient\Model\UserProviderInterface"
            // because the developer may have extended the default implementation
            // Alternatively the callable may return user entity directly
            $userProvider = $createUserCallable($providerUser);
            if ($userProvider instanceof UserInterface) {
                $user = $userProvider;
                $userProvider = new UserProvider;
                $userProvider->setUser($user);
            }

            $userProvider->setProviderUid($providerUser->getId());
            $userProvider->setProvider($provider);

            $this->objectManager->persist($userProvider);
            $this->objectManager->flush();
        }

        /** @var TokenOwnerInterface $owner */
        $owner = $userProvider->getUser();

        // Everything is okay, we can start tokens generation!
        $accessToken = new AccessToken();

        $this->populateToken($accessToken, $client, $owner, $scope);
        /** @var AccessToken $accessToken */
        $accessToken = $this->accessTokenService->createToken($accessToken);

        // Before generating a refresh token, we must make sure the authorization server supports this grant
        $refreshToken = null;

        if ($this->authorizationServer->hasGrant(RefreshTokenGrant::GRANT_TYPE)) {
            $refreshToken = new RefreshToken();

            $this->populateToken($refreshToken, $client, $owner, $scope);
            /** @var RefreshToken $refreshToken */
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

    /**
     * @param HttpRequest $request
     * @param ProviderInterface $provider
     * @return ProviderUser
     */
    abstract protected function findProviderUserFromRequest(HttpRequest $request, ProviderInterface $provider);
}
