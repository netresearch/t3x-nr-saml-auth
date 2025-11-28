..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

What is SAML SSO?
=================

SAML (Security Assertion Markup Language) is an open standard for exchanging
authentication and authorization data between parties, specifically between an
Identity Provider (IdP) and a Service Provider (SP).

Single Sign-On (SSO) allows users to authenticate once with an Identity Provider
and then access multiple applications without re-entering credentials.

Extension Features
==================

The Netresearch SAML Auth extension provides:

*  **Frontend Authentication**: SAML-based login for frontend users
*  **Backend Authentication**: SAML-based login for backend users
*  **Auto-Discovery**: Automatic SAML configuration detection based on domain
*  **User Provisioning**: Automatic creation of TYPO3 users from SAML attributes
*  **Deep Link Support**: Redirect users to their original destination after login
*  **PSR-14 Events**: Modern event system for customization

Requirements
============

..  tabs::

    ..  group-tab:: Version 13.x

        *  TYPO3 12.4 LTS or 13.4 LTS
        *  PHP 8.1 - 8.4
        *  onelogin/php-saml 4.0+

    ..  group-tab:: Version 10.x

        *  TYPO3 10.4 LTS or 11.5 LTS
        *  PHP 7.4 - 8.1
        *  onelogin/php-saml 3.x

Support
=======

For issues and feature requests, please use the
`GitHub issue tracker <https://github.com/netresearch/t3x-nr-saml-auth/issues>`__.
