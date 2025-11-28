<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    ExtensionManagementUtility::allowTableOnStandardPages(
        'tx_nrsamlauth_domain_model_settings'
    );

    ExtensionManagementUtility::addStaticFile(
        'nr_saml_auth',
        'Configuration/TypoScript',
        'Netresearch SAML Auth'
    );

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['nrsamlauth_authentication'] = 'pi_flexform';
    ExtensionManagementUtility::addPiFlexFormValue(
        'nrsamlauth_authentication',
        'FILE:EXT:nr_saml_auth/Configuration/FlexForms/flexform_auth.xml'
    );
})();
