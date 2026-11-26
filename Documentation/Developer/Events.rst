.. SPDX-License-Identifier: CC-BY-4.0
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

..  include:: /Includes.rst.txt

..  _events:

=============
PSR-14 events
=============

The extension dispatches no PSR-14 events of its own. It listens to two
TYPO3 core events to perform the SAML single logout.

..  _events-before-user-logout:

BeforeUserLogoutEvent
=====================

``TYPO3\CMS\Core\Authentication\Event\BeforeUserLogoutEvent``

The listener ``Netresearch\NrSamlAuth\EventListener\BeforeUserLogoutEventListener``
(identifier ``nr-saml-auth/before-user-logout``) reads the SAML session data
of the user who logs out, before TYPO3 removes the session.

..  _events-after-user-logged-out:

AfterUserLoggedOutEvent
=======================

``TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedOutEvent``

The listener ``Netresearch\NrSamlAuth\EventListener\AfterUserLoggedOutEventListener``
redirects the user to the single logout URL of the identity provider, using
the session data stored by the listener above. A failure is logged and the
logout continues without the redirect.
