<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-28 -->

# AGENTS.md — Classes (PHP Backend)

## 1. Overview

Core PHP classes for SAML 2.0 SSO authentication in TYPO3. Uses `onelogin/php-saml` library for protocol handling.

**Key Components:**
- `Sv/AuthenticationService.php` — TYPO3 authentication service
- `Service/SamlService.php` — SAML protocol wrapper
- `EventListener/` — PSR-14 event listeners for login/logout
- `Middleware/DeepLinkSsoMiddleware.php` — Deep link SSO handling
- `Domain/` — Extbase models and repositories

## 2. Setup & environment

```bash
composer install
```

**Requirements:** PHP 8.1+, TYPO3 12.4+ or 13.4+

## 3. Build & tests

```bash
composer ci:cgl          # Check code style
composer ci:cgl:fix      # Fix code style
composer ci:phpstan      # Static analysis
composer ci:tests:unit   # Unit tests
```

## 4. Code style & conventions

- **PSR-12** coding standard (enforced by PHP-CS-Fixer)
- **Strict types** in all files: `declare(strict_types=1);`
- **Readonly properties** for immutable data
- **Constructor property promotion** for DI
- **Final classes** unless inheritance is required
- **PHPStan level 6** compliance

### Naming conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `AuthenticationService` |
| Methods | camelCase | `getUsername()` |
| Constants | UPPER_SNAKE | `DEFAULT_TIMEOUT` |
| Properties | camelCase | `$samlSettings` |

## 5. Security & safety

- **Never log** SAML responses, tokens, or session data
- **Validate all** SAML attributes before use
- **Use parameterized queries** via Doctrine DBAL
- **Escape output** in templates (handled by Fluid)
- **No direct `$_POST`/`$_GET`** — use PSR-7 requests

## 6. PR/commit checklist

- [ ] `composer ci:cgl` passes
- [ ] `composer ci:phpstan` passes
- [ ] Unit tests cover new logic
- [ ] No secrets or certificates in code
- [ ] Breaking changes documented

## 7. Good vs. bad examples

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
// ✅ Good: Use PSR-14 events
$this->eventDispatcher->dispatch(new BeforeUserCreationEvent($userData));

// ❌ Bad: Legacy hooks (removed)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nr_saml_auth']['beforeUserCreation']
```

## 8. When stuck

- **TYPO3 API:** https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/
- **onelogin/php-saml:** https://github.com/onelogin/php-saml
- **Extension docs:** `Documentation/` folder

## 9. House Rules

- All new classes must have corresponding unit tests
- EventListeners must be registered in `Configuration/Services.yaml`
- Use `LoggerAwareTrait` for logging, not direct Logger instantiation
