<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

/**
 * Application configuration file.
 *
 * @author Grégory Bellencontre
 */
final class Config 
{
	/*
	 * Application ID (Do not change)
	 */
	static $app_id = 'a8bfe283042f521907a9f7ac88a4c01e';
	
	/*
	 * Root directory
	 */
	static $root_directory = '';
	
	/*
	 * Application namespace
	 */
	static $app_namespace = 'Demo';
	
	/*
	 * Website/Application name
	 */
	static $site_name = 'Demo';
	
	/*
	 * Maintenance activation
	 */
	static $maintenance = false;
	
	/*
	 * Authorized IP addresses when maintenance is enabled
	 */
	static $auth_ip = [];
	
	/*
	 * Package environment
	 */
	static $environment = 'development';
	
	/*
	 * Timezone information
	 */
	static $timezone = 'Europe/Paris';
	
	/*
	 * Default locale
	 */
	static $locale = 'fr_FR';
	
	/*
	 * Database management system to use
	 */
	static $dbms_to_use = "MySQL";
	
	/*
	 * List of databases configurations
	 */
	static $databases = [
		'default' => [
			'host'   => 'localhost',
			'login'  => 'root',
			'pass'   => 'root',
			'dbname' => 'demo'
		]
	];
	
	/*
	 * Database to use among configured ones
	 */
	static $db_to_use = "default";
	
	/*
	 * Front-end theme to use (directory name in /web/theme)
	 */
	static $theme = "default";
	
	/*
	 * SMTP mail configuration
	 */
	static $mail_server = [
	   'host'     => '',
	   'port'     => 25,
	   'address'  => '',
	   'password' => ''
	];
}