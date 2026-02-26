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

## License

By contributing, you agree that your contributions will be licensed under GPL-2.0-or-later.
