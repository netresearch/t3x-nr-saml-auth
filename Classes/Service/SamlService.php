<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Service;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Metadata;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Settings as SamlSettings;
use OneLogin\Saml2\Utils;
use OneLogin\Saml2\ValidationError;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionException;
use Throwable;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Service for handling SAML operations including SSO authentication
 * and single logout (SLO).
 */
final class SamlService implements SingletonInterface
{
    /**
     * Default settings array structure
     */
    private array $settings = [
        'username_prefix' => '',
        'users_pid' => 0,
        'usergroup' => '',
        'debug' => true,
        'saml' => [
            // Strict mode makes php-saml check the destination, audience, issuer,
            // validity period and subject confirmation of a response, not only its
            // signature. It is not configurable.
            'strict' => true,
            'debug' => false,
            'security' => [
                'wantXMLValidation' => true,
            ],
            'sp' => [
                'entityId' => '',
                'assertionConsumerService' => [
                    'url' => '',
                    'binding' => '',
                ],
                'NameIDFormat' => '',
                'x509cert' => '',
                'privateKey' => '',
            ],
            'idp' => [
                'entityId' => '',
                'singleSignOnService' => [
                    'url' => '',
                    'binding' => '',
                ],
                'x509cert' => '',
            ],
        ],
    ];

    private int $settingsUid = 0;

    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function setSettingsUid(int $uid): void
    {
        $this->settingsUid = $uid;
    }

    /**
     * Redirects the user to SSO Service
     *
     * @throws Error
     */
    public function redirectUserToSSO(?ServerRequestInterface $request = null): void
    {
        Utils::redirect($this->getSsoRedirectUrl($request));
    }

    /**
     * Returns the URL of the IdP's SSO service with the AuthnRequest.
     *
     * The RelayState is the current URL without its query string, with scheme,
     * host and port as TYPO3 sees them, the same view the deep-link middleware
     * checks a RelayState against.
     *
     * @throws Error
     */
    public function getSsoRedirectUrl(?ServerRequestInterface $request = null): string
    {
        $auth = new Auth($this->getSettings()['saml']);
        $this->useRequestAsCurrentUrl($request);

        return (string)$auth->login(null, [], false, false, true);
    }

    /**
     * The data single logout needs from the login response: the uid of the
     * settings record, the NameID with its Format and qualifiers, and the
     * SessionIndex of the AuthnStatement.
     *
     * The SessionIndex is the one the identity provider gave the session, not
     * the ID of the assertion (SAML 2.0 Core, sections 2.7.2 and 3.7.1). The
     * NameID is sent back with the attributes it was issued with: the
     * LogoutRequest names the principal by "the identifier and associated
     * attributes" as both providers recognise it (section 3.7.1).
     *
     * A NameID without Format has the format unspecified (section 2.2.2).
     * It is stored as such: for a missing format php-saml would put the
     * NameID format of the service provider into the LogoutRequest, and it
     * leaves the attribute out for unspecified.
     *
     * @return array{id: int, nameId: string|null, nameIdFormat: string, nameIdNameQualifier: string|null, nameIdSPNameQualifier: string|null, sessionIndex: string|null}
     *
     * @throws Error
     * @throws ValidationError
     */
    public function getSingleLogoutData(Response $response): array
    {
        $nameIdData = $response->getNameIdData();

        return [
            'id' => $this->settingsUid,
            'nameId' => $this->stringOrNull($nameIdData['Value'] ?? null),
            'nameIdFormat' => $this->stringOrNull($nameIdData['Format'] ?? null) ?? Constants::NAMEID_UNSPECIFIED,
            'nameIdNameQualifier' => $this->stringOrNull($nameIdData['NameQualifier'] ?? null),
            'nameIdSPNameQualifier' => $this->stringOrNull($nameIdData['SPNameQualifier'] ?? null),
            'sessionIndex' => $this->stringOrNull($response->getSessionIndex()),
        ];
    }

