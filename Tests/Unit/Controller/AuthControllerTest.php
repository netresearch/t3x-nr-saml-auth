<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Controller;

use Netresearch\NrSamlAuth\Controller\AuthController;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use ReflectionMethod;
use ReflectionProperty;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AuthControllerTest extends UnitTestCase
{
    /**
     * @return array<string, array{0: array<string, string>, 1: bool}>
     */
    public static function loginTypeDataProvider(): array
    {
        return [
            'logout' => [['logintype' => 'logout'], true],
            'login' => [['logintype' => 'login'], false],
            'no login type' => [[], false],
        ];
    }

    /**
     * @param array<string, string> $queryParams
     */
    #[Test]
    #[DataProvider('loginTypeDataProvider')]
    public function aRequestWithLoginTypeLogoutIsALogoutRequest(array $queryParams, bool $expected): void
    {
        $samlService = new SamlService(
            self::createStub(SettingsRepository::class),
            self::createStub(ExtensionConfiguration::class),
        );
        $subject = new AuthController($samlService, new Context(), new NullLogger());

        $serverRequest = (new ServerRequest('https://sp.example.com/', 'GET'))
            ->withQueryParams($queryParams)
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        (new ReflectionProperty(AuthController::class, 'request'))->setValue($subject, new Request($serverRequest));

        self::assertSame($expected, (new ReflectionMethod($subject, 'isLogoutRequest'))->invoke($subject));
    }
}
