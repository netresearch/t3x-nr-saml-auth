<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;

$configure = require_once __DIR__ . '/../.Build/vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org config: paths (Classes, Configuration, Resources, Tests,
    // ext_*.php), the ext_emconf.php skip, the code-quality sets and the
    // TYPO3 13 level set for the lowest TYPO3 version composer.json allows,
    // and the PHP 8.2 level set for the lowest PHP version (php ^8.2).
    $configure($rectorConfig, __DIR__ . '/..', [
        'tests' => true,
        'php'   => '8.2',
        'typo3' => 13,
    ]);

    $rectorConfig->skip([
        // The event listeners are registered with an explicit `event:` in their
        // #[AsEventListener] attribute, so TYPO3 calls __invoke($event)
        // regardless of the signature — but a PSR-14 listener has to accept the
        // event it is registered for, and dropping the parameter turns the
        // registration into a container build error as soon as the event is
        // derived from the signature.
        RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__ . '/../Classes/EventListener',
        ],
    ]);
};
