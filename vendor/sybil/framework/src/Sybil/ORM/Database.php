<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil\ORM;

use Sybil\Translation;
use Sybil\Config;
use Sybil\App;

/**
 * Database class provides tools for connection management.
 *
 * @author Grégory Bellencontre
 */
final class Database 
{
	static $db = null;
	static $translation = null;
	
	/*
	 * Performs connection to the database.
	 */
	 
	public static function connect() 
	{
		if (self::$translation === null) {
			self::$translation = new Translation(['bundle' => null, 'theme' => 'sybil', 'domain' => 'debug']);
    	}
	
		if (self::$db === null) {
			$model_path = CORE_SRC.'ORM/'.Config::$dbms_to_use . '/Model.php';
			
			if (file_exists($model_path)) {
				require_once($model_path);
				$class_name = '\Sybil\ORM\\' . Config::$dbms_to_use . '\\' . Config::$dbms_to_use;
				self::$db = $class_name::connect();
			
				return self::$db;
			}
			else {
				if (ENVIRONMENT == 'production') {
					$this->redirect('unavailable');
				}
				else {
					App::translate("dbms_file_missing",self::$translation);
					die();
				}
			}
		}
	}
	
	/*
	 * Closes the connection to the database.
	 */
	
	public static function clearInstance() 
	{
		self::$db = null;
	}
	
	/*
	 * Returns the database object.
	 */
	
	public static function getInstance() 
	{
		if (self::$db === null) {
			Database::connect();
		}
	
		return self::$db;
	}
	
}