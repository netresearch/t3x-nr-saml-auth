# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [13.0.0] - Unreleased

### Added

- TYPO3 v12.4 and v13.4 LTS support
- PHP 8.1, 8.2, 8.3, and 8.4 support
- PSR-14 event system for authentication hooks
  - `AfterUserLoggedInEventListener` - replaces `postUserLookUp` hook
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
