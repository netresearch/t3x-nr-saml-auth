<?php

/*
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
    // Shared org base config: code-quality sets, rule skips, phpstan-rector.neon
    $configure($rectorConfig, __DIR__ . '/..');

    // The extension requires php ^8.1 and CI runs a PHP 8.1 cell, so the shared
    // default of 8.2 must be lowered: it lets rules emit 8.2-only syntax
    // (ReadOnlyClassRector turns the event listeners into `final readonly class`),
    // which is a parse error on 8.1.
    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    // paths() replaces the shared list — re-declared to keep Tests/ in scope,
    // which the shared $projectRoot default leaves out.
    $rectorConfig->paths(array_merge(
        [
            __DIR__ . '/../Classes',
            __DIR__ . '/../Configuration',
            __DIR__ . '/../Resources',
            __DIR__ . '/../Tests',
        ],
        glob(__DIR__ . '/../ext_*.php') ?: [],
    ));

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
