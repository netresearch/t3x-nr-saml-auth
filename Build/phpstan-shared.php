<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

// Included by phpstan.neon. CI removes netresearch/typo3-ci-workflows on the
// TYPO3 ^12.4 cells (remove-dev-deps in .github/workflows/ci.yml), because its
// saschaegerer/phpstan-typo3 requires typo3/cms-core ^13.4.3, and
// phpstan/extension-installer goes with it. A plain NEON include of the shared
// config would fail there on a missing file, so the include is made only when
// the package is installed; phpstan/extension-installer then registers the
// strict, deprecation, PHPUnit, phpat, TYPO3 and ergebnis rules. Without the
// package, the ^12.4 cells include the two extensions they ran before by hand
// and keep the parameters the old config set. Those cells resolve PHPStan 1.x
// (phpstan-typo3 ^1.10), where level 10 does not exist.
$vendor = __DIR__ . '/../.Build/vendor';
$shared = $vendor . '/netresearch/typo3-ci-workflows/config/phpstan/phpstan.neon';

if (is_file($shared)) {
    return ['includes' => [$shared]];
}

return [
    'includes' => [
        $vendor . '/phpstan/phpstan-phpunit/extension.neon',
        $vendor . '/saschaegerer/phpstan-typo3/extension.neon',
    ],
    'parameters' => [
        'level'                     => 8,
        'treatPhpDocTypesAsCertain' => false,
    ],
];
