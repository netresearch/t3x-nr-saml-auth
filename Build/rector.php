<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 *
 * This file is part of the package netresearch/nr-saml-auth.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\ValueObject\PhpVersion;

$configure = require_once __DIR__ . '/../.Build/vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org config: paths (Classes, Configuration, Resources, Tests,
    // ext_*.php), the ext_emconf.php skip, the code-quality sets and the
    // TYPO3 12 level set for the lowest TYPO3 version composer.json allows.
    $configure($rectorConfig, __DIR__ . '/..', [
        'tests' => true,
        'typo3' => 12,
    ]);

    // composer.json requires php ^8.1 and CI runs a PHP 8.1 cell, but the
    // factory's `php` option starts at 8.2. Lowering phpVersion() after the
    // factory keeps Rector from emitting 8.2-only syntax (ReadOnlyClassRector
    // turns the event listeners into `final readonly class`, a parse error
    // on 8.1).
    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    $rectorConfig->skip([
        // The event listeners are registered with an explicit `event:` key, so
        // TYPO3 calls __invoke($event) regardless of the signature — but a PSR-14
        // listener has to accept the event it is registered for, and dropping the
        // parameter turns the tag-based registration in Services.yaml into a
        // container build error as soon as the event is derived from the signature.
        RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__ . '/../Classes/EventListener',
        ],
    ]);
};
