<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Service;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use OneLogin\Saml2\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use ReflectionMethod;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SamlServiceTest extends UnitTestCase
{
    private SamlService $subject;

    private SettingsRepository&Stub $settingsRepository;

    private ExtensionConfiguration&Stub $extensionConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsRepository = self::createStub(SettingsRepository::class);
        $this->extensionConfiguration = self::createStub(ExtensionConfiguration::class);
        $this->subject = new SamlService($this->settingsRepository, $this->extensionConfiguration);
    }

    #[Test]
    public function setSettingsUidSetsUid(): void
    {
        $this->subject->setSettingsUid(42);

        // Verify by checking that getSettings doesn't throw when settings are not found
        $settings = $this->subject->getSettings();
        self::assertIsArray($settings);
    }

    #[Test]
    public function getSettingsReturnsDefaultStructure(): void
    {
        $settings = $this->subject->getSettings();

        self::assertArrayHasKey('username_prefix', $settings);
        self::assertArrayHasKey('users_pid', $settings);
        self::assertArrayHasKey('usergroup', $settings);
        self::assertArrayHasKey('saml', $settings);
        self::assertArrayHasKey('sp', $settings['saml']);
        self::assertArrayHasKey('idp', $settings['saml']);
    }

    #[Test]
    public function getSettingsBuildsFromDatabaseSettings(): void
    {
        $settingsModel = $this->createMock(Settings::class);
        $settingsModel->method('getSpEntityId')->willReturn('https://sp.example.com');
        $settingsModel->method('getSpCustomerServiceUrl')->willReturn('https://sp.example.com/acs');
        $settingsModel->method('getSpCustomerServiceBinding')->willReturn('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST');
        $settingsModel->method('getSpNameIdFormat')->willReturn('NAMEID_UNSPECIFIED');
        $settingsModel->method('getSpCert')->willReturn('-----BEGIN CERTIFICATE-----');
        $settingsModel->method('getSpKey')->willReturn('-----BEGIN PRIVATE KEY-----');
        $settingsModel->method('getIdpEntityId')->willReturn('https://idp.example.com');
        $settingsModel->method('getIdpSsoUrl')->willReturn('https://idp.example.com/sso');
        $settingsModel->method('getIdpLogoutUrl')->willReturn('https://idp.example.com/logout');
        $settingsModel->method('getIdpSsoBinding')->willReturn('urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
        $settingsModel->method('getIdpCert')->willReturn('-----BEGIN CERTIFICATE-----IDP');
        $settingsModel->method('getUsernamePrefix')->willReturn('saml_');
        $settingsModel->method('getUsersPid')->willReturn(123);
        $settingsModel->method('getUsergroup')->willReturn('1,2,3');

        $this->settingsRepository->method('findByUid')->willReturn($settingsModel);

        $this->subject->setSettingsUid(1);
        $settings = $this->subject->getSettings();

        self::assertSame('https://sp.example.com', $settings['saml']['sp']['entityId']);
        self::assertSame('https://idp.example.com', $settings['saml']['idp']['entityId']);
        self::assertSame('saml_', $settings['username_prefix']);
        self::assertSame(123, $settings['users_pid']);
        self::assertSame('1,2,3', $settings['usergroup']);
    }

    #[Test]
    public function getSettingsAlwaysEnablesStrictValidation(): void
    {
        self::assertTrue($this->subject->getSettings()['saml']['strict']);
    }

    #[Test]
    public function getSettingsValidatesXmlUnlessTheExtensionSettingTurnsItOff(): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->expects($this->exactly(2))
            ->method('get')
            ->with('nr_saml_auth', 'validateXml')
            ->willReturnOnConsecutiveCalls('1', '0');
        $subject = new SamlService($this->settingsRepository, $extensionConfiguration);

        self::assertTrue($subject->getSettings()['saml']['security']['wantXMLValidation']);
        self::assertFalse($subject->getSettings()['saml']['security']['wantXMLValidation']);
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function currentUrlDataProvider(): array
    {
        return [
            'no proxy prefix' => [[], 'https://sp.example.com/saml/acs'],
            'proxy prefix' => [['reverseProxyPrefix' => '/site'], 'https://sp.example.com/site/saml/acs'],
        ];
    }

    /**
     * @param array<string, string> $proxyConfiguration
     */
    #[Test]
    #[DataProvider('currentUrlDataProvider')]
    public function theUrlPhpSamlComparesWithTheDestinationIsTheUrlTypo3Sees(array $proxyConfiguration, string $expectedUrl): void
    {
        $serverParams = [
            'HTTP_HOST' => 'sp.example.com',
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
            'REQUEST_URI' => '/saml/acs?logintype=login',
            'QUERY_STRING' => 'logintype=login',
            'SCRIPT_NAME' => '/index.php',
            'REMOTE_ADDR' => '10.0.0.1',
        ];
        $systemConfiguration = $proxyConfiguration === [] ? [] : $proxyConfiguration + [
            'reverseProxyIP' => '10.0.0.1',
            'reverseProxyHeaderMultiValue' => 'none',
        ];
        $request = (new ServerRequest('https://sp.example.com/saml/acs?logintype=login', 'POST', null, [], $serverParams))
            ->withAttribute('normalizedParams', NormalizedParams::createFromServerParams($serverParams, $systemConfiguration));

        // php-saml reads the path from the server variables
        $serverBackup = $_SERVER;
        $_SERVER = array_replace($_SERVER, $serverParams);
        try {
            (new ReflectionMethod($this->subject, 'useRequestAsCurrentUrl'))->invoke($this->subject, $request);
            $url = Utils::getSelfRoutedURLNoQuery();
        } finally {
            $_SERVER = $serverBackup;
            Utils::setBaseURL('');
        }

        self::assertSame($expectedUrl, $url);
    }

    #[Test]
    public function nameIdFormatItemsPopulatesItems(): void
    {
        $parameters = ['items' => []];

        $this->subject->nameIdFormatItems($parameters);

        self::assertNotEmpty($parameters['items']);
        self::assertIsArray($parameters['items']);

        // Check that items contain NAMEID constants
        $foundUnspecified = false;
        foreach ($parameters['items'] as $item) {
            if (str_contains($item[0], 'unspecified')) {
                $foundUnspecified = true;
                break;
            }
        }

        self::assertTrue($foundUnspecified, 'Should contain NAMEID_UNSPECIFIED constant');
    }
}
