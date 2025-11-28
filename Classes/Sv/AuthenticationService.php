<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Sv;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\ValidationError;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AuthenticationService as Typo3AuthService;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SAML Authentication Service for TYPO3 frontend and backend authentication.
 */
class AuthenticationService extends Typo3AuthService
{
    protected ?Response $samlResponse = null;

    private ?SettingsRepository $settingsRepository = null;
    private ?SamlService $samlService = null;

    /**
     * Validates the login and returns the user record as array
     *
     * @return bool|array<string, mixed>
     * @throws ValidationError
     * @throws \OneLogin\Saml2\Error
     */
    public function getUser(): bool|array
    {
        $this->getSamlService()->setSettingsUid($this->getSamlId());

        if (!$this->isResponsible()) {
            $this->getSamlService()->redirectUserToSSO();
            return false;
        }

        $this->getSamlService()->setSettingsUid($this->getSamlId());
        $samlResponse = $this->getSamlService()->getResponse($this->getSamlResponse());

        try {
            if (!$samlResponse->isValid()) {
                $this->logger?->warning('SAML Response from SSO server is not valid');
                return false;
            }
        } catch (ValidationError $e) {
            $this->logger?->error('SAML Response from SSO server is not valid', ['exception' => $e]);
            return false;
        }

        $settings = $this->getSettingsRepository()->findByUid($this->getSamlId());
        if (!$settings instanceof Settings) {
            $this->logger?->error('SAML Settings not found', ['saml_id' => $this->getSamlId()]);
            return false;
        }

        $username = $this->getUsername($samlResponse->getAttributes());

        $user = $this->fetchUserRecord($username, '', [
            'check_pid_clause' => '`pid` = \'' . $settings->getUsersPid() . '\'',
        ] + $this->db_user);

        if (!is_array($user)) {
            $this->insertUserRecord($username, $settings, $samlResponse->getAttributes());
            $user = $this->fetchUserRecord($username);
        }

        return $user;
    }

    /**
     * Insert a user into the fe_users table
     *
     * @param array<string, mixed> $attributes
     */
    private function insertUserRecord(string $username, Settings $settings, array $attributes): void
    {
        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $connection->insert(
            'fe_users',
            [
                'username' => $username,
                'usergroup' => $settings->getUsergroup(),
                'pid' => $settings->getUsersPid(),
                'email' => $this->getValueFromAttribute($attributes, 'mail'),
                'company' => $this->getValueFromAttribute($attributes, 'companyname'),
                'name' => $this->getValueFromAttribute($attributes, 'fullname'),
                'country' => $this->getValueFromAttribute($attributes, 'country'),
                'crdate' => time(),
                'tstamp' => time(),
            ]
        );
    }

    /**
     * Converts the username array to a string
     *
     * @param array<string, mixed> $usernameData
     */
    private function getUsername(array $usernameData): string
    {
        return implode('', $usernameData['username'] ?? []);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function getValueFromAttribute(array $attributes, string $key): string
    {
        if (!isset($attributes[$key])) {
            return '';
        }

        if (is_array($attributes[$key])) {
            return (string)reset($attributes[$key]);
        }

        return (string)$attributes[$key];
    }

    /**
     * Returns true if login service is responsible for the request
     */
    private function isResponsible(): bool
    {
        return $this->login['status'] === LoginType::LOGIN && $this->hasSamlResponse();
    }

    /**
     * Returns the SAML response from request
     */
    private function getSamlResponse(): string
    {
        $request = $this->getRequest();
        if ($request === null) {
            return '';
        }

        $parsedBody = $request->getParsedBody();
        return (string)($parsedBody['SAMLResponse'] ?? '');
    }

    /**
     * Returns true if response has SAML data
     */
    private function hasSamlResponse(): bool
    {
        return !empty($this->getSamlResponse());
    }

    /**
     * Returns the passed SAML ID or discovers it from request
     */
    private function getSamlId(): int
    {
        $request = $this->getRequest();
        if ($request === null) {
            return 1;
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['saml_id'])) {
            return (int)$queryParams['saml_id'];
        }

        $uri = $request->getUri();
        $url = $uri->getScheme() . '://' . $uri->getHost() . '/';

        $settings = $this->getSettingsRepository()->findEntityIdByHost($url);

        if ($settings instanceof Settings && $settings->getUid() !== null) {
            return $settings->getUid();
        }

        return 1;
    }

    private function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }

    private function getSettingsRepository(): SettingsRepository
    {
        if ($this->settingsRepository === null) {
            $this->settingsRepository = GeneralUtility::makeInstance(SettingsRepository::class);
        }

        return $this->settingsRepository;
    }

    private function getSamlService(): SamlService
    {
        if ($this->samlService === null) {
            $this->samlService = GeneralUtility::makeInstance(SamlService::class);
        }

        return $this->samlService;
    }

    /**
     * Authenticate a user: SAML authentication was already validated in getUser()
     *
     * Returns one of the following status codes:
     *  >= 200: User authenticated successfully. No more checking is needed by other auth services.
     *  >= 100: User not authenticated; this service is not responsible. Other auth services will be asked.
     *  > 0:    User authenticated successfully. Other auth services will still be asked.
     *  <= 0:   Authentication failed, no more checking needed by other auth services.
     *
     * @param array<string, mixed> $user User
     */
    public function authUser(array $user): int
    {
        return 200;
    }
}
