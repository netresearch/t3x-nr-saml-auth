<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Saml Authentication for frontend',
    'description' => 'TYPO3 Saml Authentication for frontend',
    'category' => 'services',
    'author' => 'Torsten Fink, Tobias Hein, Christopher Rath',
    'author_email' => 'torsten.fink@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '10.0.9',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99 || 12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
