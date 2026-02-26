<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-28 -->

# AGENTS.md — Tests

## 1. Overview

PHPUnit 10+ test suite with unit and functional tests for SAML authentication.

**Structure:**
- `Unit/` — Fast, isolated tests (no database, no TYPO3 bootstrap)
- `Functional/` — Integration tests with TYPO3 framework
- `Functional/Helper/` — SAML testing utilities
- `Functional/Fixtures/` — Test data and SAML response samples

## 2. Setup & environment

```bash
composer install
```

**For functional tests:** SQLite (no external database needed)

## 3. Build & tests

```bash
# Unit tests (fast, ~50ms)
composer ci:test:php:unit

# Functional tests (requires database driver)
typo3DatabaseDriver=pdo_sqlite composer ci:test:php:functional

# All tests
typo3DatabaseDriver=pdo_sqlite composer ci
```

## 4. Code style & conventions

- **PHPUnit 10+ attributes** (not annotations)
- **#[Test]** attribute on test methods
- **#[DataProvider]** for parameterized tests
- **self::assert*()** not `$this->assert*()`
- **Descriptive method names:** `testMethodNameDoesExpectedBehavior`

### File naming

| Type | Pattern | Example |
|------|---------|---------|
| Unit test | `*Test.php` | `AuthenticationServiceTest.php` |
| Functional test | `*Test.php` | `SettingsRepositoryTest.php` |
| Data provider | `*DataProvider` | `samlAttributeDataProvider` |

## 5. Security & safety

- **Never use real SAML responses** in fixtures
- **Mock external services** (IdP, certificates)
- **Use fake credentials** in test data
- **Clean up** test database after tests

## 6. PR/commit checklist

- [ ] All new code has test coverage
- [ ] Tests pass locally: `composer ci`
- [ ] No skipped or incomplete tests without issue reference
- [ ] Fixtures use fake/mock data only

## 7. Good vs. bad examples

### Test structure

```php
// ✅ Good: PHPUnit 10 attributes
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

final class AuthenticationServiceTest extends UnitTestCase
{
    #[Test]
    public function authUserReturnsSuccessCode(): void
    {
        $result = $this->subject->authUser(['uid' => 1]);
        self::assertSame(200, $result);
    }

    #[Test]
    #[DataProvider('attributeDataProvider')]
    public function getValueHandlesVariousTypes(array $input, string $expected): void
    {
        // ...
    }
}

// ❌ Bad: Legacy annotations
/** @test */
public function testAuthUser()
{
    $this->assertEquals(200, $this->subject->authUser(['uid' => 1]));
}
```

### SAML response testing

```php
// ✅ Good: Use SamlResponseBuilder
use Netresearch\NrSamlAuth\Tests\Functional\Helper\SamlResponseBuilder;

$response = SamlResponseBuilder::validResponse()
    ->withNameId('user@example.com')
    ->withAttributes(['groups' => ['admins']])
    ->build();

// ❌ Bad: Hardcoded XML strings
$response = '<?xml version="1.0"?><samlp:Response>...</samlp:Response>';
```

### Mocking

```php
// ✅ Good: Use test doubles for external services
$mockIdp = MockIdpProvider::createWithTestUsers();
$samlResponse = $mockIdp->authenticate('user@example.com', $acsUrl, $spEntityId);

// ❌ Bad: Calling real external services
$response = file_get_contents('https://real-idp.com/sso');
```

## 8. When stuck

- **TYPO3 Testing Framework:** https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Testing/
- **PHPUnit 10 docs:** https://docs.phpunit.de/en/10.5/
- **Test helpers:** `Tests/Functional/Helper/` classes

## 9. House Rules

- **Unit tests:** No database, no file I/O, mock all dependencies
- **Functional tests:** Use `FunctionalTestCase`, `pdo_sqlite` driver
- **SAML fixtures:** Use `SamlResponseBuilder`, not raw XML
- **Coverage goal:** All public methods should have tests
