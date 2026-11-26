<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || exit;

(static function (): void {
    // The plugin is a content element of its own (CType), the only plugin
    // type TYPO3 14 knows. Content elements of the former "list" type are
    // migrated by Netresearch\NrSamlAuth\Upgrades\PluginListTypeToCTypeUpdate.
    $pluginSignature = ExtensionUtility::registerPlugin(
        'NrSamlAuth',
        'Authentication',
        'Saml Authentication',
    );

    $flexForm = 'FILE:EXT:nr_saml_auth/Configuration/FlexForms/flexform_auth.xml';
    if ((new Typo3Version())->getMajorVersion() >= 14) {
        // TYPO3 14 reads the data structure of a record type from its
        // columnsOverrides; addPiFlexFormValue() is deprecated there.
        $GLOBALS['TCA']['tt_content']['types'][$pluginSignature]['columnsOverrides']['pi_flexform']['config']['ds'] = $flexForm;
    } else {
        // TYPO3 13.4 looks the data structure up through the
        // ds_pointerField "list_type,CType" of pi_flexform.
        ExtensionManagementUtility::addPiFlexFormValue('*', $flexForm, $pluginSignature);
    }

    ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:plugin, pi_flexform',
        $pluginSignature,
        'after:palette:headers',
    );
})();
