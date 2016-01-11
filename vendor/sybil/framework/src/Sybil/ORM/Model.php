<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 *
 * This file creates an abstract Model class, 
 * extending the chosen DBMS Model class.
 */
 
namespace Sybil\ORM; 

use Sybil\Config;
use Sybil\App;
use Sybil\ORM\Database;
use Sybil\Translation;

abstract class Model
{
	private $db = null;
	private $translation = null;
	private $pending_queue = null;
	private $core_properties = ['entity_name','identifier'];
	
	/*
	 * Class constructor.
	 */
	
	public function __construct() 
	{
		$this->db = Database::getInstance();
		
		// Translation tool
		
		if ($this->translation === null) {
    	   $this->translation = new Translation(['bundle' => false, 'theme' => 'sybil', 'domain' => 'database']);
    	}
	}
	
	/*
	 * Returns an object containing the whole object data.
	 *
	 * @return object The whole object data.
	 */
	
	public function getRecord()
	{
		$class_vars = get_class_vars(get_class($this));
		$parent_class_vars = get_class_vars(get_parent_class($this));
		
		$vars = App::array_diff_assoc_recursive($class_vars,$parent_class_vars);

		foreach($this->core_properties as $property) {
			if (isset($vars[$property])) {
				unset($vars[$property]);
			}
		}
		
		foreach($vars as $property=>$value) {
			$method_name = 'get' . App::toCamelCase($property);
			
			if (method_exists($this,$method_name)) {
				$vars[$property] = $this->$method_name();
			}
		}
		
		return (object) $vars;
	}
	
	/*
	 * Fills the object data with a given array.
	 *
	 * @param array $array Source associative array
	 */
	
	public function fill(array $array)
	{
		foreach($array as $key=>$value) {
			$method_name = 'set' . App::toCamelCase($key);
			
			if (method_exists(get_class($this),$method_name)) {
				call_user_func_array(array($this,$method_name),array($value));
			}
		}
	}	
}