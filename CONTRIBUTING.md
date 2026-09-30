<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Contributing to TYPO3 SAML Auth

Thank you for your interest in contributing to this TYPO3 extension!

## Code of Conduct

This project follows the [TYPO3 Code of Conduct](https://typo3.org/community/code-of-conduct). By participating, you agree to uphold this code.

## How to Contribute

### Reporting Bugs

1. Check [existing issues](https://github.com/netresearch/t3x-nr-saml-auth/issues) to avoid duplicates
2. Use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.yml)
3. Include:
   - TYPO3 and PHP versions
   - Steps to reproduce
   - Expected vs actual behavior
   - Relevant logs or error messages

### Suggesting Features

1. Open a [feature request](.github/ISSUE_TEMPLATE/feature_request.yml)
2. Describe the use case and expected benefit
3. Consider backwards compatibility

### Submitting Pull Requests

#### Setup

```bash
# Clone the repository
git clone https://github.com/netresearch/t3x-nr-saml-auth.git
cd t3x-nr-saml-auth

# Install dependencies
composer install

# Run tests to verify setup
composer ci
```

#### Development Workflow

1. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**
   - Follow [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/)
   - Use strict types: `declare(strict_types=1);`
   - Prefer constructor property promotion
   - Use readonly properties where applicable

3. **Run quality checks**
   ```bash
   # Full CI pipeline
   composer ci

   # Or individual checks
   composer ci:test:php:cgl      # Code style
   composer ci:test:php:phpstan  # Static analysis
   composer ci                   # All checks
   ```

4. **Commit your changes**
   ```bash
   # Use conventional commits
   git commit -m "feat: add new SAML attribute mapping"
   git commit -m "fix: resolve session timeout issue"
   git commit -m "docs: update configuration reference"
   ```

5. **Push and create PR**
   ```bash
   git push origin feature/your-feature-name
   ```
   Then open a Pull Request on GitHub.

#### Commit Message Format

We follow [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `refactor:` Code refactoring
- `test:` Adding or updating tests
- `chore:` Maintenance tasks

#### Code Standards

- **PHP**: PSR-12 + TYPO3 CGL (enforced by PHP-CS-Fixer)
- **Static Analysis**: PHPStan level 8
- **Testing**: PHPUnit 10+ with TYPO3 Testing Framework
- **Documentation**: reStructuredText (RST)

### Testing

#### Unit Tests

```bash
composer ci:test:php:unit
```

#### Functional Tests

```bash
composer ci:test:php:functional
```

Tests use SQLite by default. For MySQL testing, configure `typo3DatabaseDriver`.

### Documentation

Documentation uses reStructuredText and follows [TYPO3 Documentation Standards](https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/).

To preview locally:

```bash
make docs        # Render documentation
make docs-serve  # Serve at http://localhost:8000
```

## Questions?

- Open a [GitHub Discussion](https://github.com/netresearch/t3x-nr-saml-auth/discussions)
- Join [TYPO3 Slack](https://typo3.slack.com) #typo3-extensions

## Governance and policies

This extension follows the organisation-wide Netresearch policies:

- [Governance](https://github.com/netresearch/.github/blob/main/GOVERNANCE.md): ownership, roles, how decisions are made and conflicts resolved.
- [Roadmap](https://github.com/netresearch/.github/blob/main/ROADMAP.md): planned and excluded work for the next twelve months.
- [Handling of dependency and code analysis findings](https://github.com/netresearch/.github/blob/main/SECURITY.md#handling-of-dependency-and-code-analysis-findings): which vulnerability, licence and static-analysis findings must be fixed, by when, and how exceptions are recorded.
- [Secret management](https://github.com/netresearch/.github/blob/main/SECURITY.md#secret-management): where CI and release credentials are stored, who may use them, how committed secrets are detected, and when secrets are rotated.
- [Access roster](https://github.com/netresearch/.github/blob/main/docs/access-roster.md): the people and teams with administrative or write access to this repository.

Checks that run on every pull request in this repository:

- `.github/workflows/checks.yml`: Composer Audit (fails on any advisory for an installed package) and Opengrep SAST (fails a pull request as the [organisation rule](https://github.com/netresearch/.github/blob/main/SECURITY.md#static-analysis-sast) sets out), both through `typo3-ci-workflows`' `security.yml`; Dependency Review (fails on newly added dependencies with a vulnerability of severity high or higher); PHP License Audit (`license-check.yml`, fails on an SSPL or BSL licensed Composer dependency); CodeQL for the workflow files (the repository contains no JavaScript, and CodeQL has no PHP analysis; PHPStan and Opengrep cover the PHP code); Betterleaks secret scanning; zizmor for the workflow files; the pull request quality check (`pr-quality`); the aggregate gate `All security checks`, which fails when any of these jobs fails. The fuzz job finds no fuzz suite in `Build/phpunit/` and is skipped.
- `.github/workflows/ci.yml`: PHP lint, code style (PHP-CS-Fixer, `.php-cs-fixer.php`), PHPStan (level 8, `phpstan.neon`, and, advisory by default, once more against the newest PHPUnit as `PHPStan (unpinned PHPUnit)`), Rector (`Build/rector.php`), unit tests and functional tests (SQLite) on PHP 8.1 to 8.5 with TYPO3 12.4 and 13.4 (PHP 8.1 only with 12.4, PHP 8.5 only with 13.4), and the rendering of `Documentation/`, summarised by the aggregate gate `ci / All CI checks`.
- `.github/workflows/harness-verify.yml`: `Build/Scripts/verify-harness.sh`.
- `.github/workflows/check-template-drift.yml`: drift of the workflow files from the `netresearch/.github` typo3-extension template.

The security expectations, trust boundaries and the code behind them are in [docs/SECURITY-ASSURANCE.md](docs/SECURITY-ASSURANCE.md).

## License

By contributing, you agree that your contributions will be licensed under GPL-2.0-or-later.

## Commit Signing

All commits must be cryptographically signed and carry a DCO sign-off: `git commit -S --signoff`. The `require-signed-commits` ruleset on the default branch enforces the signature (the "Verified" badge on GitHub); the DCO check enforces the `Signed-off-by` trailer — these are two different things and both are required. Quickest setup is SSH signing: register your SSH key as a *signing key* on your GitHub account, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.
