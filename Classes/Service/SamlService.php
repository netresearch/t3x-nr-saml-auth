<?php
declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Service;

use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Metadata;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SamlService
 *
 * @category   Category
 * @package    Netresearch\NrSamlAuth\Service
 * @subpackage SubPackage
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class SamlService implements SingletonInterface
{
    /**
     * Default Settings Array
     *
     * @var array
     */
    protected $settings = [
        'username_prefix' => '',
        'users_pid' => 0,
        'usergroup' => '',
        'debug' => true,
        'saml' => [
            // Strict mode makes php-saml check the destination, audience, issuer,
            // validity period and subject confirmation of a response, not only its
            // signature.
            'strict' => true,
            'debug' => false,
            'sp' => [
                'entityId' => '',
                'assertionConsumerService' => [
                    'url' => '',
                    'binding' => '',
                ],
                'NameIDFormat' => '',
                'x509cert' => "",
                'privateKey' => "",
            ],
            'idp' => [
                'entityId' => '',
                'singleSignOnService' => [
                    'url' => '',
                    'binding' => '',
                ],
                'x509cert' => '',
            ]
        ]
    ];

    /**
     * @var \Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var integer
     */
    private $settingsUid;

    /**
     * Inject the settings repository
     *
     * @param \Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository $settingsRepository
     *
     * @return void
     */
    public function injectSettingsRepository(\Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository $settingsRepository): void
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function setSettingsUid(int $uid): void
    {
        $this->settingsUid = $uid;
    }

    /**
     * Redirects the user to sso Service
     *
     * @return string|null
     * @throws \OneLogin\Saml2\Error
     */
    public function redirectUserToSSO()
    {
        Utils::redirect($this->getSsoRedirectUrl());
    }

    /**
     * Returns the URL of the IdP's SSO service with the AuthnRequest.
     *
     * The RelayState is the current URL as TYPO3 sees it, the same view the
     * deep-link middleware checks a RelayState against.
     *
     * @throws \OneLogin\Saml2\Error
     */
    public function getSsoRedirectUrl(): string
    {
        $auth = new \OneLogin\Saml2\Auth($this->getSettings()['saml']);
        $this->useCurrentRequestAsSelfUrl();

        return (string)$auth->login(null, [], false, false, true);
    }

    /**
     * @return string|null
     * @throws \OneLogin\Saml2\Error
     */
    public function redirectUserToLogout($nameId, $sessionIndex)
    {
        $samlSettings = $this->getSettings()['saml'];
        $settings = new Settings($samlSettings);

        $auth = new \OneLogin\Saml2\Auth($samlSettings);
        $auth->logout(
            null,
            [],
            $nameId,
            $sessionIndex,
            false ,
            $settings->getSpData()['NameIDFormat']
        );
    }

    /**
     * Returns the Saml Response from Post Data
     *
     * @param $postSamlResponse
     *
     * @return Response
     * @throws \OneLogin\Saml2\Error
     * @throws \OneLogin\Saml2\ValidationError
     */
    public function getResponse(string $postSamlResponse) : Response
    {
        $settings = new Settings($this->getSettings()['saml']);
        $this->useCurrentRequestAsSelfUrl();

        return new Response($settings, $postSamlResponse);
    }

    /**
     * php-saml compares the response's Destination and Recipient with the URL
     * the response was received at. Scheme, host and port of that URL are
     * taken from TYPO3's view of the request, so TYPO3's reverse proxy
     * settings apply to them.
     */
    private function useCurrentRequestAsSelfUrl(): void
    {
        $host = (string)GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        if ($host === '') {
            return;
        }

        $isHttps = (bool)GeneralUtility::getIndpEnv('TYPO3_SSL');
        $port = (int)GeneralUtility::getIndpEnv('TYPO3_PORT');

        if ($port <= 0) {
            $port = $isHttps ? 443 : 80;
        }

        Utils::setSelfProtocol($isHttps ? 'https' : 'http');
        Utils::setSelfHost($host);
        Utils::setSelfPort($port);

        // php-saml takes the path from the server's REQUEST_URI. TYPO3 puts the
        // reverse proxy prefix in front of it; php-saml adds it as base path.
        // php-saml 4.3 and later keep the whole path below that base path;
        // earlier versions keep only its last segment.
        $publicPath = (string)parse_url((string)GeneralUtility::getIndpEnv('REQUEST_URI'), PHP_URL_PATH);
        $serverPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $prefix = '';
        if ($serverPath !== '' && strlen($publicPath) > strlen($serverPath)
            && substr($publicPath, -strlen($serverPath)) === $serverPath
        ) {
            $prefix = substr($publicPath, 0, -strlen($serverPath));
        }
        Utils::setBaseURLPath($prefix);
    }

    /**
     * Returns Possible NameFormat Values.
     *
     * @param array $parameters Array with Parameters
     *
     * @throws \ReflectionException
     */
    public function nameIdFormatItems(array $parameters)
    {
        $items = [];
        $reflectionClass = new \ReflectionClass(Constants::class);
        foreach ($reflectionClass->getConstants() as $name => $value) {
            if (substr($name, 0, 7) !== 'NAMEID_') {
                continue;
            }

            $items[] = [$value, $name, null];
        }
        $parameters['items'] = $items;
    }

    /**
     * Returns the saml metadata as xml string
     * Returns the saml metadata as xml string
     *
     * @return string
     * @throws \OneLogin\Saml2\Error
     */
    public function getMetadata() : string
    {
        $settings = new Settings($this->getSettings()['saml']);

        $metadata = Metadata::builder($settings->getSPData(), true, true, time()+60*60*24*14, null);

        $metadata = MetaData::addX509KeyDescriptors($metadata, $this->getSettings()['saml']['sp']['x509cert']);

        return $metadata;
    }

    /**
     * Returns the settings array
     *
     * @return array
     */
    public function getSettings() : array
    {
        $this->buildSettings();

        return $this->settings;
    }

    /**
     * Build Saml Settings
     *
     * @return void
     */
    protected function buildSettings()
    {
        $settingsModel = $this->fetchSettings();

        $this->settings['saml']['sp']['entityId'] = $settingsModel->getSpEntityId();
        $this->settings['saml']['sp']['assertionConsumerService']['url'] = $settingsModel->getSpCustomerServiceUrl();
        $this->settings['saml']['sp']['assertionConsumerService']['binding'] = $settingsModel->getSpCustomerServiceBinding();
        $this->settings['saml']['sp']['NameIDFormat'] = \constant(Constants::class . '::' . $settingsModel->getSpNameIdFormat());
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
     * Return the settings
     *
     * @return \Netresearch\NrSamlAuth\Domain\Model\Settings|null
     */
    private function fetchSettings(): ?\Netresearch\NrSamlAuth\Domain\Model\Settings
    {
        $settings = $this->settingsRepository->findByUid($this->settingsUid);

        return ($settings instanceof \Netresearch\NrSamlAuth\Domain\Model\Settings) ? $settings : null;
    }
}

