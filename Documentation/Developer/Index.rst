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

The ``RelayStateMiddleware`` handles post-authentication redirects:

*  Deep link support
*  Logout redirect handling

Location: ``Classes/Middleware/RelayStateMiddleware.php``

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

Extending User Creation
=======================

To customize user creation, listen to the PSR-14 events documented in the
:ref:`Events <events>` section.

Example: Adding custom user attributes from SAML response:

..  code-block:: php

    use Netresearch\NrSamlAuth\Event\BeforeUserCreationEvent;

    class CustomUserAttributeListener
    {
        public function __invoke(BeforeUserCreationEvent $event): void
        {
            $attributes = $event->getSamlAttributes();
            $userData = $event->getUserData();

            // Add custom attribute mapping
            $userData['custom_field'] = $attributes['customAttribute'][0] ?? '';

            $event->setUserData($userData);
        }
    }