    /**
     * Redirects the user to the single logout service of the identity
     * provider, with the data getSingleLogoutData() returned at login. Does
     * nothing without such data.
     *
     * @param array<string, mixed> $singleLogoutData
     *
     * @throws Error
     */
    public function redirectToSingleLogout(array $singleLogoutData): void
    {
        $url = $this->getLogoutRedirectUrl($singleLogoutData);
        if ($url !== null) {
            Utils::redirect($url);
        }
    }

    /**
     * Redirects user to SAML logout (SLO)
     *
     * $sessionIndex is the SessionIndex attribute of the AuthnStatement of
     * the login, not the ID of the assertion; it is escaped for XML. The
     * NameID is sent with the NameID format of the service provider;
     * redirectToSingleLogout() sends it with the attributes of the login.
     *
     * @throws Error
     */
    public function redirectUserToLogout(?string $nameId, ?string $sessionIndex): void
    {
        $samlSettings = $this->getSettings()['saml'];
        $settings = new SamlSettings($samlSettings);

        $auth = new Auth($samlSettings);
        $auth->logout(
            null,
            [],
            $nameId,
            $this->escapeSessionIndex($sessionIndex),
            false,
            $settings->getSpData()['NameIDFormat']
        );
    }

    /**
     * Returns the URL of the identity provider's single logout service with
     * the LogoutRequest, or null without the data getSingleLogoutData()
     * returned at login.
     *
     * @param array<string, mixed> $singleLogoutData
     *
     * @throws Error
     */
    public function getLogoutRedirectUrl(array $singleLogoutData): ?string
    {
        $settingsUid = $singleLogoutData['id'] ?? null;
        if (!is_int($settingsUid) || $settingsUid <= 0) {
            return null;
        }

        $this->setSettingsUid($settingsUid);
        $auth = new Auth($this->getSettings()['saml']);

        $sessionIndex = $this->escapeSessionIndex($this->stringOrNull($singleLogoutData['sessionIndex'] ?? null));

        return (string)$auth->logout(
            null,
            [],
            $this->stringOrNull($singleLogoutData['nameId'] ?? null),
            $sessionIndex,
            true,
            $this->stringOrNull($singleLogoutData['nameIdFormat'] ?? null),
            $this->stringOrNull($singleLogoutData['nameIdNameQualifier'] ?? null),
            $this->stringOrNull($singleLogoutData['nameIdSPNameQualifier'] ?? null),
        );
    }

