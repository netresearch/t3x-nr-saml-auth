<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Authentication;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\AuthnRequestCookie;
use Netresearch\NrSamlAuth\Service\SamlService;
use Netresearch\NrSamlAuth\Tests\Functional\Helper\SamlResponseBuilder;
use Netresearch\NrSamlAuth\Tests\Functional\Helper\TestIdentityProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Session\UserSession;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Frontend login with a SAML response, sent through the TYPO3 frontend
 * application like a browser posting the response of the identity provider
 * to the Assertion Consumer Service URL.
 */
final class SamlLoginTest extends FunctionalTestCase
{
    private const SP_ENTITY_ID = 'https://sp.example.com/';

    private const ACS_URL = 'https://sp.example.com/?logintype=login';

    private const IDP_ENTITY_ID = 'https://idp.example.com';

    private const IDP_LOGOUT_URL = 'https://idp.example.com/slo';

    private const NAMEID_EMAIL = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';

    private const NAME_ID = 'jdoe@idp.example.com';

    private const XPATH_SESSION_INDEX = '/samlp:LogoutRequest/samlp:SessionIndex';

    private const XPATH_NAME_ID = '/samlp:LogoutRequest/saml:NameID';

    private const SAML_USERS_PID = 10;

    private const REQUEST_COOKIE = 'nr_saml_auth_request';

    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    /**
     * The identity provider posts its response without a TYPO3 request token.
     * TYPO3 12.4 and later fetch users for such a request only with this
     * option, the setup for a site that sends visitors without a session to
     * the identity provider. The login rate limit is off because the tests
     * send many rejected logins from one address. `checkFeUserPid` is off so
     * that TYPO3's own username and password lookup finds users in any folder.
     */
    protected array $configurationToUseInTestInstance = [
        // An installation that still carries the removed strictMode setting
        // switched off: strict validation stays on (see the rejection tests).
        'EXTENSIONS' => [
            'nr_saml_auth' => [
                'strictMode' => '0',
            ],
        ],
        'FE' => [
            'loginRateLimit' => 0,
            'checkFeUserPid' => false,
        ],
        'SVCONF' => [
            'auth' => [
                'setup' => [
                    'FE_fetchUserIfNoSession' => true,
                ],
            ],
        ],
    ];

    private TestIdentityProvider $identityProvider;

    /**
     * ID of the AuthnRequest of the login setUp() starts, and the cookie
     * that remembers it. validResponse() answers this request and
     * postSamlResponse() sends this cookie unless a test says otherwise.
     */
    private string $requestId;

    private string $requestCookie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/login.csv');
        $this->setUpFrontendRootPage(1, ['EXT:nr_saml_auth/Tests/Functional/Fixtures/Frontend/page.typoscript']);
        $this->writeSiteConfiguration();

        $passwordHash = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('FE')
            ->getHashedPassword('correct-password');
        $this->getConnectionPool()->getConnectionForTable('fe_users')
            ->update('fe_users', ['password' => $passwordHash], ['username' => 'localuser']);

        $this->identityProvider = new TestIdentityProvider();
        $this->insertSettings($this->identityProvider->getCertificate());

