<?php

namespace Netresearch\NrSamlAuth\Controller;

use Netresearch\NrSamlAuth\Service\SamlService;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class AuthController
 *
 * @category   Authentication
 * @package    Netresearch\NrSamlAuth\Controller
 * @subpackage Controller
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class AuthController extends ActionController
{
    /**
     * @var \Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var SamlService
     */
    private $samlService;

    /**
     * @var \TYPO3\CMS\Core\Context\Context
     */
    private $context;

    /**
     * Inject the settings repository
     *
     * @param \Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository $settingsRepository
     *
     * @return void
     */
    public function injectSettingsRepository(\Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Injects the saml Service
     *
     * @param SamlService $samlService SamlService
     *
     * @return void
     */
    public function injectSamlService(SamlService $samlService) : void
    {
        $this->samlService = $samlService;
    }

    /**
     * Injects The Context
     *
     * @param \TYPO3\CMS\Core\Context\Context $context
     */
    public function injectContext(\TYPO3\CMS\Core\Context\Context $context)
    {
        $this->context = $context;
    }

    /**
     * Login Action
     *
     * @throws \OneLogin\Saml2\Error
     * @return void
     */
    public function loginAction(): void
    {
        $this->samlService->setSettingsUid(
            $this->getSamlSettingsUid()
        );

        if ($this->isLogoutRequest()) {
            return;
        }

        if ($this->isLoggedIn()) {
            $this->view->assign('isLoggedIn', 'true');
            $this->view->assign('feUser', $GLOBALS['TSFE']->fe_user->user);
        } else {
            $this->view->assign('isLoggedIn', 'false');
            $this->samlService->redirectUserToSSO();
        }
    }

    /**
     * Returns true if user is already logged in
     *
     * @return bool
     */
    private function isLoggedIn(): bool
    {
        try {
            return $this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        } catch (\Exception $exception) {
            $this->getLogger()->error($exception->getMessage(), ['exception' => $exception]);
            return false;
        }
    }

    /**
     * Returns true if request is logout
     *
     * @return bool
     */
    private function isLogoutRequest()
    {
        return GeneralUtility::_GET('logintype') === LoginType::LOGOUT;
    }

    /**
     * Returns a logger instance
     *
     * @return Logger
     */
    private function getLogger(): Logger
    {
        $this->objectManager->get(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Returns the saml setings uid from pluginconfig if set or 1.
     *
     * @return int
     */
    private function getSamlSettingsUid(): int
    {
        if (isset($this->settings['samlAuthSettings'])) {
            return (integer) $this->settings['samlAuthSettings'];
        }

        return 1;
    }
}
