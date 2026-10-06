# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: Netresearch DTT GmbH

#
# Table structure for table 'tx_nrsamlauth_domain_model_settings'
#
CREATE TABLE tx_nrsamlauth_domain_model_settings (
  name varchar(255) DEFAULT '' NOT NULL,
  redirect_url varchar(1000) DEFAULT '' NOT NULL,
	sp_entity_id varchar(250) DEFAULT '' NOT NULL,
	sp_customer_service_url varchar(1000) DEFAULT '' NOT NULL,
	sp_customer_service_binding varchar(250) DEFAULT '' NOT NULL,
	sp_name_id_format varchar(250) DEFAULT '' NOT NULL,
	sp_cert text,
	sp_key text,
	idp_entity_id varchar(250) DEFAULT '' NOT NULL,
	idp_sso_url varchar(1000) DEFAULT '' NOT NULL,
	idp_sso_binding varchar(250) DEFAULT '' NOT NULL,
	idp_logout_url varchar(1000) DEFAULT '' NOT NULL,
	idp_cert text,
	username_prefix varchar(50) DEFAULT '' NOT NULL,
	users_pid int(11) unsigned DEFAULT '0' NOT NULL,
	usergroup tinytext
);

#
# Assertions that logged a user in, kept until the assertion's validity period
# ends. The primary key makes recording an assertion a second time fail.
#
CREATE TABLE tx_nrsamlauth_assertion (
	identifier char(64) DEFAULT '' NOT NULL,
	expires int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (identifier),
	KEY expires (expires)
);
