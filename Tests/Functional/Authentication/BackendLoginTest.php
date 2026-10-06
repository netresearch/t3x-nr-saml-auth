<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Authentication;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A backend login with username and password, with the SAML authentication
 * service registered for the backend: the password decides.
 */
final class BackendLoginTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    protected array $configurationToUseInTestInstance = [
        'BE' => [
            'loginRateLimit' => 0,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/login.csv');
        $passwordHash = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE')
            ->getHashedPassword('correct-password');
        $this->getConnectionPool()->getConnectionForTable('be_users')
            ->update('be_users', ['password' => $passwordHash], ['username' => 'admin']);
    }

    #[Test]
    public function wrongPasswordIsRejected(): void
    {
        $backendUser = $this->loginWithPassword('admin', 'wrong-password');

        self::assertNull($backendUser->user['uid'] ?? null);
    }

    #[Test]
    public function correctPasswordLogsIn(): void
    {
        $backendUser = $this->loginWithPassword('admin', 'correct-password');

        self::assertSame(1, (int)($backendUser->user['uid'] ?? 0));
    }

    private function loginWithPassword(string $username, string $password): BackendUserAuthentication
    {
        $request = (new ServerRequest('https://sp.example.com/typo3/login', 'POST'))
            ->withParsedBody([
                'login_status' => 'login',
                'username' => $username,
                'userident' => $password,
            ])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        SecurityAspect::provideIn(GeneralUtility::makeInstance(Context::class))
            ->setReceivedRequestToken(RequestToken::create('core/user-auth/be'));

        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendUser->start($request);

        return $backendUser;
    }
}
