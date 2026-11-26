.. SPDX-License-Identifier: CC-BY-4.0
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

..  include:: /Includes.rst.txt

..  _migration:

===============
Migration Guide
===============

This section provides guidance for upgrading between major versions.

Upgrading from 12.x to 13.x
===========================

Version 13.x supports TYPO3 13.4 LTS and 14.3 LTS.

Requirements Changes
--------------------

*  **PHP 8.2+ required**: PHP 8.1 is no longer supported
*  **TYPO3 13.4+ required**: TYPO3 12.4 is no longer supported; version 12.x
   stays available for it

Breaking Changes
----------------

Plugin Content Element Type
~~~~~~~~~~~~~~~~~~~~~~~~~~~

The plugin "Saml Authentication" is a content element type of its own
(``CType`` ``nrsamlauth_authentication``) instead of a subtype of the
"Insert Plugin" content element (``list_type``). TYPO3 14 no longer has
plugin subtypes.

Existing content elements and backend user group permissions are migrated by
the upgrade wizard "SAML Authentication: migrate the plugin to its own content
element type" (identifier ``nrSamlAuth_pluginListTypeToCType``). TypoScript of
your own that refers to ``tt_content.list.20.nrsamlauth_authentication`` has
to refer to ``tt_content.nrsamlauth_authentication`` instead.

Migration Steps
---------------

Version 12.x does not install on TYPO3 14.3, so a site on TYPO3 12.4 moves
through TYPO3 13.4:

1.  **Update PHP**: Ensure PHP 8.2 or higher is installed
2.  **Update TYPO3 to 13.4 LTS**, still with extension 12.x, which supports
    TYPO3 13.4
3.  **Update Extension**: Run ``composer require netresearch/nr-saml-auth:^13.0``
4.  **Run the Upgrade Wizard**: Run ``vendor/bin/typo3 upgrade:run nrSamlAuth_pluginListTypeToCType``
    or use :guilabel:`Admin Tools > Upgrade > Upgrade Wizard`
5.  **Clear Caches**: Clear all TYPO3 caches
6.  **Test Authentication**: Verify SAML login still works
7.  **Update TYPO3 to 14.3 LTS** if wanted; extension 13.x supports both

Upgrading from 10.x to 12.x
===========================

Version 12.x includes breaking changes for TYPO3 12.4/13.4 compatibility.

Requirements Changes
--------------------

*  **PHP 8.1+ required**: Upgrade your PHP version
*  **TYPO3 12.4+ required**: Upgrade your TYPO3 installation
*  **onelogin/php-saml 4.0**: Library upgraded with security improvements

Breaking Changes
----------------

PSR-14 Events
~~~~~~~~~~~~~

Legacy hooks have been replaced with PSR-14 events. If you used the old
hook system, migrate to the new events:

..  code-block:: php

    // OLD: Legacy hook (removed)
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['nr_saml_auth']['beforeUserCreation']

    // NEW: PSR-14 event
    Netresearch\NrSamlAuth\Event\BeforeUserCreationEvent

See :ref:`Events <events>` for the complete event reference.

Dependency Injection
~~~~~~~~~~~~~~~~~~~~

Services now use TYPO3's DI container. Direct instantiation is deprecated:

..  code-block:: php

    // OLD: Direct instantiation (deprecated)
    $service = new \Netresearch\NrSamlAuth\Service\SamlService();

    // NEW: Dependency injection
    public function __construct(
        private readonly SamlService $samlService
    ) {}

Configuration
~~~~~~~~~~~~~

The SAML Settings record structure remains unchanged. No database migrations
are required.

Migration Steps
---------------

1.  **Update PHP**: Ensure PHP 8.1 or higher is installed
2.  **Update TYPO3**: Upgrade to TYPO3 12.4 LTS or 13.4 LTS
3.  **Update Extension**: Run ``composer update netresearch/nr-saml-auth``
4.  **Clear Caches**: Clear all TYPO3 caches
5.  **Test Authentication**: Verify SAML login still works
6.  **Update Custom Code**: Migrate any custom hooks to PSR-14 events

Backward Compatibility
======================

The extension maintains backward compatibility for:

*  SAML Settings record structure
*  Database schema
*  SAML response handling

The following are NOT backward compatible:

*  PHP 7.x support (requires 8.1+)
*  TYPO3 10.4/11.5 support (requires 12.4+)
*  Legacy hook system (use PSR-14 events)
