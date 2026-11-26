<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Controller;

use Netresearch\NrSamlAuth\Tests\Functional\Helper\TestIdentityProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The backend module Admin Tools > SAML Auth, rendered through Extbase the
 * way the backend dispatches a module request.
 */
final class SamlAuthControllerTest extends FunctionalTestCase
{
    private const MODULE = 'tools_nrsamlauth';

    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/login.csv');
        $backendUser     = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $certificate = (new TestIdentityProvider())->getCertificate();
        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')->insert(
            'tx_nrsamlauth_domain_model_settings',
            [
                'uid'                         => 1,
                'pid'                         => 0,
                'name'                        => 'Test identity provider',
                'sp_entity_id'                => 'https://sp.example.com/',
                'sp_customer_service_url'     => 'https://sp.example.com/?logintype=login',
                'sp_customer_service_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'sp_name_id_format'           => 'NAMEID_UNSPECIFIED',
                'sp_cert'                     => $certificate,
                'idp_entity_id'               => 'https://idp.example.com',
                'idp_sso_url'                 => 'https://idp.example.com/sso',
                'idp_sso_binding'             => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'idp_logout_url'              => 'https://idp.example.com/slo',
                'idp_cert'                    => $certificate,
                'users_pid'                   => 10,
                'usergroup'                   => '1',
            ],
        );
    }

    #[Test]
    public function metadataActionRendersTheSelectionOfSettingsRecords(): void
    {
        $body = (string) $this->dispatchModule([])->getBody();

        // The field dispatchModule() posts is the one the form renders
        self::assertStringContainsString('<select name="samlUid"', $body);
        self::assertStringContainsString('Test identity provider', $body);
        self::assertStringNotContainsString('EntityDescriptor', $body);
    }

    #[Test]
    public function metadataActionRendersTheMetadataOfTheSelectedSettingsRecord(): void
    {
        $body = (string) $this->dispatchModule(['samlUid' => '1'])->getBody();

        self::assertStringContainsString('Test identity provider', $body);
        self::assertStringContainsString('EntityDescriptor', $body);
        self::assertStringContainsString('entityID=&quot;https://sp.example.com/&quot;', $body);
    }

    /**
     * @param array<string, string> $arguments Fields the form of the module posts
     */
    private function dispatchModule(array $arguments): ResponseInterface
    {
        $module = $this->get(ModuleProvider::class)->getModule(self::MODULE);
        self::assertNotNull($module);
        $request = (new ServerRequest('https://sp.example.com/typo3/module/tools/saml-auth', 'POST', null, [], [
            'HTTPS'       => 'on',
            'HTTP_HOST'   => 'sp.example.com',
            'REQUEST_URI' => '/typo3/module/tools/saml-auth',
            'SCRIPT_NAME' => '/typo3/index.php',
        ]))
            // The form of the module posts its fields without a namespace
            ->withParsedBody($arguments)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('module', $module)
            ->withAttribute('route', $this->get(Router::class)->getRoute(self::MODULE));
        $request                  = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = $this->get(Bootstrap::class)->handleBackendRequest($request);

        self::assertSame(200, $response->getStatusCode());

        return $response;
    }
}
