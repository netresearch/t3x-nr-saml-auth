<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Sv;

use Exception;
use Netresearch\NrSamlAuth\Domain\Model\Settings;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\Response;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AuthenticationService as Typo3AuthService;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WeakMap;

/**
 * SAML Authentication Service for TYPO3 frontend and backend authentication.
 *
 * Note: Authentication services are instantiated by TYPO3 core and use
 * injectX() methods for dependency injection rather than constructor injection.
 */
class AuthenticationService extends Typo3AuthService
{
    /**
     * Value of the login status for a login request. LoginType::LOGIN is a
     * string constant on TYPO3 12.4 and a backed enum case on TYPO3 13.4,
     * so the value is compared directly.
     */
    private const LOGIN_STATUS = 'login';

    /**
     * Users are looked up and created in this table; the service is
     * registered for getUserFE only.
     */
    private const USER_TABLE = 'fe_users';

    /**
     * Cache that records the assertions which logged a user in, so that each
     * assertion is accepted once (registered in ext_localconf.php)
     */
    public const ASSERTION_CACHE = 'nr_saml_auth_assertions';

    protected ?Response $samlResponse = null;

    private SettingsRepository $settingsRepository;

    private SamlService $samlService;

    private ConnectionPool $connectionPool;

    private CacheManager $cacheManager;

    /**
     * Assertion accepted by getUser() per request. getUser() and authUser()
     * run on separate instances of this service for the same request object.
     *
     * @var WeakMap<ServerRequestInterface, string>|null
     */
    private static ?WeakMap $assertionAcceptedForRequest = null;

    public function injectSettingsRepository(SettingsRepository $settingsRepository): void
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function injectSamlService(SamlService $samlService): void
    {
        $this->samlService = $samlService;
    }

    public function injectConnectionPool(ConnectionPool $connectionPool): void
    {
        $this->connectionPool = $connectionPool;
    }

    public function injectCacheManager(CacheManager $cacheManager): void
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Validates the login and returns the user record as array
     *
     * @return bool|array<string, mixed>
     * @throws Error
     */
    public function getUser(): bool|array
    {
        $this->samlService->setSettingsUid($this->getSamlId());

        if (!$this->isResponsible()) {
            $this->samlService->redirectUserToSSO();
            return false;
        }

        $settings = $this->settingsRepository->findByUid($this->getSamlId());
        if (!$settings instanceof Settings) {
            $this->logger?->error('SAML Settings not found', ['saml_id' => $this->getSamlId()]);
            return false;
        }

        $assertion = $this->getValidatedAssertion($settings, true);
        if ($assertion === null) {
            return false;
        }

        [$username, $attributes] = $assertion;

        $user = $this->fetchUserInStorageFolder($username, $settings);
        if ($user === null) {
            $this->insertUserRecord($username, $settings, $attributes);
            $user = $this->fetchUserInStorageFolder($username, $settings);
        }

        return $user ?? false;
    }

    /**
     * Validates the SAML response of the current request.
     *
     * Returns the username (with the prefix of the settings record) and the
     * attributes of a valid response, or null if the request carries no
     * response, the response is not valid, it names no user, or its
     * assertion was accepted before.
     *
     * With $consume, the assertion is recorded as used until its validity
     * period ends. getUser() consumes the assertion; authUser(), which runs
     * later for the same request, accepts it only for that request.
     *
     * @return array{0: string, 1: array<string, mixed>}|null
     */
    private function getValidatedAssertion(Settings $settings, bool $consume = false): ?array
    {
        try {
            $samlResponse = $this->samlService->getResponse($this->getSamlResponse(), $this->getRequest());
            if (!$samlResponse->isValid()) {
                $this->logger?->warning('SAML Response from SSO server is not valid', [
                    'reason' => $samlResponse->getError(),
                ]);
                return null;
            }

            $attributes = $samlResponse->getAttributes();
            $assertionId = $samlResponse->getAssertionId();
            $notOnOrAfter = $samlResponse->getAssertionNotOnOrAfter();
        } catch (Exception $e) {
            $this->logger?->warning('SAML Response from SSO server is not valid', [
                'reason' => $e->getMessage(),
            ]);
            return null;
        }

        $username = $this->getUsername($attributes);
        if ($username === '') {
            $this->logger?->warning('SAML Response from SSO server has no username attribute');
            return null;
        }

        $cacheIdentifier = hash('sha256', $settings->getUid() . '|' . $assertionId);
        $cache = $this->cacheManager->getCache(self::ASSERTION_CACHE);
        $request = $this->getRequest();
        self::$assertionAcceptedForRequest ??= new WeakMap();

        if ($cache->has($cacheIdentifier)) {
            $acceptedForThisRequest = !$consume
                && $request instanceof ServerRequestInterface
                && (self::$assertionAcceptedForRequest[$request] ?? null) === $cacheIdentifier;
            if (!$acceptedForThisRequest) {
                $this->logger?->warning('SAML assertion was already used for a login');
                return null;
            }
        } elseif ($consume) {
            // 0 keeps the entry without expiry for an assertion that states no end of validity
            $lifetime = $notOnOrAfter === null
                ? 0
                : max(60, $notOnOrAfter - time() + Constants::ALLOWED_CLOCK_DRIFT);
            $cache->set($cacheIdentifier, 1, [], $lifetime);
            if ($request instanceof ServerRequestInterface) {
                self::$assertionAcceptedForRequest[$request] = $cacheIdentifier;
            }
        } else {
            // authUser() for a response getUser() did not accept
            return null;
        }

        return [$settings->getUsernamePrefix() . $username, $attributes];
    }

