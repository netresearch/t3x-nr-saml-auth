..  include:: /Includes.rst.txt

..  _configuration-reference:

=======================
Configuration Reference
=======================

SAML Settings Record Fields
===========================

..  confval-menu::
    :name: saml-settings
    :display: table

    ..  confval:: name
        :type: string
        :required: true

        A descriptive name for this SAML configuration.

    ..  confval:: sp_entity_id
        :type: string
        :required: true

        The unique identifier for your Service Provider (typically your domain URL).

    ..  confval:: sp_customer_service_url
        :type: string
        :required: true

        The Assertion Consumer Service URL where SAML responses are received.

    ..  confval:: sp_customer_service_binding
        :type: string
        :default: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST

        The binding method for the ACS endpoint.

    ..  confval:: sp_name_id_format
        :type: string
        :default: urn:oasis:names:tc:SAML:2.0:nameid-format:transient

        The format for the Name ID in SAML assertions.

    ..  confval:: sp_cert
        :type: text
        :required: false

        The Service Provider's public certificate (PEM format).

    ..  confval:: sp_key
        :type: text
        :required: false

        The Service Provider's private key (PEM format).

    ..  confval:: idp_entity_id
        :type: string
        :required: true

        The unique identifier of the Identity Provider.

    ..  confval:: idp_sso_url
        :type: string
        :required: true

        The Single Sign-On URL of the Identity Provider.

    ..  confval:: idp_sso_binding
        :type: string
        :default: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect

        The binding method for the SSO endpoint.

    ..  confval:: idp_logout_url
        :type: string
        :required: false

        The Single Logout URL of the Identity Provider.

    ..  confval:: idp_cert
        :type: text
        :required: true

        The Identity Provider's public certificate for validating responses.

    ..  confval:: username_prefix
        :type: string
        :required: false

        Optional prefix for usernames created via SAML authentication.

    ..  confval:: users_pid
        :type: int
        :required: true

        The page ID (folder) where new users will be created.

    ..  confval:: usergroup
        :type: string
        :required: false

        Comma-separated list of user group UIDs assigned to new users.

Example Configuration
=====================

..  code-block:: text

    # Service Provider Settings
    Entity ID: https://your-domain.tld
    Customer Service URL: https://your-domain.tld/?logintype=login
    Customer Service Binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST
    Name ID Format: urn:oasis:names:tc:SAML:2.0:nameid-format:transient

    # Identity Provider Settings
    Entity ID: urn:example:idp
    SSO URL: https://idp.example.com/sso
    Binding: urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect
    Certificate: [IDP Certificate]

    # User Settings
    Username Prefix: sso-
    User Folder: [Select frontend user folder]
    User Groups: [Select default frontend user groups]
