<?php

declare(strict_types=1);

$config = \TYPO3\CodingStandards\CsFixerConfig::create();

// TYPO3 coding-standards fully qualifies global classes (import_classes =>
// false), while the shared org Rector config calls importNames() and imports
// them. Left as-is the two tools undo each other on every run. The org
// PHP-CS-Fixer baseline (netresearch/typo3-ci-workflows
// config/php-cs-fixer/rules.php) imports, so align with that.
$config->addRules([
    'global_namespace_import' => [
        'import_classes'   => true,
        'import_constants' => true,
        'import_functions' => true,
    ],
]);

$config->setFinder(
    (new PhpCsFixer\Finder())
        ->ignoreVCSIgnored(true)
        ->in(__DIR__)
        ->exclude([
            '.Build',
            '.github',
            'Resources/Private/Libs',
            'var',
        ])
        ->notPath([
            'ext_emconf.php',
        ])
);

return $config;
