<?php

return [
    'ctrl' => [
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'title' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'name' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 250,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'redirect_url' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.redirect_url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1000,
                'eval' => 'nospace,trim',
            ],
        ],
        'sp_entity_id' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.sp_entity_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 250,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'sp_customer_service_url' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.sp_customer_service_url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1000,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'sp_customer_service_binding' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.sp_customer_service_binding',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 250,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'sp_name_id_format' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.sp_name_id_format',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \Netresearch\NrSamlAuth\Service\SamlService::class . '->nameIdFormatItems',
                'required' => true,
            ],
        ],
        'sp_cert' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.sp_cert',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 5,
                'fixedFont' => true,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'sp_key' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.sp_key',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 5,
                'fixedFont' => true,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'idp_entity_id' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.idp_entity_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 250,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'idp_sso_url' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.idp_sso_url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1000,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'idp_sso_binding' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.idp_sso_binding',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 250,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'idp_logout_url' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.idp_logout_url',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 1000,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'idp_cert' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.idp_cert',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 5,
                'fixedFont' => true,
                'eval' => 'nospace,trim',
                'required' => true,
            ],
        ],
        'username_prefix' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.username_prefix',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 250,
                'eval' => 'nospace,trim',
            ],
        ],
        'users_pid' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.users_pid',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'usergroup' => [
            'label' => 'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.usergroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'enableMultiSelectFilterTextfield' => true,
                'size' => 6,
                'minitems' => 1,
                'maxitems' => 50,
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.sp,
                name,redirect_url,sp_entity_id,sp_customer_service_url,sp_customer_service_binding,sp_name_id_format,sp_cert,sp_key,idp_entity_id,
                --div--;LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.idp,
                ,idp_sso_url,idp_sso_binding,idp_logout_url,idp_cert,
                --div--;LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_tca.xlf:nr_saml_auth_domain_model_settings.other,
                username_prefix,users_pid,usergroup',
        ],
    ],
];
