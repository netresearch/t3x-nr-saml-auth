<?php

declare(strict_types=1);

$config = \TYPO3\CodingStandards\CsFixerConfig::create();

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
