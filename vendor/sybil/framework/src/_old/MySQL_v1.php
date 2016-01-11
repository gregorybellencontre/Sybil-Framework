<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

use Sybil\Config;
use Sybil\Pagination;
use Sybil\App;
use Sybil\Translation;
use PDO;
use PDOException;
use FluentPDO;

/**
 * The abstract model class provides query methods 
 * for children models. (MySQL version)
 *
 * @author Grégory Bellencontre
 */
abstract class Model 
{
	private $db = null;
	private $entity = null;
	private $primary_key = 'id';
	private $excluded_fields = [];
	private $fields = [];
	private $results_nb = null;
	private $pagination = null;
	private $request = null;
	private $debug = false;
	private $translation = null;
	
	/*
	 * Class constructor.
	 */
	
	public function __construct() 
	{
		$this->db = new FluentPDO(Database::getObject());
		
		// FPDO debug mode
		
		if ($this->debug == true) {
    		$this->fpdo->debug = function($query) {
    		    $data = array(
    		      'query' => $query->getQuery(false),
    		      'params' => implode(', ', $query->getParameters()),
    		      'rowCount' => $query->getResult() ? $query->getResult()->rowCount() : 0
    		    );
    		
            	App::debug($data);
            };
        }
        
        // Entity name
		
		if ($this->entity == null) {
		    $split = explode('\\',get_class($this));
			$this->entity = strtolower(str_replace('Model','',end($split)));
		}
		
		// Getting table fields name
		
		$fields = $this->db->getPDO()->query("SHOW COLUMNS FROM " . $this->entity);
		
		$row = $fields->fetchAll();
		
		foreach($row as $key=>$field) {
			$this->fields[$field['Field']] = '';
		}
		
		// Translation tool
		
		if ($this->translation === null) {
    	   $this->translation = new Translation(['bundle' => false, 'theme' => 'sybil', 'domain' => 'database']);
    	}
	}
	
	/*
	 * Performs connection to the database.
	 */
	
