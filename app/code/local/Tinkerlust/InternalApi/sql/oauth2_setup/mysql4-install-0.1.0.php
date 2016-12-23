<?php
$installer = $this;
$installer->startSetup();

//install oauth2 clients
$installer->run("
	CREATE TABLE IF NOT EXISTS {$this->getTable('tinkerlust_oauth2_clients')} (
		client_id VARCHAR(80) NOT NULL,
		client_secret VARCHAR(80),
		redirect_uri VARCHAR(2000) NOT NULL,
		grant_types VARCHAR(80),
		scope VARCHAR(100),
		user_id VARCHAR(80)
	);

	CREATE TABLE  IF NOT EXISTS {$this->getTable('tinkerlust_oauth2_accesstokens')} (
		access_token VARCHAR(40) NOT NULL, 
		client_id VARCHAR(80) NOT NULL, 
		user_id VARCHAR(255), 
		expires TIMESTAMP NOT NULL, 
		scope VARCHAR(2000), 
		CONSTRAINT access_token_pk 
		PRIMARY KEY (access_token)
	);

	CREATE TABLE IF NOT EXISTS {$this->getTable('tinkerlust_oauth2_refreshtokens')} (
		refresh_token VARCHAR(40) NOT NULL, 
		client_id VARCHAR(80) NOT NULL, 
		user_id VARCHAR(255), 
		expires TIMESTAMP NOT NULL, 
		scope VARCHAR(2000), 
		CONSTRAINT refresh_token_pk 
		PRIMARY KEY (refresh_token)
	);
");

$installer->endSetup();
?>