<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

use Netresearch\NrSamlAuth\Middleware\DeepLinkSsoMiddleware;

//return [];
return [
    'frontend' => [
        'nrumauth/sso/redirect' => [
            'target' => DeepLinkSsoMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
        ],
    ],
];
