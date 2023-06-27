<?php
namespace Netresearch\NrSamlAuth\Controller;

use Netresearch\NrSamlAuth\Service\SamlService;
use OneLogin\Saml2\Error;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;

/**
 * Class SamlAuthController
 *
 * Saml Auth BE Controller
 *
 * @category   Authentication
 * @package    Netresearch\NrSamlAuth\Controller
 * @subpackage Controller
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class SamlAuthController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var \Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository
     */
    private $settingsRepository;

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
     * MetaData Action
     *
     * @return void
     * @throws Error
     * @throws NoSuchArgumentException
     */
    public function metadataAction()
    {
        $settings = $this->settingsRepository->findAll();

        $this->view->assign('samlSettings', $settings);

        if ($this->getArgumentSamlSesstings()) {
            /* @var SamlService $samlService */
            $samlService = $this->objectManager->get(SamlService::class);
            $samlService->setSettingsUid($this->getArgumentSamlSesstings());
            $metadataXML = $samlService->getMetadata();

            $this->view->assign(
                'SamlMetaData',
                htmlentities($metadataXML, null, 'utf-8', false)
            );
        }


    }

    /**
     * Returns the id of selected saml settings
     *
     * @return string|null
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    private function getArgumentSamlSesstings()
    {
        if ($this->request->hasArgument('samlUid')) {
            return $this->request->getArgument('samlUid');
        }

        return null;
    }
}
