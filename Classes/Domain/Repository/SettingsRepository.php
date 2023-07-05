<?php

namespace Netresearch\NrSamlAuth\Domain\Repository;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class SettingsRepository
 *
 * @category   Authentication
 * @package    Netresearch\NrSamlAuth\Domain\Repository
 * @subpackage Domain
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class SettingsRepository extends Repository
{
    /**
     * Initializes the repository
     *
     * @return void
     */ 
    public function initializeObject()
    {

        $this->setDefaultQuerySettings($this->objectManager->get(Typo3QuerySettings::class));
        $this->defaultQuerySettings->setRespectStoragePage(false);
        $this->defaultQuerySettings->setRespectSysLanguage(false);
    }

    /**
     * Record $_Server["HTTP_HOST"]
     *
     * @param string $host
     * @return Settings
     */
     public function findEntityIdByHost($host)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('sp_entity_id', $host),
            )
        );

        return $query->execute()->getFirst();
    }

}
