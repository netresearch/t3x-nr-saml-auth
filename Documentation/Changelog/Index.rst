.. SPDX-License-Identifier: CC-BY-4.0
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

..  include:: /Includes.rst.txt

..  _changelog:

=========
Changelog
=========

All notable changes to this project are documented here.

Version 13.0.0
==============

Released: (not yet released)

Security
--------

*  SAML responses that do not answer an AuthnRequest this browser started
   are rejected (login CSRF). The ID of each AuthnRequest is remembered in
   the cookie ``nr_saml_auth_request`` (``SameSite=None; Secure; HttpOnly``,
   HMAC-protected, 10 minutes, up to five pending logins), and the
   response's ``InResponseTo`` must match it.
*  IdP-initiated login (a response without ``InResponseTo``) is rejected
   unless the new field ``allow_idp_initiated`` of the settings record
   is on. It is off by default.

Upgrade
-------

*  Run the database schema update. Existing settings records get
   ``allow_idp_initiated`` switched off. If your Identity Provider
   starts logins itself, switch it on, or those logins are rejected.
*  SP-initiated login needs the Assertion Consumer Service on HTTPS.
*  A response is accepted only for the settings record that started the
   login. The record the plugin selects must be the one the Assertion
   Consumer Service URL resolves to (``saml_id``, else the record whose SP
   entity ID matches the host, else uid 1).

Added
-----

*  TYPO3 14.3 LTS support
*  Upgrade wizard ``nrSamlAuth_pluginListTypeToCType`` that migrates existing
   plugin content elements and backend user group permissions to the new
   content element type

Changed
-------

*  **Breaking:** the plugin is a content element type of its own (``CType``
   ``nrsamlauth_authentication``) instead of a subtype of the "Insert Plugin"
   content element, see :ref:`migration`
*  Event listeners are registered by their ``#[AsEventListener]`` attribute

Fixed
-----

*  The FlexForm of the plugin shows the field for the SAML settings record
   again; TYPO3 13 no longer reads the ``<TCEforms>`` wrapper
*  The backend module :guilabel:`Admin Tools > SAML Auth` renders again; it
   uses the backend ModuleTemplate instead of the ``<be:moduleLayout>``
   ViewHelper, which TYPO3 12 removed

Removed
-------

*  **Breaking:** TYPO3 12.4 LTS support (version 12.x stays available)
*  **Breaking:** PHP 8.1 support

Version 12.0.1
==============

Released: 2026-11-26

Security
--------

*  SAML responses that do not answer an AuthnRequest this browser started
   are rejected (login CSRF). The ID of each AuthnRequest is remembered in
   the cookie ``nr_saml_auth_request`` (``SameSite=None; Secure; HttpOnly``,
   HMAC-protected, 10 minutes, up to five pending logins), and the
   response's ``InResponseTo`` must match it.
*  IdP-initiated login (a response without ``InResponseTo``) is rejected
   unless the new field ``allow_idp_initiated`` of the settings record
   is on. It is off by default.

Upgrade
-------

*  Run the database schema update. Existing settings records get
   ``allow_idp_initiated`` switched off. If your Identity Provider
   starts logins itself, switch it on, or those logins are rejected.
*  SP-initiated login needs the Assertion Consumer Service on HTTPS.
*  A response is accepted only for the settings record that started the
   login. The record the plugin selects must be the one the Assertion
   Consumer Service URL resolves to (``saml_id``, else the record whose SP
   entity ID matches the host, else uid 1).

Fixed
-----

*  The plugin's FlexForm field that selects the settings record had no label
   and no configuration on TYPO3 13.4 (``<TCEforms>`` wrapper removed).
*  The backend module failed to render on TYPO3 12.4 and 13.4
   (``<be:moduleLayout>``); it now uses the ModuleTemplate API.

Version 12.0.0
==============

Released: 2026-11-26

Added
-----

*  TYPO3 12.4 LTS support
*  TYPO3 13.4 LTS support
*  PHP 8.1, 8.2, 8.3, 8.4, 8.5 support, and PHP 8.6 with TYPO3 13.4
*  PSR-14 events for authentication customization
*  Dependency injection for all services
*  Comprehensive unit and functional tests
*  RST documentation for docs.typo3.org

Changed
-------

*  Upgraded onelogin/php-saml to version 4.0
*  Replaced legacy hooks with PSR-14 events
*  Modernized code to PHP 8.1+ standards
*  Improved code quality with PHPStan level 6

Removed
-------

*  PHP 7.x support
*  TYPO3 10.4 and 11.5 support
*  Legacy hook system

Version 10.0.10
===============

Released: 2024

Fixed
-----

*  Extension icon updated to Netresearch logo

Version 10.0.x
==============

*  TYPO3 10.4 and 11.5 LTS support
*  PHP 7.4 - 8.1 support
*  Initial SAML SSO implementation
