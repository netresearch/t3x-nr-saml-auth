.. SPDX-License-Identifier: CC-BY-4.0
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

..  include:: /Includes.rst.txt

..  _developer:

===============
Developer Guide
===============

This section provides information for developers who want to extend or
customize the SAML authentication functionality.

..  toctree::
    :maxdepth: 2
    :titlesonly:

    Events

Architecture Overview
=====================

The extension uses the following key components:

AuthenticationService
---------------------

The ``AuthenticationService`` class extends TYPO3's authentication service
and handles:

*  SAML response validation
*  User lookup and creation
*  Authentication status management

Location: ``Classes/Sv/AuthenticationService.php``

SamlService
-----------

The ``SamlService`` class provides the interface to the onelogin/php-saml
library:

*  SAML configuration management
*  SSO redirect handling
*  Response parsing

Location: ``Classes/Service/SamlService.php``

SettingsRepository
------------------

The ``SettingsRepository`` provides access to SAML configuration records:

*  Auto-discovery by host
*  Settings retrieval

Location: ``Classes/Domain/Repository/SettingsRepository.php``

Middleware
----------

The ``DeepLinkSsoMiddleware`` handles post-authentication redirects:

*  Deep link support
*  Logout redirect handling

It redirects to the ``RelayState`` only if the value is a path on the
site (starting with a single ``/``) or an absolute URL with the scheme,
host and port of the current request. Any other value is ignored and the
request is handled as usual.

Location: ``Classes/Middleware/DeepLinkSsoMiddleware.php``

Dependency Injection
====================

All services are registered in the DI container and can be injected into
your own classes:

..  code-block:: php

    use Netresearch\NrSamlAuth\Service\SamlService;

    class MyController
    {
        public function __construct(
            private readonly SamlService $samlService
        ) {}
    }

Events
======

The extension dispatches no PSR-14 events of its own. The TYPO3 core events
it listens to are listed in :ref:`Events <events>`.
