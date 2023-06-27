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
