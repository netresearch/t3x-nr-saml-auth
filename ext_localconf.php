<?php
defined('TYPO3_MODE') or die('Access denied.');

/* @var string $_EXTKEY */

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Netresearch.NrSamlAuth',
    'Authentication',
    [
        'Auth' => 'login,receiveSamlResponse'
    ],
    [
        'Auth' => 'login,receiveSamlResponse'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    $_EXTKEY,
    'auth',
    \Netresearch\NrSamlAuth\Sv\AuthenticationService::class,
    [
        'title' => 'Authentication service',
        'description' => 'Authentication with over a Service Provider.',
        'subtype' => 'authUserFE,authUserBE,getUserFE',
        'available' => true,
        'priority' => 100,
        'quality' => 100,
        'className' => \Netresearch\NrSamlAuth\Sv\AuthenticationService::class
    ]
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'][] = \Netresearch\NrSamlAuth\Hooks\LogOffHook::class . '->logOffPreProcess';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = \Netresearch\NrSamlAuth\Hooks\LogOffHook::class . '->logOffPostProcess';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'][] = \Netresearch\NrSamlAuth\Hooks\PostUserLookup::class . '->process';