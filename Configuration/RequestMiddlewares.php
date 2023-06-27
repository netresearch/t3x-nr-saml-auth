<?php

//return [];

return [
    'frontend' => [
        'nrumauth/sso/redirect' => [
            'target' => \Netresearch\NrSamlAuth\Middleware\DeepLinkSsoMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
        ],
    ],
];
