<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md (root)

**Precedence:** The **closest AGENTS.md** to changed files wins. Root holds global defaults only.

## Project

TYPO3 extension for SAML 2.0 Single Sign-On authentication (extension key `nr_saml_auth`, version: see `ext_emconf.php`). Supports TYPO3 13.4/14.3 LTS with PHP 8.2+ (TYPO3 12.4 is maintained on the `TYPO3_12` branch). Component map: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md); execution plans: [`docs/exec-plans/`](docs/exec-plans/README.md).

## Global rules

- Keep PRs small (~≤300 net LOC)
- Conventional Commits: `type(scope): subject`
- Ask before: heavy deps, architectural changes, breaking changes
- Never commit secrets, certificates, or PII
- Follow TYPO3 CGL and PSR-12 coding standards

## Commands

Minimal pre-commit checks (`composer ci` runs all five):

```bash
composer ci:test:php:cgl         # PHP-CS-Fixer (dry-run; fix with composer ci:cgl)
composer ci:test:php:phpstan     # PHPStan level 10 (shared config)
composer ci:test:php:rector      # Rector (dry-run, shared org config via Build/rector.php)
composer ci:test:php:unit        # Unit tests
composer ci:test:php:functional  # Functional tests (requires typo3DatabaseDriver=pdo_sqlite)
composer ci:test:changelog       # Changelog fragments in changelog.d/ (also run by ci:test:php:cgl)
```

Harness self-check: `bash Build/Scripts/verify-harness.sh` (also runs in CI via `.github/workflows/harness-verify.yml`).

## Changelog

- Changes are recorded in [`CHANGELOG.md`](CHANGELOG.md), [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/): `## [X.Y.Z] - YYYY-MM-DD`, newest first, below `## [Unreleased]`.
- A feature or fix PR writes its entry as a fragment `changelog.d/<branch-slug>.<section>.md` ([format](changelog.d/README.md)) instead of editing `CHANGELOG.md`; `composer ci:test:changelog` checks it, and CI runs it through `composer ci:test:php:cgl`.
- The release PR runs `composer changelog:collect`, which moves the fragments into `## [Unreleased]`, and then renames that section to the version.

## Index of scoped AGENTS.md

| Path | Purpose |
|------|---------|
| [`Classes/AGENTS.md`](./Classes/AGENTS.md) | PHP backend code, services, controllers |
| [`Tests/AGENTS.md`](./Tests/AGENTS.md) | Unit and functional testing |
| [`Documentation/AGENTS.md`](./Documentation/AGENTS.md) | RST documentation for docs.typo3.org |

## When instructions conflict

Nearest AGENTS.md wins. User prompts override files.

## Commit Signing

Signed commits are required: `git commit -S --signoff`. The `require-signed-commits` ruleset on the default branch rejects unsigned commits at merge time, and the DCO check additionally requires the `Signed-off-by` trailer. Quickest setup is SSH signing — register your SSH key as a *signing key* on your GitHub account, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.
