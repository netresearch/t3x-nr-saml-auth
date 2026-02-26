<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-28 -->

# AGENTS.md (root)

**Precedence:** The **closest AGENTS.md** to changed files wins. Root holds global defaults only.

## Project

TYPO3 extension for SAML 2.0 Single Sign-On authentication. Supports TYPO3 12.4/13.4 LTS with PHP 8.1+.

## Global rules

- Keep PRs small (~≤300 net LOC)
- Conventional Commits: `type(scope): subject`
- Ask before: heavy deps, architectural changes, breaking changes
- Never commit secrets, certificates, or PII
- Follow TYPO3 CGL and PSR-12 coding standards

## Minimal pre-commit checks

```bash
composer ci:test:php:cgl          # PHP-CS-Fixer (dry-run)
composer ci:test:php:phpstan     # PHPStan level 6
composer ci:test:php:unit        # Unit tests
composer ci:test:php:functional  # Functional tests (requires typo3DatabaseDriver=pdo_sqlite)
```

## Index of scoped AGENTS.md

| Path | Purpose |
|------|---------|
| [`Classes/AGENTS.md`](Classes/AGENTS.md) | PHP backend code, services, controllers |
| [`Tests/AGENTS.md`](Tests/AGENTS.md) | Unit and functional testing |
| [`Documentation/AGENTS.md`](Documentation/AGENTS.md) | RST documentation for docs.typo3.org |

## When instructions conflict

Nearest AGENTS.md wins. User prompts override files.
