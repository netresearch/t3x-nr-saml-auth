..  include:: /Includes.rst.txt

..  _migration:

===============
Migration Guide
===============

This section provides guidance for upgrading between major versions.

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