    /**
     * Fetches the enabled frontend user with this username from the storage
     * folder of the settings record.
     *
     * The user is not looked up with fetchUserRecord(): the user table setup
     * TYPO3 passes to the service restricts the lookup to the storage folder
     * of the login request (TYPO3 13.4 adds it to `enable_clause`), while the
     * folder for SAML users is set in the settings record.
     *
     * @return array<string, mixed>|null
     */
    private function fetchUserInStorageFolder(string $username, Settings $settings): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::USER_TABLE);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class))
            ->add(GeneralUtility::makeInstance(StartTimeRestriction::class))
            ->add(GeneralUtility::makeInstance(EndTimeRestriction::class));

        $user = $queryBuilder
            ->select('*')
            ->from(self::USER_TABLE)
            ->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username)),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($settings->getUsersPid(), Connection::PARAM_INT)
                ),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return is_array($user) ? $user : null;
    }

    /**
     * Insert a user into the fe_users table
     *
     * @param array<string, mixed> $attributes
     */
    private function insertUserRecord(string $username, Settings $settings, array $attributes): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::USER_TABLE);

        $connection->insert(
            self::USER_TABLE,
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
        return ($this->login['status'] ?? '') === self::LOGIN_STATUS && $this->hasSamlResponse();
    }

    /**
     * Returns the SAML response from request
     */
    private function getSamlResponse(): string
    {
        $request = $this->getRequest();
        if (!$request instanceof ServerRequestInterface) {
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
        return !in_array($this->getSamlResponse(), ['', '0'], true);
    }

    /**
     * Returns the passed SAML ID or discovers it from request
     */
    private function getSamlId(): int
    {
        $request = $this->getRequest();
        if (!$request instanceof ServerRequestInterface) {
            return 1;
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['saml_id'])) {
            return (int)$queryParams['saml_id'];
        }

        $uri = $request->getUri();
        $url = $uri->getScheme() . '://' . $uri->getHost() . '/';

        $settings = $this->settingsRepository->findEntityIdByHost($url);

        if ($settings instanceof Settings && $settings->getUid() !== null) {
            return $settings->getUid();
        }

        return 1;
    }

    /**
     * Returns the current server request.
     *
     * TYPO3 passes the request to authentication services in the auth info
     * array (AbstractUserAuthentication::getAuthInfoArray()). The frontend
     * sets $GLOBALS['TYPO3_REQUEST'] only after the authentication middleware,
     * so the global is a fallback.
     */
    private function getRequest(): ?ServerRequestInterface
    {
        $request = $this->authInfo['request'] ?? $GLOBALS['TYPO3_REQUEST'] ?? null;

        return $request instanceof ServerRequestInterface ? $request : null;
    }

    /**
     * Authenticate a user against the SAML response of the current request.
     *
     * Returns one of the following status codes:
     *  >= 200: User authenticated successfully. No more checking is needed by other auth services.
     *  >= 100: User not authenticated; this service is not responsible. Other auth services will be asked.
     *  > 0:    User authenticated successfully. Other auth services will still be asked.
     *  <= 0:   Authentication failed, no more checking needed by other auth services.
     *
     * A request without a SAML response is left to the other services (100).
     * A request with a SAML response authenticates the user only if the
     * response is valid and names exactly this user in the storage folder of
     * the settings record (200); otherwise authentication fails (0).
     *
     * getUser() and authUser() are called on separate instances of this
     * service, so the response is validated here again.
     *
     * @param array<string, mixed> $user User
     */
    public function authUser(array $user): int
    {
        if (!$this->isResponsible()) {
            return 100;
        }

        $this->samlService->setSettingsUid($this->getSamlId());
        $settings = $this->settingsRepository->findByUid($this->getSamlId());
        if (!$settings instanceof Settings) {
            return 0;
        }

        $assertion = $this->getValidatedAssertion($settings);
        if ($assertion === null) {
            return 0;
        }

        $isSameUser = ($this->db_user['table'] ?? '') === self::USER_TABLE
            && (string)($user['username'] ?? '') === $assertion[0]
            && (int)($user['pid'] ?? -1) === $settings->getUsersPid();

        return $isSameUser ? 200 : 0;
    }
}
