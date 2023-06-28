<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_nrsamlauth_domain_model_settings'
);


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Netresearch.NrSamlAuth',
    'tools',
    'samlauth',
    '',
    [
        Netresearch\NrSamlAuth\Controller\SamlAuthController::class => 'metadata'
    ],
    [
        'access' => 'systemMaintainer',
        'icon' => 'EXT:nr_saml_auth/Resources/Public/Icons/Extension.svg',
        'labels' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/SamlAuthModule.xlf'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('nr_saml_auth', 'Configuration/TypoScript', 'Netresearch SamlAuth');

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['nrsamlauth_authentication'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('nrsamlauth_authentication', 'FILE:EXT:nr_saml_auth/Configuration/FlexForms/flexform_auth.xml');

