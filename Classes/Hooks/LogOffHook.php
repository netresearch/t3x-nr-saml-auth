<?php

namespace Netresearch\NrSamlAuth\Hooks;

use Netresearch\NrSamlAuth\Service\SamlService;
use Netresearch\NrSamlAuth\Session\SamlSession;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class LogOffHook
 *
 * @category   Authentication
 * @package    Netresearch\NrSamlAuth
 * @subpackage Hooks
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class LogOffHook implements SingletonInterface
{
    /**
     * @var SamlSession
     */
    private $samlService;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var array
     */
    private static $sessionData;


    /**
     * Keep the saml data in static property to have it available for the logoff process.
     *
     * @param                            $params         Parameters
     * @param AbstractUserAuthentication $authentication Authentication object
     *
     * @throws \OneLogin\Saml2\Error
     */
    public function logOffPreProcess($params, AbstractUserAuthentication $authentication)
    {
        $this->getSamlSession()->setUser($authentication);
        static::$sessionData = $this->getSamlSession()->getSessionData();
    }

    /**
     * Proceed the postLogOff Process
     * Use the saml data from the preProcess to generate the saml logout
     *
     * @param                            $params         Parameters
     * @param AbstractUserAuthentication $authentication Authentication object
     *
     * @throws \OneLogin\Saml2\Error
     */
    public function logOffPostProcess($params, AbstractUserAuthentication $authentication)
    {
        try {
            $samlId = static::$sessionData['id'];
            $assertionId = static::$sessionData['AssertionId'];
            $nameId = static::$sessionData['nameId'];

            if (empty($samlId) || empty($assertionId)) {
                return;
            }

            $samlService = $this->getSamlService();
            $samlService->setSettingsUid($samlId);
            $samlService->redirectUserToLogout($nameId, $assertionId);
        }catch (\Exception $exception) {
            return;
        }
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
     * Returns an instance of SamlService
     *
     * @return SamlService
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
     * Returns a instance of SamlSession
     *
     * @return SamlSession|object
     */
    private function getSamlSession()
    {
        return $this->getObjectManager()->get(SamlSession::class);
    }
}