	public static function connect() 
	{
		$auth = Config::$databases[Config::$db_to_use];
		
		try {
			return new PDO('mysql:host='.$auth['host'].';dbname='.$auth['dbname'],$auth['login'],$auth['pass'],array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
		}
		catch(PDOException $e) {
			return false;
		}
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
	 * @param bool $sanitize Sanitize string or not (true by default)
	 *
	 * @return bool true
	 */
	
	public function set($field_name,$field_value,$sanitize=true) {
		$this->fields[$field_name] = $sanitize === true ? filter_var($field_value,FILTER_SANITIZE_SPECIAL_CHARS) : $field_value;
		return true;
	}
	
	/*
	 * Loading a record with its ID
	 *
	 * @param integer $id ID of the record to find
	 *
	 * @return bool true|false
	 */
	
	public function load($id = null) {
		if ($id != null) {
		    $fields = implode(',',array_diff(array_keys($this->fields),$this->excluded_fields));
			
			$query = $this->sql->from($this->entity)->select(null)->select($fields)->where('id=?',$id);
			$res = $query->fetchAll();
			
			if (!empty($res)) {
				$this->fields = $res[0];
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/*
	 * Finding one or many records
	 */
	
	/* 
	 * Adding criterias 
	 *
	 * @param mixed $criterias Criterias for the request
	 *
	 * @return object $this This object
	 */
	
	public function find($criterias = null) {
	    $args = func_get_args();
	    
	    if (!empty($args) && $criterias != null) {
		    $this->request['where'][] = $args;
		}
		
		return $this;
	}
	
	/* 
	 * Adding fields 
	 *
	 * @param mixed $fields Fields to fetch
	 *
	 * @return object $this This object
	 */
	
	public function fetch($fields = null) {
		$this->request['fields'] = $fields;
		
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
		$this->request['orderBy'] = $sort;
		
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
		$this->request['offset'] = 0;
		
		if (is_numeric($skip) && $skip >=0) {
			$this->request['offset'] = $skip;
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
		$this->request['limit'] = 30;
		
		if (is_numeric($limit) && $limit >=0) {
			$this->request['limit'] = $limit;
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
		$this->request['pagination'] = array();
		
		if (is_numeric($page_id) && $page_id > 0 && is_numeric($per_page) && $per_page > 0) {
			$this->request['pagination'] = array(
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
	
	public function execute($excluded_fields = true) {
		// Pagination
		
		if (!empty($this->request['pagination'])) {
			extract($this->request['pagination']);
			$range = $this->setPagination($page_id,$per_page,$this->count($this->request['where']));
			$this->request['offset'] = $range[0];
			$this->request['limit'] = $range[1];
		}
		
		// Fields exclusion
		
		if ($excluded_fields === true) {
			$fields = $this->entity . '.' . implode(',' . $this->entity . '.',array_diff(array_keys($this->fields),$this->excluded_fields));
		}
		else {
			$fields = $this->entity . '.' . implode(',' . $this->entity . '.',array_keys($this->fields));
		}
		
		// Executing the request
		
		$query = $this->sql->from($this->entity)->select(null)->select($fields);
		
		if (isset($this->request['fields']) && !empty($this->request['fields'])) {
            $query->select($this->request['fields']);
		}
		
		if (!empty($this->request['where'])) {
    		foreach($this->request['where'] as $where) {
        		if (!empty($where)) {
            		call_user_func_array(array($query,'where'),$where);
        		}
    		}
		}

		if (isset($this->request['orderBy']) && !empty($this->request['orderBy'])) {
            $query->orderBy($orderBy);
		}
		
		if (isset($this->request['offset']) && !empty($this->request['offset'])) {
    		$query->offset($this->request['offset']);
		}
		
		if (isset($this->request['limit']) && !empty($this->request['limit'])) {
    		$query->limit($this->request['limit']);
		}
		
		$results = $query->fetchAll();
		
		if (count($results) != 0) {
		    if (isset($this->request['pagination'])) {
		   		$this->results_nb = $this->count($this->request['where']);
		    }
		    else {
    		    $this->results_nb = $this->count($results);
		    }
		    
			return $results;
		}
		else {
		    $this->results_nb = 0;
			return array();
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
	 * @param mixed $criterias Criterias for the request
	 *
	 * @return int|false Number of records or false
	 */
	
	public function count($criterias = null) {
		$args = func_get_args();
	   
	    if (!empty($args) && $criterias != null) {
    	    $this->request['where'][] = $args;
		}
		
	    $query = $this->sql->from($this->entity)->select(null)->select('COUNT(*) AS total');   
	    
	    if (isset($this->request['where'])) {
    	    foreach($this->request['where'] as $where) {
    	        if (!empty($where)) {
        		    call_user_func_array(array($query,'where'),$where);
        		}
    		}
		}
		
		$results = $query->fetchAll();
		
		return $results[0]['total'];
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
				die();
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
	 * Saving a record in the table (new record or loaded record)
	 *
	 * @return bool true|false
	 */
	 
	 public function save() {
		if (empty($this->fields['id'])) {
		    $query = $this->sql->insertInto($this->entity)->values($this->fields)->execute();
		}
		else {
		    $query = $this->sql->update($this->entity)->set($this->fields)->where('id',$this->get('id'))->execute();
		}
		
		if ($query !== false) {
			return $this->db->lastInsertId();
		}
		else {
			return false;
		}
	 }
		
	/*
	 * Updating one or many records
	 *
	 * @param mixed $criterias Criterias for the request
	 *
	 * @return bool true|false
	 */
	
	public function update($criterias = null) {
	    $args = func_get_args();
	   
	    $fields = array();
	    
	    foreach($this->fields as $key=>$field) {
    	    if (!empty($field)) {
        	    $fields[$key] = $field;
    	    }
	    }
	   
		$query = $this->sql->update($this->entity)->set($fields);
		
		call_user_func_array(array($query,'where'),$args);
		
		$query->execute();
        
        if ($query !== false) {
        	return true;
        }
        else {
        	return false;
        }
	}
	
	/*
	 * Removing one or many records
	 *
	 * @param mixed $criterias Criterias for the request
	 *
	 * @return bool true|false
	 */
	
	public function remove($criterias = null) {
        if ($criterias === null) {
            if ($this->get('id') != '') {
                $query = $this->sql->deleteFrom($this->entity)->where('id',$this->get('id'))->execute();
            }
        }
        else {
           $args = func_get_args();
           $query = $this->sql->deleteFrom($this->entity);
           call_user_func_array(array($query,'where'),$args);
           $query->execute();
        }
        
        if ($query !== false) {
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