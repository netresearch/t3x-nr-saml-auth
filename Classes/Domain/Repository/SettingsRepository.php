<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Domain\Repository;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for SAML settings configuration.
 */
class SettingsRepository extends Repository
{
    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Find settings by SP entity ID (host URL)
     */
    public function findEntityIdByHost(string $host): ?Settings
    {
        $query = $this->createQuery();
        $query->matching(
            $query->equals('sp_entity_id', $host)
        );

        $result = $query->execute()->getFirst();

        return $result instanceof Settings ? $result : null;
    }
}
