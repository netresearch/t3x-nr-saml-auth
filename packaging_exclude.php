<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

/*
 * Exclude list for the TER artefact, passed to `tailor ter:publish` through
 * the `exclude-from-packaging` input of the release workflow
 * (TYPO3_EXCLUDE_FROM_PACKAGING).
 *
 * tailor zips the working directory and never reads .gitattributes, so
 * `export-ignore` does not reach the TER zip. This file REPLACES tailor's
 * conf/ExcludeFromPackaging.php instead of merging with it, so the first part
 * of each list restates the defaults of typo3/tailor 1.7.0.
 *
 * Matching (tailor VersionService::createZipArchiveFromPath()):
 *   directories  preg_match('/^' . $entry . '/i', $relativePath)
 *   files        preg_match('/' . $entry . '$/i', $basename)
 * Entries are not quoted: "." is a wildcard, "/" must be written as "\/".
 */

return [
    'directories' => [
        // typo3/tailor 1.7.0 defaults
        '.build',
        '.ddev',
        '.git',
        '.github',
        '.gitlab',
        '.gitlab-ci',
        '.idea',
        '.phive',
        'bin',
        'build',
        'public',
        'tailor-version-artefact',
        'tailor-version-upload',
        'tests',
        'tools',
        'vendor',
        // nr_saml_auth
        // Agent-facing notes (docs/ARCHITECTURE.md, docs/exec-plans/). Root-anchored
        // prefix: "docs" does not match "Documentation".
        'docs',
        // Changelog fragments; the release commit collects them into CHANGELOG.md.
        'changelog\.d',
        // Local docs render output; the manual is published on docs.typo3.org.
        'Documentation-GENERATED-temp',
        // Kept from the previous list of this file.
        'resources\\/private\\/frontend',
    ],
    'files' => [
        // typo3/tailor 1.7.0 defaults
        'CODE_OF_CONDUCT.md',
        'DS_Store',
        'Dockerfile',
        'ExtensionBuilder.json',
        'Makefile',
        'bower.json',
        'codeception.yml',
        'composer.lock',
        'crowdin.yaml',
        'docker-compose.yml',
        'dynamicReturnTypeMeta.json',
        'editorconfig',
        'env',
        'eslintignore',
        'eslintrc.json',
        'gitattributes',
        'gitignore',
        'gitlab-ci.yml',
        'gitmodules',
        'gitreview',
        'package-lock.json',
        'package.json',
        'phive.xml',
        'php-cs-fixer.dist.php',
        'php-cs-fixer.php',
        'php_cs',
        'php_cs.php',
        'phpcs.xml',
        'phpcs.xml.dist',
        'phplint.yml',
        'phpstan-baseline.neon',
        'phpstan.neon',
        'phpstan.neon.dist',
        'phpstorm.meta.php',
        'phpunit.xml',
        'phpunit.xml.dist',
        'prettierrc.json',
        'rector.php',
        'scrutinizer.yml',
        'styleci.yml',
        'stylelint.config.js',
        'stylelintrc',
        'travis.yml',
        'tslint.yaml',
        'tslint.yml',
        'typoscript-lint.yaml',
        'typoscript-lint.yml',
        'typoscriptlint.yaml',
        'typoscriptlint.yml',
        'webpack.config.js',
        'webpack.mix.js',
        'yarn.lock',
        // nr_saml_auth
        // Agent instructions. The basename match also catches the scoped copies
        // in Classes/, Documentation/ and Tests/.
        'AGENTS.md',
        'CLAUDE.md',
        // Repository tooling with no runtime meaning in an installed extension.
        'bestpractices.json',
        'gitleaks.toml',
        // tailor 1.7.0 knows only the crowdin.yaml spelling.
        'crowdin.yml',
        'renovate.json',
        'packaging_exclude.php',
    ],
];
