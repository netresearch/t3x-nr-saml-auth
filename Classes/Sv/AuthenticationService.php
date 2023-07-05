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

        $this->getSamlService()->setSettingsUid($this->getSamlId());

        /**
         * @var Response $samlResponse
         */
        $samlResponse = $this->getSamlService()->getResponse($this->getSamlResponse());
        try {
            if (false === $samlResponse->isValid()) {
                $this->logger->warning('SAMLResponse form SSO server is not valid');

                return false;
            }
        } catch(ValidationError $e) {
            $this->logger->error('SAMLResponse form SSO server is not valid', ['exception' => $e]);
            return false;
        }

        /**
         * @var Settings $settings
         */
        $settings = $this->getSettingsRepository()->findByUid($this->getSamlId());
        $username = $this->getUsername($samlResponse->getAttributes());

        $user = $this->fetchUserRecord($username, '', [
            'check_pid_clause' => '`pid` = \'' . $settings->getUsersPid() . '\''
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
    private function getUsername($username)
    {
        return implode($username['username']);
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
        return $this->login['status'] === LoginType::LOGIN && $this->hasSamlResponse();
    }

    /**
     * Returns the sam respnse
     *
     * @return mixed
     */
    private function getSamlResponse()
    {
        return GeneralUtility::_POST('SAMLResponse');
    }

    /**
     * Returns true if response has saml data
     *
     * @return bool
     */
    private function hasSamlResponse(): bool
    {
        return false === empty(GeneralUtility::_POST('SAMLResponse'));
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
     * Authenticate a user: Check submitted user credentials against stored hashed password,
     * check domain lock if configured.
     *
     * Returns one of the following status codes:
     *  >= 200: User authenticated successfully. No more checking is needed by other auth services.
     *  >= 100: User not authenticated; this service is not responsible. Other auth services will be asked.
     *  > 0:    User authenticated successfully. Other auth services will still be asked.
     *  <= 0:   Authentication failed, no more checking needed by other auth services.
     *
     * @param array $user User
     *
     * @return int Authentication status code, one of 0, 100, 200
     */
    public function authUser(array $user): int
    {
        return 200;
    }
}
