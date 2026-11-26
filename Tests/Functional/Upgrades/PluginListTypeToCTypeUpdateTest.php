<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Upgrades;

use Netresearch\NrSamlAuth\Upgrades\PluginListTypeToCTypeUpdate;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardRegistry as CoreUpgradeWizardRegistry;
use TYPO3\CMS\Install\Updates\UpgradeWizardRegistry as InstallUpgradeWizardRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PluginListTypeToCTypeUpdateTest extends FunctionalTestCase
{
    private const PLUGIN_SIGNATURE = 'nrsamlauth_authentication';

    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    protected function setUp(): void
    {
        parent::setUp();

        // TYPO3 14 no longer defines tt_content.list_type, so a fresh
        // database lacks it. An installation upgraded from TYPO3 13 still
        // has the column until the database analyzer removes it.
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content');
        if (!isset($connection->createSchemaManager()->listTableColumns('tt_content')['list_type'])) {
            $connection->executeStatement("ALTER TABLE tt_content ADD COLUMN list_type VARCHAR(255) DEFAULT '' NOT NULL");
        }
    }

    #[Test]
    public function theWizardIsRegistered(): void
    {
        // The registry moved from EXT:install (TYPO3 13.4) to EXT:core (TYPO3 14.3).
        $registry = class_exists(CoreUpgradeWizardRegistry::class)
            ? $this->get(CoreUpgradeWizardRegistry::class)
            : $this->get(InstallUpgradeWizardRegistry::class);

        self::assertInstanceOf(
            PluginListTypeToCTypeUpdate::class,
            $registry->getUpgradeWizard('nrSamlAuth_pluginListTypeToCType'),
        );
    }

    #[Test]
    public function pluginContentElementsAndPermissionsAreMigrated(): void
    {
        $connectionPool = $this->get(ConnectionPool::class);
        $connectionPool->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid'       => 1,
            'pid'       => 1,
            'CType'     => 'list',
            'list_type' => self::PLUGIN_SIGNATURE,
        ]);
        $connectionPool->getConnectionForTable('tt_content')->insert('tt_content', [
            'uid'       => 2,
            'pid'       => 1,
            'CType'     => 'list',
            'list_type' => 'otherextension_pi1',
        ]);
        $connectionPool->getConnectionForTable('be_groups')->insert('be_groups', [
            'uid'                => 1,
            'pid'                => 0,
            'title'              => 'Editors',
            'explicit_allowdeny' => 'tt_content:CType:text,tt_content:list_type:' . self::PLUGIN_SIGNATURE,
        ]);

        $subject = new PluginListTypeToCTypeUpdate($connectionPool);

        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFalse($subject->updateNecessary());

        self::assertSame(
            ['CType' => self::PLUGIN_SIGNATURE, 'list_type' => ''],
            $this->fetchContentElement(1),
        );
        self::assertSame(
            ['CType' => 'list', 'list_type' => 'otherextension_pi1'],
            $this->fetchContentElement(2),
        );
        self::assertSame(
            'tt_content:CType:text,tt_content:CType:' . self::PLUGIN_SIGNATURE,
            $connectionPool->getConnectionForTable('be_groups')
                ->select(['explicit_allowdeny'], 'be_groups', ['uid' => 1])
                ->fetchOne(),
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    private function fetchContentElement(int $uid): array|false
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')
            ->select(['CType', 'list_type'], 'tt_content', ['uid' => $uid])
            ->fetchAssociative();
    }
}
