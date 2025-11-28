# Netresearch TYPO3 SAML Auth

[![CI](https://github.com/netresearch/t3x-nr-saml-auth/actions/workflows/ci.yml/badge.svg)](https://github.com/netresearch/t3x-nr-saml-auth/actions/workflows/ci.yml)
[![TYPO3](https://img.shields.io/badge/TYPO3-12.4%20|%2013.4-orange.svg)](https://typo3.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue.svg)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](https://phpstan.org/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

> TYPO3 extension for SAML SSO authentication supporting frontend and backend users using the `onelogin/php-saml` library.

## Requirements

| Version | TYPO3       | PHP        |
|---------|-------------|------------|
| 12.x    | 12.4, 13.4  | 8.1 - 8.4  |
| 10.x    | 10.4, 11.5  | 7.4 - 8.1  |

## Installation

Install via Composer:

```bash
composer require netresearch/nr-saml-auth
```

## Configuration

### Backend Setup

1. Create a new **SAML Auth Settings** record on the root page in the TYPO3 backend
2. Configure the Service Provider (SP) and Identity Provider (IdP) settings

### Example Configuration

```
# Service Provider Settings
Entity ID: https://your-domain.tld
Customer service URL: https://your-domain.tld/?logintype=login
Customer service binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST
Name ID format: urn:oasis:names:tc:SAML:2.0:nameid-format:transient

Certificate: -----BEGIN CERTIFICATE-----
MIIFYDCCA0igAwIBAgIJAMWkGz7F5peWMA0GCSqGSIb3DQEB...
-----END CERTIFICATE-----

Private key: -----BEGIN PRIVATE KEY-----
MIIJQwIBADANBgkqhkiG9w0BAQEFAASCCS0wggkpAgEAAoIC...
-----END PRIVATE KEY-----

# Identity Provider Settings
Entity ID: urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress
SSO URL: https://idp.example.com/sso
Binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect
Certificate: [IDP Certificate]

# User Settings
Username prefix: sso-
User folder: [Select frontend user folder]
User groups: [Select default frontend user groups]
```

### Testing with SimpleSAMLphp

For development/testing, you can use [SimpleSAMLphp](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html) or online SAML testing tools like [samling](https://capriza.github.io/samling/samling.html).

## Features

### Auto Discovery

The login service automatically detects the SAML configuration for the current request based on the `sp_entity_id` matching your domain.

### Deep Link Support (Middleware)

The extension includes middleware for redirecting users to their original destination after login/logout:

- The `RelayState` parameter should contain the target URL
- Transmitted via POST (login) or GET (logout) from SAML server to the configured ACS URLs

### Backend Module

Access SAML metadata via the **Admin Tools > SAML Auth** backend module to configure your IdP.

## Upgrading

### From 10.x to 12.x

Version 12.x includes breaking changes:

- **PHP 8.1+ required**: Upgrade your PHP version
- **TYPO3 12.4+ required**: Upgrade your TYPO3 installation
- **onelogin/php-saml 4.0**: Library upgraded with security improvements
- **PSR-14 Events**: Legacy hooks replaced with modern event system
- **Dependency Injection**: Services now use TYPO3 DI container

No database migrations required.

## Development

### Quality Tools

```bash
# Install dependencies
composer install

# Run all CI checks
composer ci

# Individual checks
composer ci:phpstan      # Static analysis
composer ci:cgl          # Code style check
composer ci:cgl:fix      # Code style fix
composer ci:tests:unit   # Unit tests
```

## License

This extension is proprietary software by Netresearch DTT GmbH.

## Support

For issues and feature requests, please use the [GitHub issue tracker](https://github.com/netresearch/t3x-nr-saml-auth/issues).
