# Architecture

Agent-facing component map for `nr_saml_auth`. For prose documentation see `Documentation/` (rendered for docs.typo3.org); for contribution rules see `AGENTS.md` and `CONTRIBUTING.md`.

## System overview

`nr_saml_auth` adds SAML 2.0 Single Sign-On for TYPO3 frontend users. It wraps the `onelogin/php-saml` library behind a TYPO3 authentication service and an Extbase frontend plugin: the plugin's login action redirects an anonymous visitor to the Identity Provider (IdP), the IdP posts the SAML response back, and the registered authentication service maps the asserted identity onto a TYPO3 frontend user. IdP/SP settings are editor-managed records (`tx_nrsamlauth_domain_model_settings`), selected per plugin instance via FlexForm.

## Components

| Path | Role |
|------|------|
| `Classes/Controller/AuthController.php` | Extbase frontend plugin controller. `loginAction` redirects anonymous users to SSO via `SamlService`; `receiveSamlResponseAction` is the response callback view. |
| `Classes/Controller/SamlAuthController.php` | Backend module controller (`Configuration/Backend/Modules.php`): `metadataAction` displays the SP SAML metadata XML. |
| `Classes/Service/SamlService.php` | Singleton wrapper around `onelogin/php-saml` (`OneLogin\Saml2\*`): builds SP/IdP settings from the `Settings` record, initiates the SSO redirect. |
| `Classes/Sv/AuthenticationService.php` | TYPO3 authentication service registered in `ext_localconf.php` (subtypes `authUserFE,authUserBE,getUserFE`, priority/quality 100): `getUser()` resolves the user from the SAML response, `authUser()` decides authentication. |
| `Classes/Session/SamlSession.php` | Singleton session-state helper (`@internal`, not public API). |
| `Classes/Middleware/DeepLinkSsoMiddleware.php` | PSR-15 frontend middleware (`nrumauth/sso/redirect`, after `typo3/cms-frontend/authentication`, see `Configuration/RequestMiddlewares.php`): deep-link SSO handling. |
| `Classes/EventListener/` | Listeners for TYPO3 core auth events `BeforeUserLogoutEvent`, `AfterUserLoggedInEvent`, `AfterUserLoggedOutEvent` (registered in `Configuration/Services.yaml`). The extension dispatches no events of its own. |
| `Classes/Domain/Model/Settings.php`, `Classes/Domain/Repository/SettingsRepository.php` | Extbase model/repository for the IdP/SP settings record (`tx_nrsamlauth_domain_model_settings`, TCA in `Configuration/TCA/`). |
| `Configuration/` | `Services.yaml` (DI), `RequestMiddlewares.php`, TCA, FlexForms (plugin settings selection), Sets, TypoScript, backend module/icon registration. |
| `ext_localconf.php` | Registers the `NrSamlAuth`/`Authentication` plugin and the auth service. |
| `Tests/Unit/`, `Tests/Functional/` | PHPUnit suites (`Build/phpunit/UnitTests.xml`, `FunctionalTests.xml`). `Tests/Functional/Helper/` provides `MockIdpProvider`, `SamlResponseBuilder`, `SamlAssertionFactory`; `Tests/Functional/Saml/SamlProtocolTest.php` exercises the SAML protocol flow. |
| `Build/` | `rector.php` (delegates to the shared org config from `netresearch/typo3-ci-workflows`), `phpunit/` configs, `Scripts/runTests.sh`, `Scripts/verify-harness.sh`. |

## Data flow (login)

1. Anonymous visitor hits a page with the `Authentication` plugin → `AuthController::loginAction` loads the configured `Settings` record (`samlAuthSettings` FlexForm value) into `SamlService` and calls `redirectUserToSSO()`.
2. The IdP authenticates the user and posts the SAML response back to TYPO3.
3. TYPO3's frontend authentication invokes `Sv/AuthenticationService` (`getUserFE`/`authUserFE`): it validates the response via `SamlService` and maps the asserted identity to a frontend user.
4. Core auth events (`AfterUserLoggedInEvent`, …) trigger the extension's listeners; `DeepLinkSsoMiddleware` runs after frontend authentication for deep-link SSO redirects.

## Dependency rules

No enforced architecture test exists (no `Tests/Architecture/`). Observable conventions: DI via `Configuration/Services.yaml` (autowire/autoconfigure, `Domain/Model` excluded); the `onelogin/php-saml` API is confined to `SamlService`, `Sv/AuthenticationService` and the SAML test helpers.

## Key decisions

- CI matrix and shared jobs come from the reusable `netresearch/typo3-ci-workflows` (`.github/workflows/ci.yml` for the test matrix, `checks.yml` for security/quality gates) — matrix intent is commented inline in `ci.yml`.
- Rector/CGL configs delegate to the shared org config (`Build/rector.php`, `.php-cs-fixer.php`); PHPStan runs at level 8 (`phpstan.neon`).
- No ADR directory exists; record future architecture decisions in PRs or introduce `docs/adr/` when needed.
