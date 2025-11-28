..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

The extension can be installed via Composer (recommended) or manually.

Composer Installation
=====================

Install the extension using Composer:

..  code-block:: bash

    composer require netresearch/nr-saml-auth

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
