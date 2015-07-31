<?php

namespace HtOauth\Server\ClientModuleTest\Grant;

use HtOauth\Server\ClientModule\Grant\Oauth2Client;
use League\OAuth2\Client\Exception\IDPException;
use DateInterval;
use DateTime;
use ZfrOAuth2\Server\Entity\AccessToken;
use Hrevert\OauthClient\Model\UserProviderInterface;
use League\OAuth2\Client\Entity\User as ProviderUser;
use ZfrOAuth2\Server\Entity\Client;
use ZfrOAuth2\Server\Entity\RefreshToken;
use ZfrOAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Client\Token\AccessToken as ProviderAccessToken;

class Oauth2ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    protected function createOauth2ClientGrant()
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

        $grant = new Oauth2Client(
            $accessTokenService,
            $refreshTokenService,
            $providerManager,
            $userProviderManager,
            $providerClients,
            $options,
            $objectManager
        );

        $grant->setAuthorizationServer($authorizationServer);

        return [
            $grant,
            $accessTokenService,
            $refreshTokenService,
            $providerManager,
            $userProviderManager,
            $providerClients,
            $authorizationServer,
            $options,
            $objectManager,
        ];
    }

    public function testGetExceptionWhenProviderNameIsEmpty()
    {
        /** @var Oauth2Client $grant */
        $grant = $this->createOauth2ClientGrant()[0];

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');

        $this->setExpectedException('ZfrOAuth2\Server\Exception\OAuth2Exception');
        $grant->createTokenResponse($request);
    }

    public function testGetExceptionWhenProviderAuthorizationCodeIsEmpty()
    {
        /** @var Oauth2Client $grant */
        $grant = $this->createOauth2ClientGrant()[0];

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');

        $request->expects($this->at(0))
            ->method('getParsedBody')
            ->will($this->returnValue(['provider' => 'facebook']));

        $this->setExpectedException('ZfrOAuth2\Server\Exception\OAuth2Exception');
        $grant->createTokenResponse($request);
    }

    public function testGetExceptionWhenProviderDoesNotExists()
    {
        /** @var Oauth2Client $grant */
        $grant = $this->createOauth2ClientGrant()[0];

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');

        $request->expects($this->at(0))
            ->method('getParsedBody')
            ->will($this->returnValue(['provider' => 'facebook']));

        $this->setExpectedException('ZfrOAuth2\Server\Exception\OAuth2Exception');
        $grant->createTokenResponse($request);
    }

    public function testGetExceptionWhenProviderAuthorizationCodeIsInvalid()
    {
        list(
            $grant,
            $accessTokenService,
            $refreshTokenService,
            $providerManager,
            $userProviderManager,
            $providerClients,
            $authorizationServer,
            $options,
            $objectManager
        ) = $this->createOauth2ClientGrant();

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');

        $request->expects($this->exactly(2))
            ->method('getParsedBody')
            ->will($this->returnValue(['provider' => 'facebook', 'provider_authorization_code' => 'asdfasdfq3453425']));

        $provider = $this->getMock('Hrevert\OauthClient\Model\ProviderInterface');

        $providerManager->expects($this->once())
            ->method('findByName')
            ->with('facebook')
            ->will($this->returnValue($provider));

        $provider->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('facebook'));

        $providerClient = $this->getMock('League\OAuth2\Client\Provider\ProviderInterface');

        $providerClients->expects($this->once())
            ->method('has')
            ->with('facebook')
            ->will($this->returnValue(true));

        $providerClients->expects($this->once())
            ->method('get')
            ->with('facebook')
            ->will($this->returnValue($providerClient));

        $providerClient->expects($this->once())
            ->method('getAccessToken')
            ->with('authorization_code', ['code' => 'asdfasdfq3453425'])
            ->will($this->throwException(new IDPException([])));

        $this->setExpectedException('ZfrOAuth2\Server\Exception\OAuth2Exception');
        $grant->createTokenResponse($request);
    }

    public function getCreateTokenData()
    {
        return [
            [true, null, null],
            [false, $this->getMock('Hrevert\OauthClient\Model\UserProviderInterface'), 'ytdfgadsfasdfasdf'],
            [false, $this->getMock('Hrevert\OauthClient\Model\UserProviderInterface')],
        ];
    }

    /**
     * @dataProvider getCreateTokenData
     */
    public function testCanCreateTokenResponse($hasRefreshGrant, UserProviderInterface $userProvider = null, $providerAuthorizationCode = null)
    {
        list(
            $grant,
            $accessTokenService,
            $refreshTokenService,
            $providerManager,
            $userProviderManager,
            $providerClients,
            $authorizationServer,
            $options,
            $objectManager
        ) = $this->createOauth2ClientGrant();

        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');

        if ($providerAuthorizationCode) {
            $request->expects($this->exactly(2))
                ->method('getParsedBody')
                ->will($this->returnValue(['provider' => 'facebook', 'provider_authorization_code' => $providerAuthorizationCode]));
        } else {
            $request->expects($this->exactly(2))
                ->method('getParsedBody')
                ->will($this->returnValue(['provider' => 'facebook', 'provider_access_token' => '456436sdgfgsdfgsdfgsdf']));
        }

        $provider = $this->getMock('Hrevert\OauthClient\Model\ProviderInterface');

        $providerManager->expects($this->once())
            ->method('findByName')
            ->with('facebook')
            ->will($this->returnValue($provider));

        $provider->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('facebook'));

        $providerClient = $this->getMock('League\OAuth2\Client\Provider\ProviderInterface');

        $providerClients->expects($this->once())
            ->method('has')
            ->with('facebook')
            ->will($this->returnValue(true));

        $providerClients->expects($this->once())
            ->method('get')
            ->with('facebook')
            ->will($this->returnValue($providerClient));

        if ($providerAuthorizationCode) {
            $providerAccessToken = $this->getMock('League\OAuth2\Client\Token\AccessToken', [], [], '', false);
            $providerClient->expects($this->once())
                ->method('getAccessToken')
                ->with('authorization_code', ['code' => $providerAuthorizationCode])
                ->will($this->returnValue($providerAccessToken));
        } else {
            $providerAccessToken = new ProviderAccessToken(['access_token' => '456436sdgfgsdfgsdfgsdf']);
        }

        $userDetails = new ProviderUser();
        $userDetails->uid = '23423498sdf1sdf';

        $providerClient->expects($this->once())
            ->method('getUserDetails')
            ->with($providerAccessToken)
            ->will($this->returnValue($userDetails));

        $userProviderManager->expects($this->once())
            ->method('findByProviderUid')
            ->with($userDetails->uid, $provider)
            ->will($this->returnValue($userProvider));

        if (!$userProvider) {
            $userProvider = $this->getMock('Hrevert\OauthClient\Model\UserProviderInterface');
            $createUserCallable = function ($oauth2User) use ($userProvider) {
                $this->assertInstanceOf('HtLeagueOauthClientModule\Model\Oauth2User', $oauth2User);

                return $userProvider;
            };
            $options->expects($this->once())
                ->method('getCreateUserCallable')
                ->will($this->returnValue($createUserCallable));

            $userProvider->expects($this->once())
                ->method('setProviderUid')
                ->with($userDetails->uid);

            $userProvider->expects($this->once())
                ->method('setProvider')
                ->with($provider);

            $objectManager->expects($this->once())
                ->method('persist')
                ->with($userProvider);

            $objectManager->expects($this->once())
                ->method('flush');
        }

        $owner = $this->getMock('ZfrOAuth2\Server\Entity\TokenOwnerInterface');
        $owner->expects($this->once())->method('getTokenOwnerId')->will($this->returnValue(1));
        $userProvider->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($owner));

        $accessToken = $this->getValidAccessToken();
        $accessToken->setOwner($owner);
        $accessTokenService->expects($this->once())->method('createToken')->will($this->returnValue($accessToken));

        if ($hasRefreshGrant) {
            $refreshToken = $this->getValidRefreshToken();
            $refreshTokenService->expects($this->once())->method('createToken')->will($this->returnValue($refreshToken));
        }

        $authorizationServer->expects($this->once())
                            ->method('hasGrant')
                            ->with(RefreshTokenGrant::GRANT_TYPE)
                            ->will($this->returnValue($hasRefreshGrant));

        $response = $grant->createTokenResponse($request, new Client());

        $body = json_decode($response->getBody(), true);

        $this->assertEquals($accessToken->getToken(), $body['access_token']);
        $this->assertEquals('Bearer', $body['token_type']);
        $this->assertEquals(3600, $body['expires_in']);
        $this->assertEquals('read', $body['scope']);
        $this->assertEquals(1, $body['owner_id']);

        if ($hasRefreshGrant) {
            $this->assertEquals('azerty_refresh', $body['refresh_token']);
        }
    }

    /**
     * @return RefreshToken
     */
    private function getValidRefreshToken()
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setToken('azerty_refresh');
        $refreshToken->setScopes('read');
        $validDate = new DateTime();
        $validDate->add(DateInterval::createFromDateString('3600 seconds'));

        $refreshToken->setExpiresAt($validDate);

        return $refreshToken;
    }

    /**
     * @return AccessToken
     */
    private function getValidAccessToken()
    {
        $accessToken = new AccessToken();
        $accessToken->setToken('azerty_access');
        $accessToken->setScopes('read');
        $validDate = new DateTime();
        $validDate->add(new DateInterval('PT1H'));

        $accessToken->setExpiresAt($validDate);

        return $accessToken;
    }
}
