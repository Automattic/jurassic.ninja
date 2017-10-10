<?php

$global_config = array(
	'DOMAIN' => 'mydomain.com',
	'SERVER_ID' => 'server_pilot_server_id',
	'DEFAULT_ADMIN_EMAIL_ADDRESS' => 'test@test.com',
	'db' => array(
		'username' => 'db-username',
		'password' => 'db-password',
	),
        'SITES_EXPIRATION' => 'INTERVAL 7 DAY',
        'SITES_NEVER_LOGGED_IN_EXPIRATION' => 'INTERVAL 7 DAY',
        'SITES_NEVER_CHECKED_IN_EXPIRATION' => 'INTERVAL 10 HOUR',
	'serverpilot' => array(
		'id' => 'client_id',
		'key' => 'client_key',
	),
);
