<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/**
 * Icon registration for TYPO3 12+
 */
return [
    'nr-saml-auth-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:nr_saml_auth/Resources/Public/Icons/Extension.svg',
    ],
];
