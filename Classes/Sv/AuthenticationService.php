<?php
declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Sv;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Utils;
use OneLogin\Saml2\ValidationError;
use TYPO3\CMS\Core\Authentication\AuthenticationService as Typo3AuthService;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AuthenticationService
 *
 * @category   Authentiction
 * @package    Netresearch\NrSamlAuth\Sv
 * @subpackage Service
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class AuthenticationService extends Typo3AuthService
{
    /**
     * @var Response
     */
    protected $samlResponse;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var SamlService
     */
    private $samlService;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Validates the login an returns the userrecord as array
     *
     * @return bool|array
     * @throws ValidationError
     * @throws \OneLogin\Saml2\Error
     */
    public function getUser()
    {
        $this->getSamlService()->setSettingsUid($this->getSamlId());


        if (false === $this->isResponsible()) {
            $this->getSamlService()->redirectUserToSSO();
            return false;
        }

        $settings = $this->getSettingsRepository()->findByUid($this->getSamlId());
        if (!$settings instanceof Settings) {
            $this->logger->error('SAML settings not found');
            return false;
        }

        $assertion = $this->getValidatedAssertion();
        if ($assertion === null) {
            return false;
        }

        list($username, $attributes) = $assertion;
        $dbUser = ['check_pid_clause' => $this->getStorageFolderCondition($settings)] + $this->db_user;

        // Existence check without the enable fields, so that a disabled user is not created again
        if (!is_array($this->fetchUserRecord($username, '', ['enable_clause' => ''] + $dbUser))) {
            $this->insertUserRecord($username, $settings, $attributes);
        }

        // A user who exists but is disabled, not yet active or expired is not logged in
        $user = $this->fetchUserRecord($username, '', $dbUser);
        if (!is_array($user)) {
            $this->logger->warning('Frontend user for the SAML response is disabled');
            return false;
        }

        return $user;
    }

    /**
     * Validates the SAML response of the current request.
     *
     * Returns the username and the attributes of a valid response, or null if
     * the response is not valid or names no user.
     *
     * @return array|null [username, attributes]
     */
    private function getValidatedAssertion()
    {
        try {
            $samlResponse = $this->getSamlService()->getResponse($this->getSamlResponse());
            if (false === $samlResponse->isValid()) {
                $this->logger->warning('SAMLResponse from SSO server is not valid', ['reason' => $samlResponse->getError()]);
                return null;
            }
            $attributes = $samlResponse->getAttributes();
        } catch (\Exception $e) {
            $this->logger->warning('SAMLResponse from SSO server is not valid', ['reason' => $e->getMessage()]);
            return null;
        }

        $username = $this->getUsername($attributes);
        if ($username === '') {
            $this->logger->warning('SAMLResponse from SSO server has no username attribute');
            return null;
        }

        return [$username, $attributes];
    }

    /**
     * SQL condition for the storage folder of the settings record
     *
     * @param Settings $settings
     * @return string
     */
    private function getStorageFolderCondition(Settings $settings): string
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_users')
            ->expr();

        return (string)$expressionBuilder->eq('pid', (int)$settings->getUsersPid());
    }

    /**
     * Insert a user into the fe_users table
     *
     * @param string $username Name of user in typo3 database
     * @param Settings $settings SamlSettings Object
     * @param array $attributes
     *
     * @return void
     */
    private function insertUserRecord(string $username, Settings $settings, array $attributes): void
    {
        /* @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('fe_users');

        $connection->insert(
            'fe_users',
            [
                'username'  => $username,
                'usergroup' => $settings->getUsergroup(),
                'pid'       => $settings->getUsersPid(),
                'email'     => $this->getValueFromAttribute($attributes, 'mail'),
                'company'   => $this->getValueFromAttribute($attributes, 'companyname'),
                'name'      => $this->getValueFromAttribute($attributes, 'fullname'),
                'country'   => $this->getValueFromAttribute($attributes, 'country'),
                'crdate'    => time(),
                'tstamp'    => time()
            ]
        );
    }

    /**
     * Converts the array $username into a string.
     *
     * @param $username
     * @return string
     */
    private function getUsername(array $username): string
    {
        if (!isset($username['username']) || !is_array($username['username'])) {
            return '';
        }

        return implode('', $username['username']);
    }

    private function getValueFromAttribute(array $attributes, string $key): ?string
    {
        if (!isset($attributes[$key])) {
            return "";
        }

        if (is_array($attributes[$key])) {
            return reset($attributes[$key]);
        }

        return $attributes[$key];
    }

    /**
     * Returns true if login service is responsible for the request
     *
     * @return bool
     */
    private function isResponsible(): bool
    {
        return ($this->login['status'] ?? '') === LoginType::LOGIN && $this->hasSamlResponse();
    }

    /**
     * Returns the sam respnse
     *
     * @return mixed
     */
    private function getSamlResponse(): string
    {
        $samlResponse = GeneralUtility::_POST('SAMLResponse');

        return is_string($samlResponse) ? $samlResponse : '';
    }

    /**
     * Returns true if response has saml data
     *
     * @return bool
     */
    private function hasSamlResponse(): bool
    {
        return false === empty($this->getSamlResponse());
    }

    /**
     * Returns the passed saml id or 1 if not passed by request.
     *
     * @return int
     * @throws Exception
     */
    private function getSamlId()
    {
        if ($samlId = GeneralUtility::_GET('saml_id')) {
            return (int) $samlId;
        }

        $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/';
        $settings = $this->getSettingsRepository()->findEntityIdByHost($url);

        if ($settings) {
            return $settings->getUid();
        }

        return 1;
    }

    /**
     * Returns the instance of object manager
     *
     * @return ObjectManager
     */
    private function getObjectManager(): ObjectManager
    {
        if ($this->objectManager instanceof ObjectManager) {
            return $this->objectManager;
        }

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        return $this->objectManager;
    }

    /**
     * Returns instance of settings repository
     *
     * @return SettingsRepository
     * @throws Exception
     */
    private function getSettingsRepository(): SettingsRepository
    {
        if ($this->settingsRepository instanceof SettingsRepository) {
            return $this->settingsRepository;
        }

        $this->settingsRepository = $this->getObjectManager()->get(SettingsRepository::class);

        return $this->settingsRepository;
    }

    /**
     * Returns an instance of SamlService
     *
     * @return SamlService
     * @throws Exception
     */
    private function getSamlService(): SamlService
    {
        if ($this->samlService instanceof SamlService) {
            return $this->samlService;
        }

        $this->samlService = $this->getObjectManager()->get(SamlService::class);
        return $this->samlService;
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
     * response is valid and names exactly this frontend user in the storage
     * folder of the settings record (200); otherwise authentication fails (0).
     *
     * @param array $user User
     *
     * @return int Authentication status code, one of 0, 100, 200
     */
    public function authUser(array $user): int
    {
        if (false === $this->isResponsible()) {
            return 100;
        }

        if (($this->db_user['table'] ?? '') !== 'fe_users') {
            return 0;
        }

        $this->getSamlService()->setSettingsUid($this->getSamlId());
        $settings = $this->getSettingsRepository()->findByUid($this->getSamlId());
        if (!$settings instanceof Settings) {
            return 0;
        }

        $assertion = $this->getValidatedAssertion();
        if ($assertion === null) {
            return 0;
        }

        $isSameUser = (string)($user['username'] ?? '') === $assertion[0]
            && (int)($user['pid'] ?? -1) === (int)$settings->getUsersPid();

        return $isSameUser ? 200 : 0;
    }
}
