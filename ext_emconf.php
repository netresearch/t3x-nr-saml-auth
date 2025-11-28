<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'SAML Authentication for Frontend',
    'description' => 'TYPO3 SAML Authentication for frontend users using single sign-on (SSO)',
    'category' => 'services',
    'author' => 'Torsten Fink, Tobias Hein, Christopher Rath',
    'author_email' => 'torsten.fink@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'version' => '13.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'php' => '8.1.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
