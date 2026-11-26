<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Controller;

use Netresearch\NrSamlAuth\Tests\Functional\Helper\TestIdentityProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The backend module "Admin Tools > SAML Auth" (tools_nrsamlauth), rendered
 * through the Extbase backend bootstrap as the backend module dispatcher
 * calls it.
 */
final class SamlAuthControllerTest extends FunctionalTestCase
{
    private const MODULE = 'tools_nrsamlauth';

    private const SP_ENTITY_ID = 'https://sp.example.com/saml/metadata';

    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/login.csv');
        $backendUser     = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->getConnectionPool()->getConnectionForTable('tx_nrsamlauth_domain_model_settings')->insert(
            'tx_nrsamlauth_domain_model_settings',
            [
                'uid'                         => 1,
                'pid'                         => 0,
                'name'                        => 'Test identity provider',
                'sp_entity_id'                => self::SP_ENTITY_ID,
                'sp_customer_service_url'     => 'https://sp.example.com/acs',
                'sp_customer_service_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'sp_name_id_format'           => 'NAMEID_UNSPECIFIED',
                'sp_cert'                     => (new TestIdentityProvider())->getCertificate(),
                'idp_entity_id'               => 'https://idp.example.com/metadata',
                'idp_sso_url'                 => 'https://idp.example.com/sso',
                'idp_sso_binding'             => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                'idp_logout_url'              => 'https://idp.example.com/slo',
                'idp_cert'                    => (new TestIdentityProvider())->getCertificate(),
                'users_pid'                   => 10,
                'usergroup'                   => '1',
            ],
        );
    }

    #[Test]
    public function metadataActionRendersTheSettingsSelectionInTheModuleTemplate(): void
    {
        $body = $this->renderModule($this->moduleRequest());

        // The document header of the backend ModuleTemplate.
        self::assertStringContainsString('module-docheader', $body);
        self::assertMatchesRegularExpression('#<select[^>]*>.*Test identity provider.*</select>#s', $body);
        self::assertStringNotContainsString('EntityDescriptor', $body);
    }

    #[Test]
    public function metadataActionShowsTheMetadataOfTheSelectedSettings(): void
    {
        $form = $this->renderModule($this->moduleRequest());
        self::assertSame(1, preg_match('#<select[^>]*name="([^"]+)"#', $form, $matches), 'The module shows the selection');

        // Submit the selection under the field name the rendered form uses.
        parse_str(rawurlencode($matches[1]) . '=1', $arguments);
        $body = $this->renderModule($this->moduleRequest()->withParsedBody($arguments)->withMethod('POST'));

        self::assertStringContainsString('module-docheader', $body);
        self::assertStringContainsString('EntityDescriptor', $body);
        self::assertStringContainsString(self::SP_ENTITY_ID, $body);
    }

    private function moduleRequest(): ServerRequestInterface
    {
        $module = $this->get(ModuleProvider::class)->getModule(self::MODULE);
        self::assertNotNull($module);

        $request = (new ServerRequest('https://sp.example.com/typo3/module/tools/saml-auth', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('module', $module)
            ->withAttribute('route', $this->get(Router::class)->getRoute(self::MODULE));

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    private function renderModule(ServerRequestInterface $request): string
    {
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response                 = $this->get(Bootstrap::class)->handleBackendRequest($request);

        self::assertSame(200, $response->getStatusCode());

        return (string) $response->getBody();
    }
}
