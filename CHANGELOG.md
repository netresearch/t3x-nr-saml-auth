<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [13.0.0] - Unreleased

### Added

- Upgrade wizard `nrSamlAuth_pluginListTypeToCType` that migrates content elements of the "Insert Plugin" type with the plugin `nrsamlauth_authentication`, and the backend user group permissions for them, to the new content element type

### Changed

- **BREAKING**: TYPO3 12.4 and PHP 8.1 no longer supported; TYPO3 14.3 supported. Version 12.x stays available for TYPO3 12.4
- **BREAKING**: The plugin is a content element type of its own (`CType` `nrsamlauth_authentication`) instead of a `list_type` subtype of the "Insert Plugin" content element; run the upgrade wizard after updating. TypoScript referring to `tt_content.list.20.nrsamlauth_authentication` has to refer to `tt_content.nrsamlauth_authentication`
- The static TypoScript template is registered in `Configuration/TCA/Overrides/sys_template.php`; `ext_tables.php` is removed
- The event listeners are registered by their `#[AsEventListener]` attribute only

### Fixed

- The FlexForm of the plugin shows the field for the SAML settings record again: the `<TCEforms>` wrapper, which TYPO3 13 no longer reads, is removed
- The backend module "Admin Tools > SAML Auth" renders again: it uses the backend `ModuleTemplate` instead of the `<be:moduleLayout>` ViewHelper, which TYPO3 12 removed

### Known deprecations

- Deprecated on TYPO3 14.3 and kept while TYPO3 13.4 is supported: the array form of `#[Validate]` in `Settings`, the EXT:install names of the upgrade wizard API, `ExtensionManagementUtility::addPiFlexFormValue()` (called on TYPO3 13.4 only), and `ext_emconf.php` without `version`/`providesPackages` in `composer.json` (#108345)

## [12.0.0] - 2026-11-26

### Added

- TYPO3 v12.4 and v13.4 LTS support
- PHP 8.1, 8.2, 8.3, 8.4, and 8.5 support, and PHP 8.6 with TYPO3 13.4
- PSR-14 event system for authentication hooks
  - `SingleLogoutSessionMiddleware` - stores the single logout data in the frontend session after a login
  - `BeforeUserLogoutEventListener` - replaces `logoff_pre_processing` hook
  - `AfterUserLoggedOutEventListener` - replaces `logoff_post_processing` hook
- Modern dependency injection via `Configuration/Services.yaml`
- Backend module registration via `Configuration/Backend/Modules.php`
- Icon registration via `Configuration/Icons.php`
- Comprehensive unit test suite (55+ tests)
- PHPStan static analysis (level 6)
- PHP-CS-Fixer with TYPO3 coding standards
- GitHub Actions CI pipeline with matrix testing

### Changed

- **BREAKING**: Minimum PHP version is now 8.1
- **BREAKING**: Minimum TYPO3 version is now 12.4
- **BREAKING**: `onelogin/php-saml` upgraded from ^3.0 to ^4.0
- Controllers now use constructor property promotion
- All classes use `declare(strict_types=1)`
- Middleware uses PSR-7 request objects (no `$_POST` superglobals)
- Repository uses `createQuery()->getQuerySettings()` instead of direct instantiation

### Removed

- **BREAKING**: Legacy hook classes (`Classes/Hooks/`)
  - `LogOffHook` - replaced by PSR-14 event listeners
  - `PostUserLookup` - replaced by PSR-14 event listeners
- `ObjectManager` usage (replaced with dependency injection)
- `TYPO3_MODE` constant checks (replaced with `TYPO3`)
- Support for TYPO3 v10 and v11 (use version 10.x branch)
- Support for PHP 7.x (use version 10.x branch)

### Fixed

- The SessionIndex of a LogoutRequest is escaped once. php-saml 4.6 escapes it itself, so the extension's own escaping made a SessionIndex with `<`, `&` or `"` reach the identity provider escaped twice; php-saml is now required at `^4.6`.
- Repository initialization using proper query settings factory
- Middleware properly uses PSR-7 request body parsing

### Security

- Updated `onelogin/php-saml` to v4.0 with security improvements
- Removed usage of superglobal variables

## [10.0.10] - 2024-11-01

### Changed

- Update actions/checkout to v6
- Extension icon changed to Netresearch logo

## [10.0.9] and earlier

See git history for previous changes.
