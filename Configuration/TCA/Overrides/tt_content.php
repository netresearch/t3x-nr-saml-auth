<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

(static function (): void {
    ExtensionUtility::registerPlugin(
        'NrSamlAuth',
        'Authentication',
        'Saml Authentication'
    );
})();
