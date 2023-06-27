<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Saml Authentication for frontend',
    'description' => 'TYPO3 Saml Authentication for frontend',
    'category' => 'services',
    'author' => 'Torsten Fink',
    'author_email' => 'torsten.fink@netresearch.de',
    'author_company' => 'Netresearch GmbH & Co. KG',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'version' => '10.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
