<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Authentication;

use Netresearch\NrSamlAuth\Tests\Functional\Helper\SamlResponseBuilder;
use Netresearch\NrSamlAuth\Tests\Functional\Helper\TestIdentityProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
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

    private const SAML_USERS_PID = 10;

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
    }

    #[Test]
    public function validResponseLogsInAndCreatesTheUserInTheStorageFolder(): void
    {
        $response = $this->postSamlResponse($this->identityProvider->sign($this->validResponse()));

        self::assertSame(200, $response->getStatusCode());
        $user = $this->findUser('jdoe', self::SAML_USERS_PID);
        self::assertIsArray($user, 'A frontend user is created in the storage folder of the settings record');
        self::assertSame('1', (string)$user['usergroup']);
        self::assertSame([(int)$user['uid']], $this->loggedInUserIds());
    }

    #[Test]
    public function responseIsAcceptedOnce(): void
    {
        $samlResponse = $this->identityProvider->sign(
            $this->validResponse()->withAttribute('username', 'localuser')
        );
        $this->postSamlResponse($samlResponse);
        self::assertCount(1, $this->loggedInUserIds());
        $this->getConnectionPool()->getConnectionForTable('fe_sessions')->truncate('fe_sessions');

        $this->postSamlResponse($samlResponse);
        $this->postSamlResponse($samlResponse, ['user' => 'localuser', 'pass' => 'wrong-password']);

        self::assertSame([], $this->loggedInUserIds());
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
            'other audience' => $this->validResponse()->withAudience('https://other-sp.example.org/'),
            'other issuer' => $this->validResponse()->withIssuer('https://other-idp.example.org'),
            'other destination' => $this->validResponse()->withDestination('https://other-sp.example.org/?logintype=login'),
            'expired' => $this->validResponse()->expired(),
            'not yet valid' => $this->validResponse()->notYetValid(),
            default => self::fail('Unknown variant ' . $variant),
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
            'other audience' => ['other audience'],
            'other issuer' => ['other issuer'],
            'other destination' => ['other destination'],
            'expired' => ['expired'],
            'not yet valid' => ['not yet valid'],
        ];
    }

    #[Test]
    public function responseThatIsNotSamlIsRejectedWithoutError(): void
    {
        $response = $this->postSamlResponse(base64_encode('not a SAML response'));

        self::assertSame(200, $response->getStatusCode());
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
        self::assertSame([(int)$samlUser['uid']], $this->loggedInUserIds());
    }

    #[Test]
    public function usernamePrefixOfTheSettingsRecordIsApplied(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')
            ->update('tx_nrsamlauth_domain_model_settings', ['username_prefix' => 'sso-'], ['uid' => 1]);

        $this->postSamlResponse($this->identityProvider->sign($this->validResponse()));

        $user = $this->findUser('sso-jdoe', self::SAML_USERS_PID);
        self::assertIsArray($user);
        self::assertSame([(int)$user['uid']], $this->loggedInUserIds());
    }

    private function validResponse(): SamlResponseBuilder
    {
        return (new SamlResponseBuilder())
            ->withIssuer(self::IDP_ENTITY_ID)
            ->withAudience(self::SP_ENTITY_ID)
            ->withDestination(self::ACS_URL)
            ->withAttributes([
                'username' => 'jdoe',
                'mail' => 'jdoe@example.com',
            ]);
    }

    /**
     * @param array<string, string> $additionalFields
     */
    private function postSamlResponse(string $samlResponse, array $additionalFields = []): ResponseInterface
    {
        $request = (new InternalRequest(self::ACS_URL))
            ->withMethod('POST')
            ->withParsedBody(['SAMLResponse' => $samlResponse] + $additionalFields);

        return $this->executeFrontendSubRequest($request);
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

        return array_values(array_filter(array_map(static fn(array $row): int => (int)$row['ses_userid'], $rows)));
    }

    private function insertSettings(string $idpCertificate): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')->insert(
            'tx_nrsamlauth_domain_model_settings',
            [
                'uid' => 1,
                'pid' => 0,
                'name' => 'Test identity provider',
                'sp_entity_id' => self::SP_ENTITY_ID,
                'sp_customer_service_url' => self::ACS_URL,
                'sp_customer_service_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'sp_name_id_format' => 'NAMEID_UNSPECIFIED',
                'idp_entity_id' => self::IDP_ENTITY_ID,
                'idp_sso_url' => 'https://idp.example.com/sso',
                'idp_sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'idp_cert' => $idpCertificate,
                'users_pid' => self::SAML_USERS_PID,
                'usergroup' => '1',
            ]
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
    }
}
