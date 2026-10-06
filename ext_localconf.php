<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

use Netresearch\NrSamlAuth\Controller\AuthController;
use Netresearch\NrSamlAuth\Sv\AuthenticationService;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') || die();

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

    // Assertions that logged a user in, kept until their validity period ends.
    // Group "system" so that clearing the page caches does not remove them.
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][AuthenticationService::ASSERTION_CACHE] ??= [
        'frontend' => VariableFrontend::class,
        'backend' => Typo3DatabaseBackend::class,
        'groups' => ['system'],
    ];

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
