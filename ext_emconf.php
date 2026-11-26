<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'SAML Frontend Authentication',
    'description' => 'Single sign-on (SSO) for frontend users via SAML.',
    'category' => 'services',
    'author' => 'Torsten Fink, Tobias Hein, Christopher Rath',
    'author_email' => 'torsten.fink@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'version' => '13.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'backend' => '13.4.0-14.3.99',
            'php' => '8.2.0-8.6.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
