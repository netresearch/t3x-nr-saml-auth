<?php

declare(strict_types=1);

use Netresearch\NrSamlAuth\Controller\SamlAuthController;

/**
 * Backend module registration for TYPO3 12+
 */
return [
    'tools_nrsamlauth' => [
        'parent' => 'tools',
        'position' => ['after' => 'tools_ExtensionmanagerExtensionmanager'],
        'access' => 'systemMaintainer',
        'workspaces' => 'live',
        'path' => '/module/tools/saml-auth',
        'labels' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/SamlAuthModule.xlf',
        'iconIdentifier' => 'nr-saml-auth-module',
        'extensionName' => 'NrSamlAuth',
        'controllerActions' => [
            SamlAuthController::class => ['metadata'],
        ],
    ],
];
