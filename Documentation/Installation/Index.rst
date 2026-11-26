.. SPDX-License-Identifier: CC-BY-4.0
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

The extension supports Composer installations only. The package in the
TYPO3 Extension Repository (TER) does not contain the SAML library
``onelogin/php-saml``, so an installation from the TER or from a ZIP file
without Composer does not work.

Composer Installation
=====================

Install the extension using Composer:

..  code-block:: bash

    composer require netresearch/nr-saml-auth

Version 13.x requires TYPO3 13.4 LTS or 14.3 LTS and PHP 8.2 or later. For
TYPO3 12.4, install version 12.x:

..  code-block:: bash

    composer require netresearch/nr-saml-auth:^12.0

After installation, activate the extension in the Extension Manager or via CLI:

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Initial Setup
=============

After installation, you need to:

1.  Create a **SAML Auth Settings** record on the root page (PID 0) in the
    TYPO3 backend under :guilabel:`List > Create new record > SAML Auth Settings`

2.  Configure the Service Provider (SP) settings (your TYPO3 installation)

3.  Configure the Identity Provider (IdP) settings (your SSO server)

4.  Select the user folder and default user groups for new users

See the :ref:`Configuration <configuration>` section for detailed setup
instructions.

Verification
============

To verify the installation:

1.  Access the :guilabel:`Admin Tools > SAML Auth` backend module
2.  Check that your SAML metadata is correctly generated
3.  Share the SP metadata with your Identity Provider administrator
