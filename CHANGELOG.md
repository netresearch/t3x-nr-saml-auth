<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [13.1.0] - 2026-11-26

### Security

- Over HTTPS the cookie that remembers the AuthnRequests is named `__Host-nr_saml_auth_request`, and only that name is read. Its value is signed but not bound to the browser, so an attacker who could set cookies for the site's host — from a sibling subdomain with `Domain=`, or with a plain-HTTP response where the host has no HSTS — could plant a cookie the site minted for the attacker's own login and then post the matching response (login CSRF). Browsers accept a `__Host-` cookie only from a secure origin, with `Secure`, `Path=/` and without `Domain`, which closes both routes. Over plain HTTP the cookie keeps the name `nr_saml_auth_request`.
- A response whose Response element has no `InResponseTo` while a `SubjectConfirmationData` of its assertion has one is no longer taken for an IdP-initiated login. Where the identity provider signs only the assertion, anybody could remove `InResponseTo` from the Response element of a response to their own login; with "Allow IdP-initiated login" on, the response then logged a visitor into that account, because php-saml compares the assertion's `InResponseTo` only with the one of the Response element. Such a response is now rejected.

### Deprecated

- `SamlService::getSsoRedirectUrl()` is deprecated and will be removed in 14.0.0. It does not remember the ID of the AuthnRequest in the browser, so the response to it has been rejected since 13.0.0. Use `SamlService::createSsoRedirect()`, which returns the URL and the cookie to send with the redirect, or `redirectUserToSSO()`.

### Fixed

- The 13.0.0 upgrade note gave the wrong TypoScript path for the plugin: it is rendered at `tt_content.nrsamlauth_authentication.20`, not `tt_content.nrsamlauth_authentication`.
- Added the missing dependency on `typo3/cms-backend`, which the backend module needs.
- The TER package no longer contains agent instructions and repository tooling (`AGENTS.md`, `CLAUDE.md`, `docs/`, `.gitleaks.toml`, `.bestpractices.json`, `crowdin.yml`, `renovate.json`, `packaging_exclude.php`).
- The documentation no longer describes PSR-14 events the extension does not have.

### Changed

- The documentation states that the extension supports Composer installations only. The TER package does not contain `onelogin/php-saml`, so an installation without Composer does not work.
- Language files use XLIFF 1.2 with 2-space indentation.

### Removed

- The legacy `Documentation/Settings.cfg`; `Documentation/guides.xml` carries its settings.
- `Resources/Private/Libs/Build`, an unused build definition of the SAML library for installations without Composer.

### Upgrade

- Logins started before the update and finished after it fail once over HTTPS, because the cookie name changes; the user starts the login again.
- As since 13.0.0, the cookie is host-only: the page with the plugin that starts the login and the Assertion Consumer Service URL must be on the same host. Otherwise the browser does not send the cookie with the response and the login is rejected.

## [13.0.0] - 2026-11-26

### Security

- Reject SAML responses that do not answer an AuthnRequest this browser started (login CSRF). Before, any valid signed response posted to the Assertion Consumer Service logged the browser in, so somebody with an account at the identity provider could log a visitor into that account. The extension now remembers the ID of each AuthnRequest in the cookie `nr_saml_auth_request` (`SameSite=None; Secure; HttpOnly`, HMAC-protected, 10 minutes, up to five pending logins) and has php-saml check the response's `InResponseTo` against it.
- IdP-initiated login (a response without `InResponseTo`) is rejected unless the new settings record field "Allow IdP-initiated login" (`allow_idp_initiated`) is on. It is off by default.

### Upgrade

- Run the database schema update: it adds the column `allow_idp_initiated` to `tx_nrsamlauth_domain_model_settings` with the value 0 for every existing record. If your identity provider starts logins itself (for example from an application portal), switch "Allow IdP-initiated login" on in the settings record; otherwise those logins are rejected after the update.
- SP-initiated login needs the Assertion Consumer Service URL on HTTPS; over plain HTTP the browser does not send the cookie back with the identity provider's cross-site POST.
- A response is accepted only for the settings record that started the login. The record the plugin selects must be the one the Assertion Consumer Service URL resolves to (its `saml_id` parameter, else the record whose SP entity ID matches the host, else uid 1); otherwise the login is now rejected.

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

## [12.0.1] - 2026-11-26

### Security

- Reject SAML responses that do not answer an AuthnRequest this browser started (login CSRF). Before, any valid signed response posted to the Assertion Consumer Service logged the browser in, so somebody with an account at the identity provider could log a visitor into that account. The extension now remembers the ID of each AuthnRequest in the cookie `nr_saml_auth_request` (`SameSite=None; Secure; HttpOnly`, HMAC-protected, 10 minutes, up to five pending logins) and has php-saml check the response's `InResponseTo` against it.
- IdP-initiated login (a response without `InResponseTo`) is rejected unless the new settings record field "Allow IdP-initiated login" (`allow_idp_initiated`) is on. It is off by default.

### Upgrade

- Run the database schema update: it adds the column `allow_idp_initiated` to `tx_nrsamlauth_domain_model_settings` with the value 0 for every existing record. If your identity provider starts logins itself (for example from an application portal), switch "Allow IdP-initiated login" on in the settings record; otherwise those logins are rejected after the update.
- SP-initiated login needs the Assertion Consumer Service URL on HTTPS; over plain HTTP the browser does not send the cookie back with the identity provider's cross-site POST.
- A response is accepted only for the settings record that started the login. The record the plugin selects must be the one the Assertion Consumer Service URL resolves to (its `saml_id` parameter, else the record whose SP entity ID matches the host, else uid 1); otherwise the login is now rejected.

### Fixed

- The plugin's FlexForm field that selects the SAML settings record had no label and no configuration on TYPO3 13.4: the FlexForm still wrapped it in `<TCEforms>`, which TYPO3 13 no longer reads.
- The backend module Admin Tools > SAML Auth failed to render on TYPO3 12.4 and 13.4: its layout used `<be:moduleLayout>`, which TYPO3 12 removed. The module now renders through the backend's ModuleTemplate API.

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
