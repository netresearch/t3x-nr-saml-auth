<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Service;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Metadata;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Settings as SamlSettings;
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
            'strict' => false,
            'debug' => false,
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
    ) {}

    public function setSettingsUid(int $uid): void
    {
        $this->settingsUid = $uid;
    }

    /**
     * Redirects the user to SSO Service
     *
     * @throws \OneLogin\Saml2\Error
     */
    public function redirectUserToSSO(): void
    {
        $auth = new Auth($this->getSettings()['saml']);
        $auth->login();
    }

    /**
     * Redirects user to SAML logout (SLO)
     *
     * @throws \OneLogin\Saml2\Error
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
            $sessionIndex,
            false,
            $settings->getSpData()['NameIDFormat']
        );
    }

    /**
     * Returns the SAML Response from POST data
     *
     * @throws \OneLogin\Saml2\Error
     * @throws \OneLogin\Saml2\ValidationError
     */
    public function getResponse(string $postSamlResponse): Response
    {
        $settings = new SamlSettings($this->getSettings()['saml']);

        return new Response($settings, $postSamlResponse);
    }

    /**
     * Returns possible NameFormat values for TCA itemsProcFunc
     *
     * @throws \ReflectionException
     */
    public function nameIdFormatItems(array &$parameters): void
    {
        $items = [];
        $reflectionClass = new \ReflectionClass(Constants::class);

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
     * @throws \OneLogin\Saml2\Error
     */
    public function getMetadata(): string
    {
        $settings = new SamlSettings($this->getSettings()['saml']);
        $validUntil = time() + 60 * 60 * 24 * 14;

        $metadata = Metadata::builder($settings->getSPData(), true, true, $validUntil, null);
        $metadata = Metadata::addX509KeyDescriptors(
            $metadata,
            $this->getSettings()['saml']['sp']['x509cert']
        );

        return $metadata;
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
        $settingsModel = $this->fetchSettings();
        if ($settingsModel === null) {
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
     * Fetches settings from repository
     */
    private function fetchSettings(): ?Settings
    {
        $settings = $this->settingsRepository->findByUid($this->settingsUid);

        return $settings instanceof Settings ? $settings : null;
    }
}
