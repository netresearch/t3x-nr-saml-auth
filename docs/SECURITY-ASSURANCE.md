<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Security assurance

This document states what users of `netresearch/nr-saml-auth` can and cannot expect in terms of security, where the extension's trust boundaries are, and which code counters the weaknesses that matter for it. It describes the code on `main` and the SAML processing of the frontend login in `AuthenticationService::getUser()` and `SamlService`, the backend module and the settings records; when this file and the code disagree, the code wins and this file is corrected. Vulnerabilities are reported as described in [SECURITY.md](../SECURITY.md), not in public issues. The component map is in [ARCHITECTURE.md](ARCHITECTURE.md).

The SAML protocol, the XML parsing and the cryptography are done by [onelogin/php-saml](https://github.com/SAML-Toolkits/php-saml) (`composer.json` requires `^4.3.1`; the line references below are to 4.3.2) and its dependency `robrichards/xmlseclibs`. The extension's own code builds the library settings from a database record, hands the posted response to the library, and maps the result onto a TYPO3 frontend user.

## What the extension does, security-wise

| Entry point | Who can reach it | Input | Code |
|-------------|------------------|-------|------|
| Frontend plugin `NrSamlAuth` / `Authentication` | Any visitor of a page carrying the plugin | None; an anonymous visitor is redirected to the IdP with an AuthnRequest | `ext_localconf.php`, `Classes/Controller/AuthController.php`, `Classes/Service/SamlService.php` (`redirectUserToSSO()`) |
| Assertion Consumer Service: a frontend login request (`logintype=login`) with a `SAMLResponse` POST field | Any client | The base64-encoded SAML response, `saml_id` query parameter, `RelayState` | TYPO3 frontend authentication, `Classes/Sv/AuthenticationService.php` (`getUser()` and `authUser()`, registered for `getUserFE`, `authUserFE` and `authUserBE` in `ext_localconf.php`), `Classes/Middleware/DeepLinkSsoMiddleware.php` |
| After a frontend login and before and after a logout | Logged-in frontend users | The SAML response of the login request | `Classes/EventListener/`, `Classes/Session/SamlSession.php` |
| Backend module Admin Tools > SAML Auth | Backend users; the module is registered with `'access' => 'systemMaintainer'` | Uid of a settings record | `Configuration/Backend/Modules.php`, `Classes/Controller/SamlAuthController.php` |
| Settings records `tx_nrsamlauth_domain_model_settings` | Backend users; TYPO3 table permissions apply | SP and IdP entity IDs, URLs, bindings, certificates, SP private key, user storage folder and user groups | `Configuration/TCA/tx_nrsamlauth_domain_model_settings.php`, `ext_tables.sql` |

A login works like this: the plugin sends the visitor to the IdP's SSO URL. The IdP posts a SAML response to the Assertion Consumer Service URL of the settings record. TYPO3's frontend authentication calls `AuthenticationService::getUser()`, which selects the settings record (by the `saml_id` query parameter, else by an `sp_entity_id` equal to the request's scheme and host, else uid 1), builds the php-saml settings from it (`SamlService::buildSettings()`), and validates the response with `OneLogin\Saml2\Response::isValid()` in php-saml's strict mode. For a valid response it looks up the frontend user by the `username` attribute, prefixed with the record's username prefix, in the record's user storage folder, and creates the user there if none exists; a user that exists there but is disabled, not yet active or expired is not logged in. `AuthenticationService::authUser()` then authenticates exactly that user. A frontend login request without a SAML response is sent to the IdP by `getUser()`. If the request carries a `RelayState` on the site's own host, `DeepLinkSsoMiddleware` redirects there. `AfterUserLoggedInEventListener` is meant to store the settings uid, the assertion ID and the NameID in the frontend user session for single logout; on TYPO3 12.4 and 13.4 it does not store them yet ([#114](https://github.com/netresearch/t3x-nr-saml-auth/issues/114)).

The extension opens no network connections. It does not fetch IdP metadata: the IdP entity ID, SSO URL, logout URL and certificate are entered into the settings record by an integrator. The library's `IdPMetadataParser::parseRemoteXML()` is not called, and `Classes/` contains no HTTP client.

## Security expectations

Users can expect:

- A SAML response that contains a document type declaration is rejected, which counters XML external entity attacks (php-saml `Utils::loadXML()`, `src/Saml2/Utils.php` lines 94–99).
- `AuthenticationService::getUser()` accepts a SAML response only if it declares SAML version 2.0, carries an ID, has the status `Success`, contains exactly one assertion, and the response or the assertion carries an XML signature that validates against the IdP certificate of the selected settings record (`Response::isValid()`, `src/Saml2/Response.php` lines 137–173 and 402–435). A response without a signature is rejected.
- php-saml's strict mode is always on (`'strict' => true` in `SamlService`), so a response must also carry a Conditions element, an AuthnStatement and a valid bearer subject confirmation, and every Destination, Recipient, audience, issuer and validity period the response states must match: the URL it was received at, the settings record's SP entity ID, its IdP entity ID, and the current time (`src/Saml2/Response.php` lines 175–400). The URL is the request URL as TYPO3 normalised it, including the reverse proxy settings for scheme, host, port and path prefix (`SamlService::getResponse()`). Schema validation follows the extension setting `validateXml` (default on).
- A response that fails validation, that php-saml cannot load, parse or decrypt, or that has no `username` attribute logs nobody in: `getUser()` returns `false` and `authUser()` returns `0`.
- Each assertion logs a user in once. `getUser()` records a hash of the IdP entity ID and the assertion ID in the table `tx_nrsamlauth_assertion` until the assertion's validity period ends. The hash is the table's primary key, so recording an assertion a second time fails, also for two requests at the same time, and the assertion is rejected, also when it is posted for another settings record that trusts the same IdP.
- `AuthenticationService::authUser()` returns `100` ("not responsible") for a request without a SAML response, so a backend login with username and password is decided by TYPO3's other authentication services. For a request with a SAML response it returns `200` only for the frontend user `getUser()` resolved from that response in the same request, and `0` otherwise.
- `DeepLinkSsoMiddleware` redirects to a `RelayState` only if it is a path starting with a single `/` or an absolute URL with the scheme, host and port of the current request; any other value is ignored.
- The user storage folder and the user groups of a user created at login come from the settings record, never from the assertion. The assertion supplies only the username and the `mail`, `companyname`, `fullname` and `country` values, each cast to a string (`AuthenticationService::insertUserRecord()`, `getValueFromAttribute()`).
- The backend module does not output the SP private key: it prints SP metadata built from the SP certificate only (`SamlService::getMetadata()`), and the metadata is HTML-escaped with `htmlentities()` before the template outputs it (`SamlAuthController::metadataAction()`, `Resources/Private/Templates/SamlAuth/Metadata.html`).
- php-saml's own debug output is off (`'debug' => false` in `SamlService`), so validation errors are not echoed to the client. A response that fails validation is logged with php-saml's reason, which can quote the issuer, audience, destination or status message of the response, not with the response itself (`AuthenticationService::getValidatedAssertion()`).
- Database writes and lookups use Doctrine DBAL `Connection::insert()`, Extbase queries and named parameters (`AuthenticationService`, `SettingsRepository`, `AfterUserLoggedInEventListener`).

Users cannot expect:

- Protection against a compromised or malicious IdP. Whoever holds the private key that matches the IdP certificate in a settings record can log in as any user in that record's storage folder, and a new username creates a new frontend user.
- Encryption of the SP private key at rest. `sp_key` is a plain `text` column (`ext_tables.sql`) edited in a plain text field (TCA). Every backend user who may read the table, and anyone with access to the database or its backups, can read the key. The table is not restricted to administrators. Keep the table out of shared database dumps.
- Updates of existing users. Attribute values are written only when the user is created; a later login does not change the record.
- Deprovisioning. A user removed at the IdP keeps the TYPO3 frontend user and any session that has not expired.
- IdP-initiated single logout. The extension sends the user to the IdP's logout URL after a TYPO3 logout (`AfterUserLoggedOutEventListener`); it does not process a `LogoutRequest` or `LogoutResponse` from the IdP.
- Protection against a misconfigured installation. The extension trusts its settings records, the IdP certificate entered there, and the TYPO3 and web server configuration, including HTTPS for the Assertion Consumer Service URL.

## Threat model and trust boundaries

| Boundary | Untrusted input | Control |
|----------|-----------------|---------|
| Client → `AuthenticationService::getUser()` and `authUser()` | `SAMLResponse`, `saml_id`, username and password fields | `getUser()` accepts only a response that php-saml loads without a DOCTYPE and validates in strict mode (status, assertion count, signature against the IdP certificate of the selected record, destination, audience, issuer, validity period), once per assertion; `authUser()` authenticates only the user resolved from that response and returns 100 for requests without a SAML response; `saml_id` is cast to an integer |
| Client → `DeepLinkSsoMiddleware` | `RelayState` | Redirect only to a path or URL on the host of the request |
| IdP → TYPO3 | Username and attributes in a signed assertion | Trusted once the signature is valid; storage folder and user groups come from the settings record; values written with DBAL |
| Visitor → frontend plugin | Page request | Only redirects to the SSO URL of the record selected in the plugin's FlexForm |
| Backend module output | Settings record fields | Metadata HTML-escaped before the template outputs it |
| Integrator → extension | Settings records, extension configuration | Trusted |

## Secure design principles applied

- **Reuse of a maintained implementation.** SAML parsing, signature validation and decryption are delegated to php-saml and xmlseclibs; the extension contains no XML or cryptographic code of its own. `SamlService` is the only place that builds the library settings.
- **Configuration over assertion content.** The storage folder and groups of provisioned users are fixed by the settings record, not by the assertion.
- **Minimal disclosure.** The private key never leaves the settings record through the extension's output, library debug output is off, and log entries carry messages, not SAML documents.

## Countering common weaknesses

| Weakness (CWE / OWASP) | Counter | Evidence |
|------------------------|---------|----------|
| XML external entities (CWE-611, A05:2021) | php-saml rejects documents with a DOCTYPE | `.Build/vendor/onelogin/php-saml/src/Saml2/Utils.php` (`loadXML()`) |
| Improper verification of a cryptographic signature (CWE-347) | `getUser()` accepts a response only if php-saml validates a signature on the response or the assertion against the configured IdP certificate | `Response::isValid()`; `AuthenticationService::getUser()`; `Tests/Functional/Authentication/SamlLoginTest.php` posts responses signed with a key generated per test through the frontend application |
| Improper authentication (CWE-287) | `authUser()` returns `200` only for the user resolved from a valid SAML response of the same request and `100` for requests without one | `AuthenticationService::authUser()`; `Tests/Functional/Authentication/BackendLoginTest.php`, `SamlLoginTest.php` |
| Insufficient verification of data authenticity (CWE-345), authentication bypass by capture-replay (CWE-294) | Strict validation of the destination, audience, issuer and validity period a response states; each assertion accepted once, enforced by a primary key | `SamlService`, `AuthenticationService::getValidatedAssertion()`; `SamlLoginTest.php` |
| URL redirection to an untrusted site (CWE-601) | Redirect only to a `RelayState` on the host of the request | `Classes/Middleware/DeepLinkSsoMiddleware.php`; `SamlLoginTest.php` |
| SQL injection (CWE-89, A03:2021) | DBAL insert, Extbase queries, named parameters, integer cast | `Classes/Sv/AuthenticationService.php`, `Classes/EventListener/AfterUserLoggedInEventListener.php`, `Classes/Domain/Repository/SettingsRepository.php` |
| Cross-site scripting (CWE-79, A03:2021) | `htmlentities()` before the raw output of the metadata; Fluid escaping in the login template | `Classes/Controller/SamlAuthController.php`, `Resources/Private/Templates/Auth/Login.html` |
| Insertion of sensitive information into log files (CWE-532) | Log entries contain messages, the settings uid and php-saml's validation reason, not the SAML response | `Classes/Sv/AuthenticationService.php`, `Classes/EventListener/` |
| Hard-coded credentials (CWE-798) | None in the code; certificates and keys live in the settings records. Betterleaks scans every pull request, with the allow-list in `.gitleaks.toml` | `.github/workflows/checks.yml` |
| Vulnerable and outdated components (A06:2021) | Composer Audit and Dependency Review on every pull request, Renovate update pull requests | `.github/workflows/checks.yml`, `renovate.json` |

## Verification

The unit and functional tests run in CI on every pull request (`.github/workflows/ci.yml`); PHPStan runs at level 8, and Opengrep runs under the [organisation rule](https://github.com/netresearch/.github/blob/main/SECURITY.md#static-analysis-sast). The full list of pull-request checks is in [CONTRIBUTING.md](../CONTRIBUTING.md#governance-and-policies). Locally:

```bash
composer ci:test:php:unit
typo3DatabaseDriver=pdo_sqlite composer ci:test:php:functional
composer ci:test:php:phpstan
```
