..  include:: /Includes.rst.txt

..  _events:

===========
PSR-14 Events
===========

The extension provides PSR-14 events for customizing the authentication
process.

Available Events
================

BeforeUserCreationEvent
-----------------------

Dispatched before a new user is created from SAML attributes.

..  code-block:: php

    namespace Netresearch\NrSamlAuth\Event;

    final class BeforeUserCreationEvent
    {
        public function getSamlAttributes(): array;
        public function getUserData(): array;
        public function setUserData(array $userData): void;
        public function getSettings(): Settings;
    }

AfterUserCreationEvent
----------------------

Dispatched after a new user has been created.

..  code-block:: php

    namespace Netresearch\NrSamlAuth\Event;

    final class AfterUserCreationEvent
    {
        public function getUser(): array;
        public function getSamlAttributes(): array;
        public function getSettings(): Settings;
    }

BeforeAuthenticationEvent
-------------------------

Dispatched before authentication is processed.

..  code-block:: php

    namespace Netresearch\NrSamlAuth\Event;

    final class BeforeAuthenticationEvent
    {
        public function getSamlResponse(): Response;
        public function getSettings(): Settings;
        public function setSkipAuthentication(bool $skip): void;
    }

Registering Event Listeners
===========================

Register your event listeners in your extension's ``Services.yaml``:

..  code-block:: yaml

    # Configuration/Services.yaml
    services:
      Vendor\MyExtension\EventListener\CustomUserCreationListener:
        tags:
          - name: event.listener
            identifier: 'myextension/custom-user-creation'
            event: Netresearch\NrSamlAuth\Event\BeforeUserCreationEvent

Example Implementation
======================

Custom attribute mapping:

..  code-block:: php

    namespace Vendor\MyExtension\EventListener;

    use Netresearch\NrSamlAuth\Event\BeforeUserCreationEvent;

    final class CustomUserCreationListener
    {
        public function __invoke(BeforeUserCreationEvent $event): void
        {
            $attributes = $event->getSamlAttributes();
            $userData = $event->getUserData();

            // Map department to custom field
            if (isset($attributes['department'][0])) {
                $userData['tx_myext_department'] = $attributes['department'][0];
            }

            // Map employee ID
            if (isset($attributes['employeeId'][0])) {
                $userData['tx_myext_employee_id'] = $attributes['employeeId'][0];
            }

            $event->setUserData($userData);
        }
    }