        [$this->requestId, $this->requestCookie] = $this->startLogin();
    }

    #[Test]
    public function validResponseLogsInAndCreatesTheUserInTheStorageFolder(): void
    {
        $response = $this->postSamlResponse($this->identityProvider->sign($this->validResponse()));

        self::assertSame(200, $response->getStatusCode());
        $user = $this->findUser('jdoe', self::SAML_USERS_PID);
        self::assertIsArray($user, 'A frontend user is created in the storage folder of the settings record');
        self::assertSame('1', (string) $user['usergroup']);
        self::assertSame([(int) $user['uid']], $this->loggedInUserIds());
    }

    /**
     * A response that answers no AuthnRequest (IdP-initiated login) could be
     * posted from any browser, for example by an attacker who logs a victim
     * into the attacker's account (login CSRF). It is rejected unless the
     * settings record allows IdP-initiated login.
     */
    #[Test]
    public function unsolicitedResponseIsRejectedByDefault(): void
    {
        $this->postSamlResponse($this->identityProvider->sign($this->validResponse()->withInResponseTo(null)));

        self::assertSame([], $this->loggedInUserIds());
        self::assertNull($this->findUser('jdoe', self::SAML_USERS_PID));
    }

    #[Test]
    public function unsolicitedResponseIsAcceptedWhenTheSettingsRecordAllowsIdpInitiatedLogin(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')
            ->update('tx_nrsamlauth_domain_model_settings', ['allow_idp_initiated' => 1], ['uid' => 1]);

        $this->postSamlResponse(
            $this->identityProvider->sign($this->validResponse()->withInResponseTo(null)),
            [],
            self::ACS_URL,
            false,
            [],
        );

        $user = $this->findUser('jdoe', self::SAML_USERS_PID);
        self::assertIsArray($user);
        self::assertSame([(int) $user['uid']], $this->loggedInUserIds());
    }

    /**
     * Allowing IdP-initiated login does not let a response through that
     * answers an AuthnRequest this browser did not start.
     */
    #[Test]
    public function responseToAnotherAuthnRequestIsRejectedAlsoWhenIdpInitiatedLoginIsAllowed(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')
            ->update('tx_nrsamlauth_domain_model_settings', ['allow_idp_initiated' => 1], ['uid' => 1]);

        $this->postSamlResponse($this->identityProvider->sign(
            $this->validResponse()->withInResponseTo('_attacker_request'),
        ));

        self::assertSame([], $this->loggedInUserIds());
    }

    /**
     * The browser starts two logins, for example in two tabs, and the
     * response to the first arrives: the user is logged in, and the cookie
     * the response sets forgets the answered request and keeps the other.
     */
    #[Test]
    public function responseToAnAuthnRequestOfThisBrowserLogsInAndTheRequestIsForgotten(): void
    {
        [$secondRequestId, $cookie] = $this->startLogin(1, [self::REQUEST_COOKIE => $this->requestCookie]);
        self::assertNotSame($this->requestId, $secondRequestId);

        $response = $this->postSamlResponse(
            $this->identityProvider->sign($this->validResponse()),
            ['RelayState' => '/some/page'],
            self::ACS_URL,
            false,
            [self::REQUEST_COOKIE => $cookie],
        );

        self::assertSame(303, $response->getStatusCode());
        self::assertCount(1, $this->loggedInUserIds());

        $setCookie = null;
        foreach ($response->getHeader('Set-Cookie') as $header) {
            if (str_starts_with($header, self::REQUEST_COOKIE . '=')) {
                $setCookie = Cookie::fromString($header);
            }
        }
        self::assertInstanceOf(Cookie::class, $setCookie, 'The response updates the cookie');
        self::assertSame(
            [$secondRequestId],
            (new AuthnRequestCookie())->getPendingRequestIds(
                (new ServerRequest())->withCookieParams([self::REQUEST_COOKIE => (string) $setCookie->getValue()]),
                1,
            ),
        );
    }

    #[Test]
    public function startingALoginRemembersTheAuthnRequestInACookieThatTheCrossSitePostCarries(): void
    {
        $samlService = $this->get(SamlService::class);
        $samlService->setSettingsUid(1);

        $redirect = $samlService->createSsoRedirect($this->loginPageRequest([]));
        $cookie   = $redirect['cookie'];

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame(self::REQUEST_COOKIE, $cookie->getName());
        self::assertSame(Cookie::SAMESITE_NONE, $cookie->getSameSite());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('/', $cookie->getPath());
        self::assertLessThanOrEqual(AuthnRequestCookie::LIFETIME, $cookie->getMaxAge());
        self::assertGreaterThan(AuthnRequestCookie::LIFETIME - 60, $cookie->getMaxAge());
        self::assertSame(
            [$this->authnRequestIdOf($redirect['url'])],
            (new AuthnRequestCookie())->getPendingRequestIds(
                (new ServerRequest())->withCookieParams([self::REQUEST_COOKIE => (string) $cookie->getValue()]),
                1,
            ),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function cookiesWithoutTheAnsweredRequestDataProvider(): array
    {
        return [
            'no cookie'                         => ['no cookie'],
            'cookie that is not signed'         => ['not signed'],
            'cookie of another request'         => ['other request'],
            'cookie with changed request'       => ['tampered'],
            'cookie that expired'               => ['expired'],
            'cookie of another settings record' => ['other settings record'],
        ];
    }

    /**
     * A response that answers an AuthnRequest this browser did not start
     * for this settings record within the lifetime of the cookie is rejected.
     */
    #[Test]
    #[DataProvider('cookiesWithoutTheAnsweredRequestDataProvider')]
    public function responseToAnAuthnRequestThisBrowserDidNotStartIsRejected(string $variant): void
    {
        $requestId = '_attacker_request';
        $cookies   = match ($variant) {
            'no cookie'             => [],
            'not signed'            => [self::REQUEST_COOKIE => $requestId],
            'other request'         => [self::REQUEST_COOKIE => $this->requestCookie],
            'tampered'              => [self::REQUEST_COOKIE => $this->withPayloadOf($this->requestCookie, [[1, $requestId, time() + 600]])],
            'expired'               => [self::REQUEST_COOKIE => $this->startLogin(1, [], time() - AuthnRequestCookie::LIFETIME - 1)[1]],
            'other settings record' => [self::REQUEST_COOKIE => $this->startLogin($this->copySettingsRecord())[1]],
            default                 => self::fail('Unknown variant ' . $variant),
        };
        if (in_array($variant, ['expired', 'other settings record'], true)) {
            // The response answers the request of that cookie
            $requestId = $this->pendingRequestIdIn($cookies[self::REQUEST_COOKIE], $variant === 'expired');
        }

        $this->postSamlResponse(
            $this->identityProvider->sign($this->validResponse()->withInResponseTo($requestId)),
            [],
            self::ACS_URL . '&saml_id=1',
            false,
            $cookies,
        );

        self::assertSame([], $this->loggedInUserIds());
        self::assertNull($this->findUser('jdoe', self::SAML_USERS_PID));
    }

    #[Test]
    public function responseIsAcceptedOnce(): void
    {
        $samlResponse = $this->identityProvider->sign(
            $this->validResponse()->withAttribute('username', 'localuser'),
        );
        $this->postSamlResponse($samlResponse);
        self::assertCount(1, $this->loggedInUserIds());
        $this->getConnectionPool()->getConnectionForTable('fe_sessions')->truncate('fe_sessions');

        $this->postSamlResponse($samlResponse);
        $this->postSamlResponse($samlResponse, ['user' => 'localuser', 'pass' => 'wrong-password']);

        self::assertSame([], $this->loggedInUserIds());
    }

    /**
     * @return array<string, array{0: array<string, int>}>
     */
    public static function inactiveUserDataProvider(): array
    {
        return [
            'disabled'                 => [['disable' => 1]],
            'start time in the future' => [['starttime' => time() + 3600]],
            'end time passed'          => [['endtime' => time() - 3600]],
        ];
    }

    /**
     * @param array<string, int> $fields
     */
    #[Test]
    #[DataProvider('inactiveUserDataProvider')]
    public function inactiveUserIsNotLoggedInAndNotCreatedAgain(array $fields): void
    {
        $this->getConnectionPool()->getConnectionForTable('fe_users')
            ->update('fe_users', $fields, ['username' => 'localuser']);

        $this->postSamlResponse($this->identityProvider->sign(
            $this->validResponse()->withAttribute('username', 'localuser'),
        ));

        self::assertSame([], $this->loggedInUserIds());

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('fe_users');
        $queryBuilder->getRestrictions()->removeAll();
        $count = $queryBuilder->count('uid')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter('localuser')),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(self::SAML_USERS_PID, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
        self::assertSame(1, (int) $count, 'No second user with the same name is created');
    }

    /**
     * Databases with a case-insensitive collation (MariaDB, MySQL) find the
     * existing user; SQLite creates a second one. Either way exactly the
     * user getUser() resolved is logged in.
     */
    #[Test]
    public function usernameInOtherLetterCaseLogsInTheUserTheDatabaseFinds(): void
    {
        $this->postSamlResponse($this->identityProvider->sign(
            $this->validResponse()->withAttribute('username', 'LocalUser'),
        ));

        $loggedIn = $this->loggedInUserIds();
        self::assertCount(1, $loggedIn);
        $user = $this->getConnectionPool()->getConnectionForTable('fe_users')
            ->select(['username', 'pid'], 'fe_users', ['uid' => $loggedIn[0]])
            ->fetchAssociative();
        self::assertIsArray($user);
        self::assertSame('localuser', strtolower((string) $user['username']));
        self::assertSame(self::SAML_USERS_PID, (int) $user['pid']);
    }

    #[Test]
    public function responseSignedWithAnotherKeyIsRejected(): void
    {
        $otherIdentityProvider = new TestIdentityProvider();

        $this->postSamlResponse($otherIdentityProvider->sign($this->validResponse()));

        self::assertSame([], $this->loggedInUserIds());
        self::assertNull($this->findUser('jdoe', self::SAML_USERS_PID));
    }

    /**
     * A response the identity provider signed for another service provider,
     * another endpoint, or a time that has passed is rejected.
     */
    #[Test]
    #[DataProvider('responsesNotMeantForThisLoginDataProvider')]
    public function responseNotMeantForThisLoginIsRejected(string $variant): void
    {
        $builder = match ($variant) {
            'other audience'    => $this->validResponse()->withAudience('https://other-sp.example.org/'),
            'other issuer'      => $this->validResponse()->withIssuer('https://other-idp.example.org'),
            'other destination' => $this->validResponse()->withDestination('https://other-sp.example.org/?logintype=login'),
            'expired'           => $this->validResponse()->expired(),
            'not yet valid'     => $this->validResponse()->notYetValid(),
            default             => self::fail('Unknown variant ' . $variant),
        };

        $this->postSamlResponse($this->identityProvider->sign($builder));

        self::assertSame([], $this->loggedInUserIds());
        self::assertNull($this->findUser('jdoe', self::SAML_USERS_PID));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function responsesNotMeantForThisLoginDataProvider(): array
    {
        return [
            'other audience'    => ['other audience'],
            'other issuer'      => ['other issuer'],
            'other destination' => ['other destination'],
            'expired'           => ['expired'],
            'not yet valid'     => ['not yet valid'],
        ];
    }

    #[Test]
    public function responseThatIsNotSamlIsRejectedWithoutError(): void
    {
        $response = $this->postSamlResponse(base64_encode('not a SAML response'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $this->loggedInUserIds());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function undecodableResponseDataProvider(): array
    {
        return [
            'not base64' => ['!!!!'],
            'blank'      => [' '],
        ];
    }

    #[Test]
    #[DataProvider('undecodableResponseDataProvider')]
    public function responseThatDecodesToNothingIsRejectedWithoutError(string $samlResponse): void
    {
        $response = $this->postSamlResponse($samlResponse);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $this->loggedInUserIds());
    }

    /**
     * Both settings records allow IdP-initiated login, so that only the
     * replay protection can reject the second post.
     */
    #[Test]
    public function responseIsAcceptedOnceAcrossSettingsRecordsOfTheSameIdentityProvider(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings');
        $connection->update('tx_nrsamlauth_domain_model_settings', ['allow_idp_initiated' => 1], ['uid' => 1]);
        $this->copySettingsRecord();

        $samlResponse = $this->identityProvider->sign($this->validResponse()->withInResponseTo(null));
        $this->postSamlResponse($samlResponse, [], self::ACS_URL . '&saml_id=1');
        self::assertCount(1, $this->loggedInUserIds());
        $this->getConnectionPool()->getConnectionForTable('fe_sessions')->truncate('fe_sessions');

        $this->postSamlResponse($samlResponse, [], self::ACS_URL . '&saml_id=2');

        self::assertSame([], $this->loggedInUserIds());
    }

    #[Test]
    public function responseWithoutUsernameIsRejected(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withIssuer(self::IDP_ENTITY_ID)
            ->withAudience(self::SP_ENTITY_ID)
            ->withDestination(self::ACS_URL)
            ->withAttribute('mail', 'jdoe@example.com');

        $this->postSamlResponse($this->identityProvider->sign($builder));

        self::assertSame([], $this->loggedInUserIds());
        self::assertNull($this->findUser('', self::SAML_USERS_PID));
    }

    #[Test]
    public function rejectedResponseDoesNotLetUsernameAndPasswordThrough(): void
    {
        $otherIdentityProvider = new TestIdentityProvider();

        $this->postSamlResponse(
            $otherIdentityProvider->sign($this->validResponse()),
            ['user' => 'localuser', 'pass' => 'wrong-password'],
        );

        self::assertSame([], $this->loggedInUserIds());
    }

    #[Test]
    public function userWithTheSameNameInAnotherFolderIsNotLoggedIn(): void
    {
        $otherFolderUser = $this->findUser('jdoe', 20);
        self::assertIsArray($otherFolderUser);

        $this->postSamlResponse($this->identityProvider->sign($this->validResponse()));

        $samlUser = $this->findUser('jdoe', self::SAML_USERS_PID);
        self::assertIsArray($samlUser);
        self::assertSame([(int) $samlUser['uid']], $this->loggedInUserIds());
    }

    #[Test]
    public function usernamePrefixOfTheSettingsRecordIsApplied(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')
            ->update('tx_nrsamlauth_domain_model_settings', ['username_prefix' => 'sso-'], ['uid' => 1]);

        $this->postSamlResponse($this->identityProvider->sign($this->validResponse()));

        $user = $this->findUser('sso-jdoe', self::SAML_USERS_PID);
        self::assertIsArray($user);
        self::assertSame([(int) $user['uid']], $this->loggedInUserIds());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function relayStatesOnThisHostDataProvider(): array
    {
        return [
            'path'                      => ['/some/page?a=1', '/some/page?a=1'],
            'absolute URL on this host' => ['https://sp.example.com/some/page', 'https://sp.example.com/some/page'],
            'host in other letter case' => ['https://SP.example.com/some/page', 'https://SP.example.com/some/page'],
        ];
    }

    #[Test]
    #[DataProvider('relayStatesOnThisHostDataProvider')]
    public function relayStateOnThisHostRedirectsAfterLogin(string $relayState, string $expectedLocation): void
    {
        $response = $this->postSamlResponse(
            $this->identityProvider->sign($this->validResponse()),
            ['RelayState' => $relayState],
        );

        self::assertSame(303, $response->getStatusCode());
        self::assertSame($expectedLocation, $response->getHeaderLine('Location'));
        self::assertStringContainsString('fe_typo_user=', $response->getHeaderLine('Set-Cookie'));
        self::assertCount(1, $this->loggedInUserIds());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function relayStatesOffThisHostDataProvider(): array
    {
        return [
            'other host'                     => ['https://elsewhere.example.org/'],
            'host as prefix of another host' => ['https://sp.example.com.example.org/'],
            'scheme-relative URL'            => ['//elsewhere.example.org/'],
            'backslash after slash'          => ['/\\elsewhere.example.org/'],
            'other scheme on this host'      => ['http://sp.example.com/'],
            'other port on this host'        => ['https://sp.example.com:8443/'],
            'credentials in URL'             => ['https://user@sp.example.com/'],
            'script URL'                     => ['javascript:alert(1)'],
            'relative path without slash'    => ['elsewhere.example.org/'],
        ];
    }

    #[Test]
    #[DataProvider('relayStatesOffThisHostDataProvider')]
    public function relayStateOffThisHostIsNotFollowed(string $relayState): void
    {
        $response = $this->postSamlResponse(
            $this->identityProvider->sign($this->validResponse()),
            ['RelayState' => $relayState],
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Location'));
        self::assertCount(1, $this->loggedInUserIds());
    }

    /**
     * The SessionIndex is the one of the AuthnStatement, which the identity
     * provider chooses and which need not be the ID of the assertion.
     */
    #[Test]
    public function loginStoresTheDataSingleLogoutNeedsInTheFrontendSession(): void
    {
        $samlResponse = $this->identityProvider->sign(
            $this->validResponse()
                ->withNameId(self::NAME_ID, self::NAMEID_EMAIL)
                ->withSessionIndex('_idp_session_4711'),
        );

        $this->postSamlResponse($samlResponse);

        self::assertCount(1, $this->loggedInUserIds());
        self::assertNotSame('_idp_session_4711', $this->assertionIdOf($samlResponse));
        self::assertSame(
            [
                'id'                    => 1,
                'nameId'                => self::NAME_ID,
                'nameIdFormat'          => self::NAMEID_EMAIL,
                'nameIdNameQualifier'   => null,
                'nameIdSPNameQualifier' => null,
                'sessionIndex'          => '_idp_session_4711',
            ],
            $this->storedSingleLogoutData(),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function sessionIndexDataProvider(): array
    {
        return [
            'identifier'             => ['_idp_session_4711'],
            'characters XML escapes' => ['session <1> & "2"'],
        ];
    }

    /**
     * The LogoutRequest names the session by the SessionIndex of the login
     * and the principal by the NameID with the attributes it was issued with
     * (SAML 2.0 Core, sections 3.7.1 and 2.7.2).
     */
    #[Test]
    #[DataProvider('sessionIndexDataProvider')]
    public function logoutRequestCarriesTheSessionIndexAndTheNameIdOfTheLogin(string $sessionIndex): void
    {
        $samlResponse = $this->identityProvider->sign(
            $this->validResponse()
                ->withNameId(self::NAME_ID, self::NAMEID_EMAIL)
                ->withNameIdQualifiers(self::IDP_ENTITY_ID, self::SP_ENTITY_ID)
                ->withSessionIndex($sessionIndex),
        );
        $this->postSamlResponse($samlResponse);

        $logoutRequest = $this->logoutRequestFor($this->storedSingleLogoutData());

        $sessionIndexes = $logoutRequest->query(self::XPATH_SESSION_INDEX);
        self::assertNotFalse($sessionIndexes);
        self::assertCount(1, $sessionIndexes);
        self::assertSame($sessionIndex, $sessionIndexes->item(0)?->textContent);
        self::assertNotSame($this->assertionIdOf($samlResponse), $sessionIndexes->item(0)?->textContent);

        $nameId = $logoutRequest->query(self::XPATH_NAME_ID)?->item(0);
        self::assertInstanceOf(DOMElement::class, $nameId);
        self::assertSame(self::NAME_ID, $nameId->textContent);
        self::assertSame(self::NAMEID_EMAIL, $nameId->getAttribute('Format'));
        self::assertSame(self::IDP_ENTITY_ID, $nameId->getAttribute('NameQualifier'));
        self::assertSame(self::SP_ENTITY_ID, $nameId->getAttribute('SPNameQualifier'));
    }

    /**
     * A NameID issued without Format goes back without Format, also when the
     * settings record configures another NameID format for the service
     * provider.
     */
    #[Test]
    public function logoutRequestNameIdHasNoFormatWhenTheLoginNameIdHadNone(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')
            ->update('tx_nrsamlauth_domain_model_settings', ['sp_name_id_format' => 'NAMEID_EMAIL_ADDRESS'], ['uid' => 1]);
        $this->postSamlResponse($this->identityProvider->sign(
            $this->validResponse()->withNameId(self::NAME_ID)->withNameIdFormat(null),
        ));

        $nameId = $this->logoutRequestFor($this->storedSingleLogoutData())
            ->query(self::XPATH_NAME_ID)?->item(0);

        self::assertInstanceOf(DOMElement::class, $nameId);
        self::assertSame(self::NAME_ID, $nameId->textContent);
        self::assertFalse($nameId->hasAttribute('Format'));
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function missingSessionIndexDataProvider(): array
    {
        return [
            'no attribute'    => [null],
            'empty attribute' => [''],
        ];
    }

    /**
     * The SessionIndex is optional. Without one the LogoutRequest names only
     * the principal, and the identity provider terminates the session that
     * NameID specifies (SAML 2.0 Core, section 3.7.3.2).
     */
    #[Test]
    #[DataProvider('missingSessionIndexDataProvider')]
    public function logoutRequestWithoutSessionIndexOfTheLoginNamesOnlyThePrincipal(?string $sessionIndex): void
    {
        $this->postSamlResponse($this->identityProvider->sign(
            $this->validResponse()->withNameId(self::NAME_ID)->withSessionIndex($sessionIndex),
        ));

        $singleLogoutData = $this->storedSingleLogoutData();
        self::assertIsArray($singleLogoutData);
        self::assertNull($singleLogoutData['sessionIndex']);

        $logoutRequest = $this->logoutRequestFor($singleLogoutData);

        self::assertSame(0, $logoutRequest->query(self::XPATH_SESSION_INDEX)?->length);
        self::assertSame(
            self::NAME_ID,
            $logoutRequest->query(self::XPATH_NAME_ID)?->item(0)?->textContent,
        );
    }

    #[Test]
    public function noLogoutRequestWithoutSingleLogoutData(): void
    {
        $samlService = $this->get(SamlService::class);

        self::assertNull($samlService->getLogoutRedirectUrl([]));
        self::assertNull($samlService->getLogoutRedirectUrl(['id' => 0, 'nameId' => self::NAME_ID]));
    }

    #[Test]
    public function rejectedResponseStoresNoSingleLogoutData(): void
    {
        $otherIdentityProvider = new TestIdentityProvider();

        $this->postSamlResponse($otherIdentityProvider->sign($this->validResponse()));

        self::assertSame([], $this->loggedInUserIds());
        self::assertNull($this->storedSingleLogoutData());
    }

    #[Test]
    public function responseOutsideALoginRequestStoresNoSingleLogoutData(): void
    {
        $this->postSamlResponse(
            $this->identityProvider->sign($this->validResponse()->withDestination('https://sp.example.com/')),
            [],
            'https://sp.example.com/',
            true,
        );

        self::assertCount(1, $this->loggedInUserIds());
        self::assertNull($this->storedSingleLogoutData());
    }

    #[Test]
    public function responseThatIsNotValidStoresNoSingleLogoutDataInAnExistingSession(): void
    {
        $otherIdentityProvider = new TestIdentityProvider();

        $this->postSamlResponse(
            $otherIdentityProvider->sign($this->validResponse()),
            [],
            self::ACS_URL,
            true,
        );

        self::assertNull($this->storedSingleLogoutData());
    }

    /**
     * A response whose assertion was used before is rejected. On TYPO3 12.4
     * a user who is logged in stays logged in; the rejected response does
     * not replace the data in that user's session.
     */
    #[Test]
    public function assertionUsedBeforeStoresNoSingleLogoutDataInAnExistingSession(): void
    {
        $samlResponse = $this->identityProvider->sign($this->validResponse()->withNameId(self::NAME_ID));
        $this->postSamlResponse($samlResponse);
        self::assertNotNull($this->storedSingleLogoutData());
        $this->getConnectionPool()->getConnectionForTable('fe_sessions')->truncate('fe_sessions');

        $this->postSamlResponse($samlResponse, [], self::ACS_URL, true);

        self::assertNull($this->storedSingleLogoutData());
    }

    /**
     * The LogoutRequest in the URL SamlService sends the user to on logout.
     *
     * @param array<string, mixed>|null $singleLogoutData
     */
    private function logoutRequestFor(?array $singleLogoutData): DOMXPath
    {
        self::assertIsArray($singleLogoutData);
        $url = $this->get(SamlService::class)->getLogoutRedirectUrl($singleLogoutData);
        self::assertIsString($url);
        self::assertStringStartsWith(self::IDP_LOGOUT_URL . '?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertIsString($query['SAMLRequest'] ?? null);
        $decoded = base64_decode($query['SAMLRequest'], true);
        self::assertIsString($decoded);
        $xml = gzinflate($decoded);
        self::assertIsString($xml);

        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml), 'The LogoutRequest is well-formed XML');
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

        return $xpath;
    }

    private function assertionIdOf(string $samlResponse): string
    {
        $xml = base64_decode($samlResponse, true);
        self::assertIsString($xml);
        self::assertSame(1, preg_match('/<saml:Assertion[^>]*\sID="([^"]+)"/', $xml, $matches));

        return $matches[1];
    }

    /**
     * The data of the session that is logged in, or null if the session
     * holds none from this extension.
     *
     * @return array<string, mixed>|null
     */
    private function storedSingleLogoutData(): ?array
    {
        $record = $this->getConnectionPool()->getConnectionForTable('fe_sessions')
            ->select(['*'], 'fe_sessions', [])
            ->fetchAssociative();
        if (!is_array($record)) {
            return null;
        }

        $data = UserSession::createFromRecord((string) $record['ses_id'], $record)->get('NrSamlAuth');

        return is_array($data) ? $data : null;
    }

    /**
     * A response to the AuthnRequest of the login setUp() started.
     */
    private function validResponse(): SamlResponseBuilder
    {
        return (new SamlResponseBuilder())
            ->withInResponseTo($this->requestId)
            ->withIssuer(self::IDP_ENTITY_ID)
            ->withAudience(self::SP_ENTITY_ID)
            ->withDestination(self::ACS_URL)
            ->withAttributes([
                'username' => 'jdoe',
                'mail'     => 'jdoe@example.com',
            ]);
    }

    /**
     * @param array<string, string>      $additionalFields
     * @param array<string, string>|null $cookies          null: the cookie of the login setUp() started
     */
    private function postSamlResponse(
        string $samlResponse,
        array $additionalFields = [],
        string $url = self::ACS_URL,
        bool $asLocalUser = false,
        ?array $cookies = null,
    ): ResponseInterface {
        $request = (new InternalRequest($url))
            ->withMethod('POST')
            ->withParsedBody(['SAMLResponse' => $samlResponse] + $additionalFields)
            ->withCookieParams($cookies ?? [self::REQUEST_COOKIE => $this->requestCookie]);

        return $this->executeFrontendSubRequest(
            $request,
            $asLocalUser ? (new InternalRequestContext())->withFrontendUserId(2) : null,
        );
    }

    /**
     * Starts an SP-initiated login for the settings record the way the
     * extension redirects a visitor to the identity provider. Returns the ID
     * of the AuthnRequest in the redirect URL and the value of the cookie
     * that remembers it; $now mints the cookie at another time.
     *
     * @param array<string, string> $cookies Cookies the browser already has
     *
     * @return array{0: string, 1: string}
     */
    private function startLogin(int $settingsUid = 1, array $cookies = [], ?int $now = null): array
    {
        $samlService = $now === null
            ? $this->get(SamlService::class)
            : new SamlService(
                $this->get(SettingsRepository::class),
                GeneralUtility::makeInstance(ExtensionConfiguration::class),
                new AuthnRequestCookie($now),
            );
        $samlService->setSettingsUid($settingsUid);

        $redirect = $samlService->createSsoRedirect($this->loginPageRequest($cookies));
        self::assertInstanceOf(Cookie::class, $redirect['cookie']);

        return [$this->authnRequestIdOf($redirect['url']), (string) $redirect['cookie']->getValue()];
    }

    /**
     * @param array<string, string> $cookies
     */
    private function loginPageRequest(array $cookies): ServerRequest
    {
        $request = (new ServerRequest('https://sp.example.com/login', 'GET', null, [], [
            'HTTPS'       => 'on',
            'HTTP_HOST'   => 'sp.example.com',
            'SERVER_PORT' => '443',
            'REQUEST_URI' => '/login',
            'SCRIPT_NAME' => '/index.php',
        ]))->withCookieParams($cookies);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    /**
     * The ID of the AuthnRequest in the URL of the identity provider's SSO
     * service.
     */
    private function authnRequestIdOf(string $url): string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertIsString($query['SAMLRequest'] ?? null);
        $decoded = base64_decode($query['SAMLRequest'], true);
        self::assertIsString($decoded);
        $xml = gzinflate($decoded);
        self::assertIsString($xml);
        self::assertSame(1, preg_match('/<samlp:AuthnRequest[^>]*\sID="([^"]+)"/', $xml, $matches));

        return $matches[1];
    }

    /**
     * The request ID in a cookie value, read without checking its HMAC or,
     * with $ignoreExpiry, its expiry time.
     */
    private function pendingRequestIdIn(string $cookieValue, bool $ignoreExpiry): string
    {
        [$payload] = explode('.', $cookieValue);
        $entries   = json_decode((string) base64_decode(strtr($payload, '-_', '+/'), true), true);
        self::assertIsArray($entries);
        self::assertCount(1, $entries);
        self::assertIsArray($entries[0]);
        self::assertIsString($entries[0][1]);
        if (!$ignoreExpiry) {
            self::assertGreaterThan(time(), $entries[0][2]);
        }

        return $entries[0][1];
    }

    /**
     * The cookie value with another payload and the HMAC of the original.
     *
     * @param list<array{0: int, 1: string, 2: int}> $entries
     */
    private function withPayloadOf(string $cookieValue, array $entries): string
    {
        [, $hmac] = explode('.', $cookieValue);

        return rtrim(strtr(base64_encode((string) json_encode($entries)), '+/', '-_'), '=') . '.' . $hmac;
    }

    /**
     * Copies settings record 1 to a new record and returns its uid.
     */
    private function copySettingsRecord(): int
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings');
        $record     = $connection->select(['*'], 'tx_nrsamlauth_domain_model_settings', ['uid' => 1])->fetchAssociative();
        self::assertIsArray($record);
        $connection->insert('tx_nrsamlauth_domain_model_settings', ['uid' => 2] + $record);

        return 2;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findUser(string $username, int $pid): ?array
    {
        $row = $this->getConnectionPool()->getConnectionForTable('fe_users')
            ->select(['*'], 'fe_users', ['username' => $username, 'pid' => $pid])
            ->fetchAssociative();

        return is_array($row) ? $row : null;
    }

    /**
     * @return list<int>
     */
    private function loggedInUserIds(): array
    {
        $rows = $this->getConnectionPool()->getConnectionForTable('fe_sessions')
            ->select(['ses_userid'], 'fe_sessions', [])
            ->fetchAllAssociative();

        return array_values(array_filter(array_map(static fn (array $row): int => (int) $row['ses_userid'], $rows)));
    }

    private function insertSettings(string $idpCertificate): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')->insert(
            'tx_nrsamlauth_domain_model_settings',
            [
                'uid'                         => 1,
                'pid'                         => 0,
                'name'                        => 'Test identity provider',
                'sp_entity_id'                => self::SP_ENTITY_ID,
                'sp_customer_service_url'     => self::ACS_URL,
                'sp_customer_service_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'sp_name_id_format'           => 'NAMEID_UNSPECIFIED',
                'idp_entity_id'               => self::IDP_ENTITY_ID,
                'idp_sso_url'                 => 'https://idp.example.com/sso',
                'idp_sso_binding'             => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'idp_logout_url'              => self::IDP_LOGOUT_URL,
                'idp_cert'                    => $idpCertificate,
                'users_pid'                   => self::SAML_USERS_PID,
                'usergroup'                   => '1',
            ],
        );
    }

    private function writeSiteConfiguration(): void
    {
        $directory = $this->instancePath . '/typo3conf/sites/main';
        GeneralUtility::mkdir_deep($directory);
        file_put_contents($directory . '/config.yaml', implode("\n", [
            'rootPageId: 1',
            "base: 'https://sp.example.com/'",
            'languages:',
            '  -',
            '    languageId: 0',
            "    title: 'English'",
            '    enabled: true',
            "    base: '/'",
            "    locale: 'en_US.UTF-8'",
            "    navigationTitle: 'English'",
            "    flag: 'us'",
            '',
        ]));
        file_put_contents($directory . '/settings.yaml', implode("\n", [
            'nr_saml_auth:',
            '  strictMode: false',
            '',
        ]));
    }
}
