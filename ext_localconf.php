<?php

declare(strict_types=1);

use Netresearch\NrSamlAuth\Controller\AuthController;
use Netresearch\NrSamlAuth\Sv\AuthenticationService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

(static function (): void {
    ExtensionUtility::configurePlugin(
        'NrSamlAuth',
        'Authentication',
        [
            AuthController::class => 'login, receiveSamlResponse',
        ],
        [
            AuthController::class => 'login, receiveSamlResponse',
        ]
    );

    ExtensionManagementUtility::addService(
        'nr_saml_auth',
        'auth',
        AuthenticationService::class,
        [
            'title' => 'SAML Authentication service',
            'description' => 'Authentication via SAML Service Provider for single sign-on (SSO)',
            'subtype' => 'authUserFE,authUserBE,getUserFE',
            'available' => true,
            'priority' => 100,
            'quality' => 100,
            'className' => AuthenticationService::class,
        ]
    );
})();
