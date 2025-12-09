..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

The extension is configured through a **SAML Auth Settings** record in the
TYPO3 backend.

..  toctree::
    :maxdepth: 2
    :titlesonly:

    Reference

Creating a Settings Record
==========================

1.  Go to :guilabel:`List` module on the root page (PID 0)
2.  Click :guilabel:`Create new record`
3.  Select :guilabel:`SAML Auth Settings`

Service Provider (SP) Configuration
===================================

The Service Provider represents your TYPO3 installation.

Entity ID
---------

The unique identifier for your Service Provider. Typically your domain URL:

..  code-block:: text

    https://your-domain.tld

Customer Service URL (ACS)
--------------------------

The Assertion Consumer Service URL where SAML responses are received:

..  code-block:: text

    https://your-domain.tld/?logintype=login

Name ID Format
--------------

The format for the Name ID in SAML assertions:

*  ``urn:oasis:names:tc:SAML:2.0:nameid-format:transient`` - Temporary identifier
*  ``urn:oasis:names:tc:SAML:2.0:nameid-format:emailAddress`` - Email address
*  ``urn:oasis:names:tc:SAML:2.0:nameid-format:persistent`` - Persistent identifier

Certificates
------------

You can optionally configure SP certificates for signed requests:

*  **Certificate**: Public certificate (PEM format)
*  **Private Key**: Private key (PEM format)

Identity Provider (IdP) Configuration
=====================================

The Identity Provider is your SSO server (e.g., Azure AD, Okta, SimpleSAMLphp).

Entity ID
---------

The unique identifier provided by your IdP.

SSO URL
-------

The Single Sign-On URL where authentication requests are sent.

Logout URL
----------

The Single Logout URL for ending sessions (optional).

Certificate
-----------

The IdP's public certificate for validating SAML responses (required).

User Configuration
==================

Username Prefix
---------------

Optional prefix added to usernames created from SAML authentication:

..  code-block:: text

    sso-

This helps identify SSO-created users in the system.

User Folder
-----------

Select the page (folder) where new frontend users will be stored.

User Groups
-----------

Select the default user groups assigned to newly created users.

Auto-Discovery
==============

The extension supports automatic configuration discovery based on the request
domain. When a user attempts to login, the extension matches the current
domain against configured ``sp_entity_id`` values to find the appropriate
SAML configuration.

This allows multiple SAML configurations for different domains within the
same TYPO3 installation.
