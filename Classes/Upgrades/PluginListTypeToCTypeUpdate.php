<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Upgrades;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

/**
 * Turns content elements of the former "list" type with the plugin
 * nrsamlauth_authentication into content elements of that type (CType),
 * and the matching be_groups permissions with them.
 *
 * The Install namespace is the one both supported versions provide: EXT:install
 * on TYPO3 13.4, and EXT:core on TYPO3 14.3, where it extends the classes in
 * TYPO3\CMS\Core\Upgrades and is deprecated for removal in TYPO3 15.
 */
#[UpgradeWizard('nrSamlAuth_pluginListTypeToCType')]
final class PluginListTypeToCTypeUpdate extends AbstractListTypeToCTypeUpdate
{
    public function getTitle(): string
    {
        return 'SAML Authentication: migrate the plugin to its own content element type';
    }

    public function getDescription(): string
    {
        return 'Changes content elements of type "Insert Plugin" with the plugin "Saml Authentication"'
            . ' (list_type nrsamlauth_authentication) to the content element type nrsamlauth_authentication,'
            . ' and the backend user group permissions for it.';
    }

    /**
     * @return array<string, string>
     */
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'nrsamlauth_authentication' => 'nrsamlauth_authentication',
        ];
    }
}
