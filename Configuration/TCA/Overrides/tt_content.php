<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

(static function (): void {
    ExtensionUtility::registerPlugin(
        'NrSamlAuth',
        'Authentication',
        'Saml Authentication'
    );
})();
