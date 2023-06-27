<?php

namespace Netresearch\NrSamlAuth\Session;


use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Class SamlSession
 *
 * @category   Authentication
 * @package    Netresearch\NrSamlAuth\Session
 * @subpackage Session
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class SamlSession implements SingletonInterface
{
    private $keyName = 'NrSamlAuth';

    /**
     * @var AbstractUserAuthentication
     */
    private $user;

    /**
     * @param AbstractUserAuthentication $userAuthentication
     */
    public function setUser(AbstractUserAuthentication $userAuthentication)
    {
        $this->user = $userAuthentication;
    }

    /**
     * Returns frontend user authentication
     *
     * @return AbstractUserAuthentication
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns true if fe user is available
     *
     * @return bool
     */
    public function isSessionAvailable()
    {
        return $this->getUser() instanceof FrontendUserAuthentication;
    }

    /**
     * Returns the session data
     *
     * @return mixed|null
     */
    public function getSessionData()
    {
        if (false === $this->isSessionAvailable()) {
            return null;
        }

        $this->getUser()->fetchUserSession();
        return $this->getUser()->getSessionData($this->keyName);
    }

    /**
     * Set the data to session
     *
     * @param array|null $sessionData
     *
     * @return bool
     */
    public function setSessionData(array $sessionData = null)
    {
        if (false === $this->isSessionAvailable()) {
            return false;
        }

        $this->getUser()->fetchUserSession();
        $this->getUser()->setSessionData($this->keyName, $sessionData);
        $this->getUser()->storeSessionData();

        return true;
    }
}
