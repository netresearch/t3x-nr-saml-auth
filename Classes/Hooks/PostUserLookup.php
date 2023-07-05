<?php

namespace Netresearch\NrSamlAuth\Hooks;

use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use Netresearch\NrSamlAuth\Session\SamlSession;
use OneLogin\Saml2\Response;
use OneLogin\Saml2\Utils;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class PostUserLookup
 *
 * @category   Authentication
 * @package    Netresearch\NrSamlAuth\Hooks
 * @subpackage Hooks
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class PostUserLookup
{
    /**
     * @var Response
     */
    protected $samlResponse;

    /**
     * @var SamlService
     */
    private $samlService;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;


    /**
     * Writes data from saml response into session which are needed to perform logout.
     *
     * @param array $params
     *
     * @return void
     */
    public function process($params, $ref)
    {
        $pObj = $params['pObj'];

       $obj = $this->getSamlService();
       $obj->setSettingsUid($this->getSamlId());

        if (false === $this->hasSamlResponse()) {
            unset($this->samlService);
            return;
        }

        try {
            /**
             * @var Response $samlResponse
             */
            $samlResponse = $this->getSamlService()->getResponse($this->getSamlResponse());

            $sessionData = ['id' => $this->getSamlId(), 'AssertionId' => $samlResponse->getAssertionId(), 'nameId' => $samlResponse->getNameId()];
            $this->getSamlSession()->setUser($pObj);
            $this->getSamlSession()->setSessionData($sessionData);
        } catch (\Exception $exception) {
            GeneralUtility::makeInstance(LogManager::class)
                          ->getLogger(__CLASS__)
                          ->error($exception->getMessage());
        }
    }

    /**
     * Returns the sam response
     *
     * @return string
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
     */
    private function getSamlId()
    {

        if ($samlId = GeneralUtility::_GET('saml_id')) {
            return (int) $samlId;
        }

        $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/';

        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('tx_nrsamlauth_domain_model_settings');
        $res = $queryBuilder
        ->select('uid')
            ->from('tx_nrsamlauth_domain_model_settings')
            ->where(
                $queryBuilder->expr()->eq(
                    'sp_entity_id',
                    $queryBuilder->createNamedParameter($url, \PDO::PARAM_STR)
                )
            )->executeQuery()->fetchAssociative();

        if (isset($res['uid'])) {
            return $res['uid'];
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
     * Returns an instance of SamlService
     *
     * @return SamlService
     */
    private function getSamlService(): SamlService
    {
        if ($this->samlService instanceof SamlService) {
            return $this->samlService;
        }

        $this->samlService = GeneralUtility::makeInstance(SamlService::class);
        return $this->samlService;
    }

    /**
     * @return SamlSession|object
     * @throws Exception
     */
    private function getSamlSession()
    {
        return $this->getObjectManager()->get(SamlSession::class);
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

        $this->settingsRepository = GeneralUtility::makeInstance(SettingsRepository::class);

        return $this->settingsRepository;
    }
}
