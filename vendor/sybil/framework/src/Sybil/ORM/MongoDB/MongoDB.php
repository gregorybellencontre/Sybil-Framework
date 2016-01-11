<?php
/**
 * Sybil Framework
 * (c) 2015 Grégory Bellencontre
 */

namespace Sybil\ORM\MongoDB;

use Sybil\App;
use Sybil\ORM\Database;
use Sybil\Config;
use MongoClient;

/**
 * The MongoDB class manages tables and properties.
 *
 * @author Grégory Bellencontre
 */
final class MongoDB 
{
	private $db = null;
	private $db_name = null;

	public function __construct()
	{
		$this->db = Database::getInstance();
		$this->db_name = Config::$databases[Config::$db_to_use]['dbname'];
	}
	
	/*
	 * Performs a connection to the database.
	 */
	 
	public static function connect() 
	{
		$auth = Config::$databases[Config::$db_to_use];
		
		try {
			return new MongoClient("mongodb://" . $auth['login'] . ":" . $auth['pass'] . "@" . $auth['host'] . "/" . $auth['dbname']);
		}
		catch(PDOException $e) {
			return false;
		}
	}
	
}