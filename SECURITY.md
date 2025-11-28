# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 12.x    | :white_check_mark: |
| 10.x    | :x:                |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

### Responsible Disclosure

If you discover a security vulnerability in this extension, please report it responsibly:

1. **Email**: Send details to [security@netresearch.de](mailto:security@netresearch.de)
2. **Subject**: `[SECURITY] TYPO3 nr_saml_auth - Brief description`
3. **Include**:
   - Type of vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### Response Timeline

- **Initial Response**: Within 48 hours
- **Assessment**: Within 7 days
- **Fix Timeline**: Depends on severity
  - Critical: 24-48 hours
  - High: 7 days
  - Medium: 30 days
  - Low: Next release

### What to Expect

1. **Acknowledgment**: We'll confirm receipt of your report
2. **Investigation**: We'll assess the vulnerability
3. **Updates**: We'll keep you informed of progress
4. **Credit**: With your permission, we'll credit you in the release notes

## Security Best Practices

When using this extension:

### Certificate Management

- Use strong key sizes (RSA 2048+ or ECDSA P-256+)
- Rotate certificates before expiration
- Store private keys securely (not in web-accessible directories)
- Use environment variables for sensitive configuration in CI/CD

### SAML Configuration

- Enable strict mode in production (`strictMode = 1`)
- Validate XML signatures (`validateXml = 1`)
- Use HTTPS for all SAML endpoints
- Configure proper audience restrictions
- Implement session timeout policies

### TYPO3 Hardening

- Keep TYPO3 and all extensions updated
- Follow [TYPO3 Security Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Security/)
- Restrict backend access to authorized IPs
- Enable Content Security Policy headers

## Known Security Considerations

### Session Handling

This extension uses TYPO3's session management. Ensure your TYPO3 installation has secure session settings:

```php
$GLOBALS['TYPO3_CONF_VARS']['FE']['lockIP'] = 2;
$GLOBALS['TYPO3_CONF_VARS']['FE']['sessionDataLifetime'] = 86400;
```

### Relay State Validation

The deep link middleware validates RelayState URLs against the current domain. Custom URL validation can be implemented via PSR-14 events.

## Dependencies

This extension uses [onelogin/php-saml](https://github.com/onelogin/php-saml) v4.x. Security updates are tracked via Renovate and applied promptly.

## Changelog

Security-related changes are documented in [CHANGELOG.md](CHANGELOG.md) under the "Security" section.
