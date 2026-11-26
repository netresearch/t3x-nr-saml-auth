<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md — Classes (PHP Backend)

## Overview

Core PHP classes for SAML 2.0 SSO authentication in TYPO3. Uses `onelogin/php-saml` library for protocol handling.

**Key Components:**
- `Sv/AuthenticationService.php` — TYPO3 authentication service
- `Service/SamlService.php` — SAML protocol wrapper
- `EventListener/` — PSR-14 event listeners for login/logout
- `Middleware/DeepLinkSsoMiddleware.php` — Deep link SSO handling
- `Domain/` — Extbase models and repositories
- `Upgrades/` — upgrade wizard that moves the plugin from `list_type` to its own CType

## Setup & environment

```bash
composer install
```

**Requirements:** PHP 8.2+, TYPO3 13.4 or 14.3

## Build & tests

```bash
composer ci:test:php:cgl      # Check code style
composer ci:cgl               # Fix code style
composer ci:test:php:phpstan  # Static analysis
composer ci:test:php:unit     # Unit tests
```

## Code style & conventions

- **PSR-12** coding standard (enforced by PHP-CS-Fixer)
- **Strict types** in all files: `declare(strict_types=1);`
- **Readonly properties** for immutable data
- **Constructor property promotion** for DI
- **Final classes** unless inheritance is required
- **PHPStan level 10** compliance (`phpstan.neon`, shared config of `netresearch/typo3-ci-workflows`)

### Naming conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `AuthenticationService` |
| Methods | camelCase | `getUsername()` |
| Constants | UPPER_SNAKE | `DEFAULT_TIMEOUT` |
| Properties | camelCase | `$samlSettings` |

## Security & safety

- **Never log** SAML responses, tokens, or session data
- **Validate all** SAML attributes before use
- **Use parameterized queries** via Doctrine DBAL
- **Escape output** in templates (handled by Fluid)
- **No direct `$_POST`/`$_GET`** — use PSR-7 requests

## PR/commit checklist

- [ ] `composer ci:test:php:cgl` passes
- [ ] `composer ci:test:php:phpstan` passes
- [ ] Unit tests cover new logic
- [ ] No secrets or certificates in code
- [ ] Breaking changes documented

## Good vs. bad examples

### Dependency Injection

```php
// ✅ Good: Constructor injection with readonly
public function __construct(
    private readonly SamlService $samlService,
    private readonly SettingsRepository $settingsRepository,
) {}

// ❌ Bad: Direct instantiation
$service = new SamlService();
$service = GeneralUtility::makeInstance(SamlService::class);
```

### Type declarations

```php
// ✅ Good: Full type declarations
public function getUsername(array $samlAttributes): string
{
    return $samlAttributes['uid'][0] ?? '';
}

// ❌ Bad: Missing types
public function getUsername($samlAttributes)
{
    return $samlAttributes['uid'][0] ?? '';
}
```

### PSR-14 Events

```php
// ✅ Good: Listen to TYPO3 core events with the #[AsEventListener] attribute (see existing listeners)
#[AsEventListener(
    identifier: 'nr-saml-auth/before-user-logout',
    event: BeforeUserLogoutEvent::class,
)]
final class BeforeUserLogoutEventListener
```

```php
// ❌ Bad: Legacy hooks (removed)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nr_saml_auth']['beforeUserCreation']
```

Note: the extension currently only *listens* to TYPO3 core events (`Classes/EventListener/`); it does not dispatch events of its own — there is no `Classes/Event/` namespace.

## When stuck

- **TYPO3 API:** https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/
- **onelogin/php-saml:** https://github.com/onelogin/php-saml
- **Extension docs:** `Documentation/` folder

## House Rules

- All new classes must have corresponding unit tests
- EventListeners are registered with the `#[AsEventListener]` attribute, with an explicit `identifier` and `event`
- Inject `Psr\Log\LoggerInterface` via the constructor for logging (the pattern used across `Controller/` and `EventListener/`), not direct Logger instantiation
