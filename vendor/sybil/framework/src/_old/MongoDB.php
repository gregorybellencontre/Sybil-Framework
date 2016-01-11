<?php
/*
 * Abtract model class for MongoDB engine
 * using MongoDB PHP driver
 * 
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

use Sybil\Pagination;
use Sybil\Translation;
use Sybil\App;
use MongoClient;
 
abstract class Model {
	static $connection = null;
	var $db = null;
	var $entity = null;
	var $schema = array();
	var $excluded_fields = array();
	var $fields = array();
	var $find_params = array();
	var $results_nb = null;
	var $pagination = null;
	var $translation = null;
	
	/*
	 * Constructing the model
	 */
	
	public function __construct() {
		$this->db = self::$connection;
		$collection_name = strtolower(str_replace('Model','',get_class($this)));
		
		if ($this->entity == null) {
			$this->entity = $this->db->$collection_name;
		}
		else {
			$collection = $this->entity;
			$this->entity = $this->db->$collection;
		}
		
		$this->fields = isset($this->schema) ? array_diff($this->schema,$this->excluded_fields) : array();
		
		if ($this->translation === null) {
    	   $this->translation = new Translation('sybil','database');
    	}
	}
	
	/*
	 * Connecting to the database
	 *
	 * @param bool $error Trigger an error if connection fail
	 */
	
	public static function connect($error=true) {
		$auth = Config::$databases[Config::$db_to_use];
		
		try {
			$connection = new MongoClient("mongodb://" . $auth['login'] . ":" . $auth['pass'] . "@" . $auth['host'] . "/" . $auth['dbname']);
			self::$connection = $connection->$auth['dbname'];
		}
		catch(PDOException $e) {
			if (ENVIRONMENT == 'production' || $error === false) {
			    return false;
			}
			else {
				App::debug("DB_ERROR: Impossible to reach the database or wrong ids.");
				die();
			}
		}
		
		return true;
	}
	
	/*
	 * Getting every fields value from the last find request
	 *
	 * @return array Array of fields with values
	 */
	
	public function getAll() {
		return $this->fields;
	}
	
	/*
	 * Getting a field value
	 *
	 * @param string $field_name Name of the field
	 *
	 * @return string Value of the requested field
	 */
	
	public function get($field_name) {
		return isset($this->fields[$field_name]) ? $this->fields[$field_name] : null;
	}
	
	/*
	 * Setting a field value
	 *
	 * @param string $field_name Name of the field to update
	 * @param string $field_value New value for the field
	 * @param bool $sanitize Sanitize string or not (false by default)
	 *
	 * @return bool true
	 */
	
	public function set($field_name,$field_value,$sanitize=false) {
		$this->fields[$field_name] = $sanitize === true ? filter_var($field_value,FILTER_SANITIZE_SPECIAL_CHARS) : $field_value;
		return true;
	}
	
	/*
	 * Unsetting a field in a document
	 *
	 * @param string $field_name Name of the field to unset
	 *
	 * @return bool true|false
	 */
	
	public function unsetField($field_name) {
		if (isset($this->fields[$field_name])) {
			unset($this->fields[$field_name]);
			return true;
		}
		else {
			return false;
		}
	}
	
	/*
	 * Loading a document with its ID
	 *
	 * @param integer $id ID of the document to find
	 *
	 * @return bool true|false
	 */
	
	public function load($id=0) {
		if ($id != null) {
		    $fields = array();
		    
		    if (isset($this->excluded_fields) && !empty($this->excluded_fields)) {
			    foreach($this->excluded_fields as $field) {
				    $fields[$field] = false;
			    }
		    }
		    
		    try {
				$this->fields = $this->entity->findOne(array('_id' => new MongoId($id)),$fields);
				return true;
			}
			catch(Exception $e) {
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/*
	 * Finding one or many documents
	 */
	
	/* 
	 * Adding criterias 
	 *
	 * @param array $criterias Criterias for the research
	 *
	 * @return object $this Class object
	 */
	
	public function search($criterias = array()) {
		$this->find_params['criterias'] = array();
		
		if (is_array($criterias) && !empty($criterias)) {
			foreach($criterias as $field=>$value) {
				if (is_array($value) && !preg_match('#\$#',current(array_keys($value)))) {
					$criterias[$field] = new MongoRegex("/" . $value[0] . "/i");
				}
			}
			
			$this->find_params['criterias'] = $criterias;
		}
		elseif (!empty($criterias)) {
			if (ENVIRONMENT == 'development') {
				App::debug($this->translation->translate("WARNING - You passed a string to the search function (array expected)"));
			}
		}
		
		return $this;
	}
	
	/* 
	 * Setting the order of results 
	 *
	 * @param array $sort Configuration by field ($field=>$direction)
	 *
	 * @return object $this Class object
	 */
	
	public function sort($sort = array()) {
		$this->find_params['sort'] = array();
		
		if (is_array($sort) && !empty($sort)) {
			foreach($sort as $field=>$direction) {
				switch(strtolower($direction)) {
					case 'asc':
						$sort[$field] = 1;
						break;
					case 'desc':
						$sort[$field] = -1;
						break;
				}
			}
		
			$this->find_params['sort'] = $sort;
		}
		elseif (!empty($sort)) {
			if (ENVIRONMENT == 'development') {
				App::debug($this->translation->translate("WARNING - You passed a string to the sort function (array expected)"));
			}
		}
		
		return $this;
	}
	
	/* 
	 * Skipping first results
	 *
	 * @param int $skip Number of records to skip
	 *
	 * @return object $this Class object
	 */
	
	public function skip($skip = 0) {
		$this->find_params['skip'] = 0;
		
		if (is_numeric($skip) && $skip >=0) {
			$this->find_params['skip'] = $skip;
		}
		else {
			if (ENVIRONMENT == 'development') {
				App::debug($this->translation->translate("WARNING - You passed a wrong parameter to the skip function (positive integer expected)"));
				die();
			}
		}
		
		return $this;
	}
	
	/* 
	 * Limiting results
	 *
	 * @param int $limit Maximum number of records
	 *
	 * @return object $this Class object
	 */
	
	public function limit($limit = 30) {
		$this->find_params['limit'] = 30;
		
		if (is_numeric($limit) && $limit >=0) {
			$this->find_params['limit'] = $limit;
		}
		else {
			if (ENVIRONMENT == 'development') {
				App::debug($this->translation->translate("WARNING - You passed a wrong parameter to the limit function (positive integer expected)"));
				die();
			}
		}
		
		return $this;
	}
	
	/* 
	 * Paginating results
	 *
	 * @param int $page_id Current page number
	 * $param int $per_page Number of records per page
	 *
	 * @return object $this This object
	 */
	
	public function paginate($page_id = 1,$per_page = 10) {
		$this->find_params['pagination'] = array();
		
		if (is_numeric($page_id) && $page_id > 0 && is_numeric($per_page) && $per_page > 0) {
			$this->find_params['pagination'] = array(
				'page_id'  => $page_id,
				'per_page' => $per_page
			);
		}
		else {
			if (ENVIRONMENT == 'development') {
				App::debug($this->translation->translate("WARNING - You passed wrong parameters to the paginate function (positive integers expected for both parameters)"));
				die();
			}
		}
		
		return $this;
	}
	
	/* 
	 * Executing the find request 
	 *
	 * @return bool true|false
	 */
	
	public function find($excluded_fields = true) {
	   $fields = array();
	    
	    // Excluding fields from the request
	    if ($excluded_fields === true && isset($this->excluded_fields) && !empty($this->excluded_fields)) {
		    foreach($this->excluded_fields as $field) {
			    $fields[$field] = false;
		    }
	    }
	    
	    // Searching with criterias
		$request = $this->entity->find($this->find_params['criterias'],$fields);
		
		// Sorting
		if (!empty($this->find_params['sort'])) {
			$request->sort($this->find_params['sort']);
		}
		
		// Pagination or skip/limit
		if (!empty($this->find_params['pagination'])) {
			extract($this->find_params['pagination']);
			$range = $this->setPagination($page_id,$per_page,$this->count($this->find_params['criterias']));
			$request->skip($range[0]);
			$request->limit($range[1]);
		}
		else {
			if (!empty($this->find_params['skip'])) {
				$request->skip($this->find_params['skip']);
			}
			
			if (!empty($this->find_params['limit'])) {
				$request->limit($this->find_params['limit']);
			}
			
			$this->results_nb = $request->count();
		}
		
		// Clearing find parameters
		$this->find_params = array();
		
		if ($this->fields !== null) {
			return $request;
		}
		else {
			return false;
		}
	}  
	
	/*
	 * Getting the number of results
	 *
	 * @return int Number of results from the last find request
	 */
	
	public function getResultsNb() {
		return $this->results_nb;
	}
	
	/*
	 * Getting the number of records in the table
	 *
	 * @param array $criterias Criterias for the research
	 *
	 * @return int|false Number of records or false
	 */
	
	public function count($criterias = array()) {
		if (is_array($criterias)) {
			foreach($criterias as $field=>$value) {
				if (is_array($value)) {
					$criterias[$field] = new MongoRegex("/" . $value[0] . "/i");
				}
			}
		
			$res = $this->entity->count($criterias);
			
			if ($res['err'] === null) {
				return $res;
			}
			else {
				return false;
			}
		}
		elseif (!empty($criterias)) {
			if (ENVIRONMENT == 'development') {
				App::debug($this->translation->translate("WARNING - You passed a string to the count function (array expected)"));
			}
		}
	}
	
	/*
	 * Setting the pagination
	 *
	 * @param int $page_id Current page number
	 * @param int $per_page Number of records per page
	 * @param int $count Total number of records
	 *
	 * @return array $range Skip and limit values
	 */
	
	public function setPagination($page_id,$per_page,$count,$prefix='') {
		$this->results_nb = $count;
		
		$start = ($page_id * $per_page) - $per_page;
		
		$range = array($start,$per_page);
		
		if (file_exists(CORE.'core.Pagination.php')) {
			require_once(CORE.'core.Pagination.php');
			$p = new Pagination($page_id,$per_page,$count);
			$this->pagination = $p->display($prefix);
			
			return $range;
		}
		else {
			if (ENVIRONMENT == 'development') {
				App::debug($this->translation->translate("WARNING - The core file 'core.Pagination.php' is missing. Impossible to manage pagination. Default values returned."));
			}
		
			return array(0,10);
		}
	}
	
	/*
	 * Getting the pagination
	 *
	 * @return html Pagination list in HTML
	 */
	
	public function getPagination() {
		return $this->pagination;
	}
	
	/*
	 * Saving a document in the collection (new document or loaded document)
	 *
	 * @param $replace Replacing every field value or not (false by default)
	 *
	 * @return bool true|false
	 */
	 
	 public function save($replace = false) {
		if (!isset($this->fields['_id'])) {
			$res = $this->entity->insert($this->fields);
		}
		else {
			$id = $this->fields['_id'];
			unset($this->fields['_id']);
			
			if ($replace === true) {
				$res = $this->entity->update(array('_id'=>new MongoId($id)),$this->fields);
			}
			else {
				$res = $this->entity->update(array('_id'=>new MongoId($id)),array('$set'=>$this->fields));
			}
			
			$this->fields['_id'] = $id;
		}
		
		if ($res['err'] === null) {
			return $this->fields['_id'];
		}
		else {
			return false;
		}
	}
	
	/*
	 * Updating one or many documents in the collection
	 *
	 * @param $conditions Criterias for selecting documents to update
	 * @param $replace Replacing every field value or not (false by default)
	 *s
	 * @return bool true|false
	 */
	
	public function update($conditions = array(),$replace = false) {
		if ($replace === true) {
			$res = $this->entity->update($conditions,$this->fields,array('multiple'=>true));
		}
		else {
			$res = $this->entity->update($conditions,array('$set'=>$this->fields),array('multiple'=>true));
		}
		
		if ($res['err'] === null) {
			return $this->fields['_id'];
		}
		else {
			return false;
		}
	}
		
	/*
	 * Removing the loaded document from the collection
	 *
	 * @return bool true|false
	 */
	
	public function remove() {
		$id = $this->fields['_id'];
		
		$res = $this->entity->remove(array('_id' => new MongoId($id)));
		
		if ($res['err'] === null) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/*
	 * Loading a model class
	 *
	 * @param string $model_name Name of the model file to load
	 */
	 
	 public function loadModel($model_name) {
		 $model_path = MODEL.'model.'.$model_name.'.php';
		 
		 if (file_exists($model_path)) {
			 require_once($model_path);
		 }
		 else {
    		 if (ENVIRONMENT == 'development') {
				App::debug($this->translation->translate("The model file %model% doesn't exists",$model_path,false,'sybil','debug'));
				die();
			}
		 }
	 }
			
}