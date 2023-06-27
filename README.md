Netresearch TYPO3 Saml Auth
===========================

> With this TYPO3 extension you can authenticate against a SAML SSO server.\
> It works for backend and frontend users and make use of the `onelogin/php-saml` package.

## Installation
Require the package.

```bash
composer require netresearch/nr-saml-auth
```

Then you have to add a new record of `SAML Auth Settings` on the root page in the TYPO3 backend and configure it properly.

Example configuration
```
Entity ID: your-sp

Customer service URL: https://domain.tld/?logintype=login

Customer service binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST

Name ID format: urn:oasis:names:tc:SAML:2.0:nameid-format:transient

Certificate: -----BEGIN CERTIFICATE-----
MIIFYDCCA0igAwIBAgIJAMWkGz7F5peWMA0GCSqGSIb3DQEB
...
6E29QdAP/7OlaUjL8yb0hAQfcweKg7A9Kw+nVngScgiq99FT
-----END CERTIFICATE-----

Private key: -----BEGIN PRIVATE KEY-----
MIIJQwIBADANBgkqhkiG9w0BAQEFAASCCS0wggkpAgEAAoICAQCk/hHdRe3
...
3gxX31MgSwnYq6RTKQvPUlEX2UmMcjk=
-----END PRIVATE KEY-----

Entity ID: urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress

SSO URL: http://sso-url.de:80/sso

Binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect

# Provided by the SSO server else just input something
Certificate: ...

# Prefix for created users
Other: sso-

Groups: Choose you frontenduser folder

Groups: Select the Frontendusergroup which should every logged in user assigned to
```

As example SSO server you could use [https://capriza.github.io/samling/samling.html](https://simplesamlphp.org/docs/stable/simplesamlphp-install.html#download-and-install-simplesamlphp).

Auto discovery
==============
* the login service will try to autodetect the configuration for the current login/logout request
* Therefor ensure, that your sp_entity_id matches your domain


Middleware
==========
* the extension provides a middleware to redirect the client to the page he came from after login/logout
* ensure that RelayState parameter contains the target URL and is transmitted via post/get from SAML server to the configured ACS urls