    /**
     * php-saml puts the SessionIndex into the XML of the LogoutRequest as it
     * is. Unlike an assertion ID it may hold any string.
     */
    private function escapeSessionIndex(?string $sessionIndex): ?string
    {
        return $sessionIndex === null ? null : htmlspecialchars($sessionIndex, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Returns the SAML Response from POST data
     *
     * php-saml compares the response's Destination and Recipient with the URL
     * the response was received at. That URL is taken from the request as
     * TYPO3 normalised it, so the reverse proxy configuration of TYPO3 applies.
     *
     * @throws Error
     * @throws ValidationError
     */
    public function getResponse(string $postSamlResponse, ?ServerRequestInterface $request = null): Response
    {
        $settings = new SamlSettings($this->getSettings()['saml']);
        $this->useRequestAsCurrentUrl($request);

        return new Response($settings, $postSamlResponse);
    }

    private function useRequestAsCurrentUrl(?ServerRequestInterface $request): void
    {
        // Without TYPO3's view of the request, php-saml falls back to the
        // server variables; values from an earlier call are dropped.
        $normalizedParams = $request?->getAttribute('normalizedParams');
        if (!$request instanceof ServerRequestInterface || !$normalizedParams instanceof NormalizedParams) {
            Utils::setBaseURL('');
            return;
        }

        $isHttps = $normalizedParams->isHttps();
        $port = $normalizedParams->getRequestPort();

        Utils::setSelfProtocol($isHttps ? 'https' : 'http');
        Utils::setSelfHost($normalizedParams->getRequestHostOnly());
        if ($port <= 0) {
            $port = $isHttps ? 443 : 80;
        }

        Utils::setSelfPort($port);

        // php-saml takes the path from the server's REQUEST_URI. TYPO3 puts the
        // reverse proxy prefix in front of it; php-saml adds it as base path.
        $publicPath = (string)parse_url($normalizedParams->getRequestUri(), PHP_URL_PATH);
        $serverPath = (string)parse_url((string)($request->getServerParams()['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $prefix = '';
        if ($serverPath !== '' && strlen($publicPath) > strlen($serverPath) && str_ends_with($publicPath, $serverPath)) {
            $prefix = substr($publicPath, 0, -strlen($serverPath));
        }

        Utils::setBaseURLPath($prefix);
    }

    /**
     * Returns possible NameFormat values for TCA itemsProcFunc
     *
     * @throws ReflectionException
     */
    public function nameIdFormatItems(array &$parameters): void
    {
        $items = [];
        $reflectionClass = new ReflectionClass(Constants::class);

        foreach ($reflectionClass->getConstants() as $name => $value) {
            if (!str_starts_with($name, 'NAMEID_')) {
                continue;
            }

            $items[] = [$value, $name, null];
        }

        $parameters['items'] = $items;
    }

    /**
     * Returns the SAML metadata as XML string
     *
     * @throws Error
     */
    public function getMetadata(): string
    {
        $settings = new SamlSettings($this->getSettings()['saml']);
        $validUntil = time() + 60 * 60 * 24 * 14;

        $metadata = Metadata::builder($settings->getSPData(), true, true, $validUntil);

        return Metadata::addX509KeyDescriptors(
            $metadata,
            $this->getSettings()['saml']['sp']['x509cert']
        );
    }

    /**
     * Returns the complete settings array
     */
    public function getSettings(): array
    {
        $this->buildSettings();

        return $this->settings;
    }

    /**
     * Builds the SAML settings from database configuration
     */
    private function buildSettings(): void
    {
        $this->settings['saml']['security']['wantXMLValidation'] = $this->isXmlValidationEnabled();

        $settingsModel = $this->fetchSettings();
        if (!$settingsModel instanceof Settings) {
            return;
        }

        $this->settings['saml']['sp']['entityId'] = $settingsModel->getSpEntityId();
        $this->settings['saml']['sp']['assertionConsumerService']['url'] = $settingsModel->getSpCustomerServiceUrl();
        $this->settings['saml']['sp']['assertionConsumerService']['binding'] = $settingsModel->getSpCustomerServiceBinding();
        $this->settings['saml']['sp']['NameIDFormat'] = constant(Constants::class . '::' . $settingsModel->getSpNameIdFormat());
        $this->settings['saml']['sp']['x509cert'] = $settingsModel->getSpCert();
        $this->settings['saml']['sp']['privateKey'] = $settingsModel->getSpKey();

        $this->settings['saml']['idp']['entityId'] = $settingsModel->getIdpEntityId();
        $this->settings['saml']['idp']['singleSignOnService']['url'] = $settingsModel->getIdpSsoUrl();
        $this->settings['saml']['idp']['singleLogoutService']['url'] = $settingsModel->getIdpLogoutUrl();
        $this->settings['saml']['idp']['singleSignOnService']['binding'] = $settingsModel->getIdpSsoBinding();
        $this->settings['saml']['idp']['x509cert'] = $settingsModel->getIdpCert();

        $this->settings['username_prefix'] = $settingsModel->getUsernamePrefix();
        $this->settings['users_pid'] = $settingsModel->getUsersPid();
        $this->settings['usergroup'] = $settingsModel->getUsergroup();
    }

    /**
     * Schema validation of responses, extension setting `validateXml` (default on)
     */
    private function isXmlValidationEnabled(): bool
    {
        try {
            $value = $this->extensionConfiguration->get('nr_saml_auth', 'validateXml');
        } catch (Throwable) {
            return true;
        }

        return $value === null || $value === '' || (bool)$value;
    }

    /**
     * Fetches settings from repository
     */
    private function fetchSettings(): ?Settings
    {
        $settings = $this->settingsRepository->findByUid($this->settingsUid);

        return $settings instanceof Settings ? $settings : null;
    }
}
