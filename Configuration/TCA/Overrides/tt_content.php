<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || exit;

(static function (): void {
    ExtensionUtility::registerPlugin(
        'NrSamlAuth',
        'Authentication',
        'Saml Authentication',
    );
})();
