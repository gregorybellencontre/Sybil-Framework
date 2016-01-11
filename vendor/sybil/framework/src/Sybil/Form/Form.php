<?php
/*
 * Form class using the Form builder and Models to generate forms
 *
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

abstract class Form {
    var $model = null;
    var $model_name = null;
    var $entity = null;
    var $form = null;
    var $errors = null;
    var $translation = null;
    
    /*
	 * Class constructor
	 */
    
    public function __construct($record) {
    	if ($this->model == null) {
    	    $split = explode('\\',get_class($this));
	    	$this->model = str_replace('Form','',end($split));
	    }
	    
	    $this->model_name = '\Sybil\Model\\' . $this->model.'Model';
	    
	    if ($record != null) { 
	        $this->loadModel();
	        $this->entity = new $this->model_name();
	    	$this->entity->load($record);
		}
		
		if ($this->translation === null) {
    	   $this->translation = new Translation(false,'sybil','debug');
    	}
    }
    
    /*
	 * Loading a model class
	 *
	 * @param string $model_name Name of the model file to load
	 *
	 * @return object An instance of the object from class passed in parameter (otherwise, no return)
	 */
	 
	 public function loadModel($model_name = null) {
		 $model_path = $model_name == null ? BUNDLE.BUNDLE_NAME.'/model/'.$this->model.'Model.php' : BUNDLE.BUNDLE_NAME.'/model/'.$model_name.'Model.php';
		 
		 if (file_exists($model_path)) {
			 require_once($model_path);
			 
			 if ($model_name != null) {
			     $model_class_name = '\Sybil\Model\\' . $model_name . 'Model';
				 return new $model_class_name();
			 }
		 }
		 else {
			 if (ENVIRONMENT == 'production') {
				$this->setError();
			}
			else {
				App::debug($this->translate("The model file %model% doesn't exists",$model_path,false,'sybil','debug'));
				die();
			}
		 }
	 }
	 
	 /*
	 * Generating an array for FormBuilder dropdown helper
	 *
	 * @param array $data Data to parse
	 * @param array $fill Fields to use (value,label)
	 * @param string $selected Value to auto-select
	 *
	 * @return array $d Array generated
	 */
	 
	 public function dropdown($data, $fill, $selected = null) {
	 	 $d = array();
	 	
		 foreach($data as $key=>$value) {
	    	$d[] = array(
	    		'value' => $value[$fill[0]],
	    		'label' => $value[$fill[1]],
	    		'selected' => ($selected == $value[$fill[0]] ? true : false)
	    	);
    	}
    	
    	return $d;
	 } 

	 /*
	 * Validating a form
	 *
	 * @param array $validation_rules Rules of validation
	 *
	 * @return array Fields values and errors
	 */

	 public function validate($form) {
	 	$this->$form();
	 	$validation_rules = $this->getRules();
	 	
	 	$fields = $_POST['form_data'];
	 	$errors = array();
	 	
	 	$this->useDomain('form');
	 	
	 	foreach($validation_rules as $field=>$rules) {
		 	if (isset($rules['required']) && $rules['required'] === true) {
			 	if (!isset($rules['file']) && (!isset($fields[$field]) || empty($fields[$field]))) {
				 	$errors[$field]['error'] = isset($rules['message']) && !empty($rules['message']) ? $rules['message'] : $this->translate("This field is required") . '.';
			 	}
		 	}
		 	
		 	if (isset($rules['pattern']) && $rules['pattern'] !== null) {
			 	if (!preg_match('#^' . $rules['pattern'] . '$#',$fields[$field])) {
				 	$errors[$field]['error'] = true;
			 	}
		 	}
		 	
		 	if (isset($rules['min']) && is_numeric($rules['min']) && $rules['min'] > 0) {
			 	if (!isset($fields[$field]) || count($fields[$field]) < $rules['min']) {
				 	$errors[$field]['error'] = $this->translation->translate("You have to check a minimum of %count% box",$rules['min']) . ' (' . $this->translate("%count% checked",count($fields[$field]) || 0) . ').';
			 	}
		 	}
		 	
		 	if (isset($rules['equals']) && $rules['equals'] !== null) {
			 	if ($fields[$field] !== $fields[$rules['equals']]) {
    			 	$errors[$rules['equals']]['error'] = isset($rules['message']) && !empty($rules['message']) ? $rules['message'] : $this->translation->translate("The value of this field must match with the next field one") . '.';
    			 	$errors[$field]['error'] = isset($rules['message']) && !empty($rules['message']) ? $rules['message'] : $this->translate("The value of this field must match with the previous field one") . '.';
			 	}
		 	}
	 	}
	 	
	 	if(!empty($_FILES)) {
		 	foreach($_FILES as $field=>$data) {
		 	    if (!empty($data['name'])) {
    			 	if (isset($validation_rules[$field]['required']) && $validation_rules[$field]['required'] === true) {
    				 	if (empty($data['name'])) {
    					 	$errors[$field]['error'] = isset($validation_rules[$field]['message']) && !empty($validation_rules[$field]['message']) ? $validation_rules[$field]['message'] : $this->translate("This field is required") . '.';
    				 	}
    			 	}
    			 	
    			 	if (isset($validation_rules[$field]['maxsize']) && $validation_rules[$field]['maxsize'] > 0) {
    			 		$maxsize = App::getOctets($validation_rules[$field]['maxsize']);
    			 	
    				 	if ($data['size'] > $maxsize) {
    					 	$errors[$field]['error'] = isset($validation_rules[$field]['message']) && !empty($validation_rules[$field]['message']) ? $validation_rules[$field]['message'] : $this->translate("Your file is bigger than the authorized size") . '.';
    				 	}
    			 	}
    			 	
    			 	if (isset($validation_rules[$field]['formats']) && !empty($validation_rules[$field]['formats'])) {
    				 	if (!in_array($data['type'],$validation_rules[$field]['formats'])) {
    					 	$errors[$field]['error'] = isset($validation_rules[$field]['message']) && !empty($validation_rules[$field]['message']) ? $validation_rules[$field]['message'] : $this->translate("The format of your file is not authorized") . '.';
    				 	}
    			 	}
    			 	
    			 	if (isset($validation_rules[$field]['min_dimensions']) && !empty($validation_rules[$field]['min_dimensions'])) {
    				 	$size = getimagesize($data['tmp_name']);
    				 	
    				 	if ($size) {
    				 		if ($size[0] <= $validation_rules[$field]['min_dimensions'][0] || $size[1] <= $validation_rules[$field]['min_dimensions'][1]) {
    					 		$errors[$field]['error'] = isset($validation_rules[$field]['message']) && !empty($validation_rules[$field]['message']) ? $validation_rules[$field]['message'] : $this->translate("Your image does not respect the minimum dimensions") . '.';
    				 		}
    				 	}
    			 	}
    			 	
    			 	if (isset($validation_rules[$field]['max_dimensions']) && !empty($validation_rules[$field]['max_dimensions'])) {
    				 	$size = getimagesize($data['tmp_name']);
    				 	
    				 	if ($size) {
    				 		if ($size[0] > $validation_rules[$field]['max_dimensions'][0] || $size[1] > $validation_rules[$field]['max_dimensions'][1]) {
    					 		$errors[$field]['error'] = isset($validation_rules[$field]['message']) && !empty($validation_rules[$field]['message']) ? $validation_rules[$field]['message'] : $this->translate("Your image does not respect the maximum dimensions") . '.';
    				 		}
    				 	}
    			 	}
			 	}
		 	}
	 	}
	 	
	 	if (!empty($errors)) {
	 		$this->errors = $errors;
	 		
	 		return false;
	 	}
	 	else {
		 	return true;
	 	}
	 }
	 
	 /*
	 * Setting the controller
	 *
	 * @param string $controller Controller to use for translation
	 */
	
	public function useController($controller) {
        $this->translation->useController($controller);	
	}
    
    /*
	 * Setting the theme
	 *
	 * @param string $theme Theme to use for translation
	 */
	
	public function useTheme($theme) {
        $this->translation->useTheme($theme);
	}
	
	/*
	 * Setting the domain
	 *
	 * @param string $domain Domain to use for translation
	 */
	
	public function useDomain($domain) {
    	$this->translation->useDomain($domain);
	}
	
	/*
	 * Translating a string
	 *
	 * @param string $source Source string
	 * @param mixed $data Data for variable replacement or count number (for pluralization)
	 * @param string $controller Controller where to search the translation file
	 * @param string $theme Theme where to search the translation file
	 * @param string $domain Domain to use
	 *
	 * @return string Translated string
	 */
	
	public function translate($source,$data=null,$controller=null,$theme=null,$domain=null) {
	    if (is_array($controller)) {
    	   list($controller,$theme,$domain) = $controller; 
	    }
	    else {
    	   $controller = $controller === null ? null : $controller;
    	}
	
	    if ($this->translation === null) {
	       //App::debug(array($controller,$theme,$domain));
    	   $this->translation = new Translation($controller,$theme,$domain);
    	   return $this->translation->translate($source,$data);
    	}
    	else {
            return $this->translation->translate($source,$data,$controller,$theme,$domain);	
    	}
	}

	 /*
	 * Check if data is attached to the form
	 *
	 * @return bool true|false
	 */

	 public function isFilled() {
	    $fields = $this->entity ? $this->entity->getAll() : array();
	    
	 	return !empty($fields);
	 }
	 
	 /*
	 * Getting entity
	 *
	 * @return object Model object with data
	 */

	 public function getEntity() {
	 	return $this->entity;
	 }

	 /*
	 * Getting errors
	 *
	 * @return array Form errors
	 */

	 public function getErrors() {
	 	return $this->errors;
	 }
	 
	 /*
	 * Setting error
	 *
	 * @param string Field name
	 * @param string Message to display
	 */

	 public function setError($field,$message) {
	 	return $this->errors[$field]['error'] = gettext($message);
	 }
	 
	 /*
	 * Getting HTML form
	 *
	 * @return html Form in HTML
	 */
	 
	 public function getHTML() {
		 return $this->form['form'];
	 }
	 
	 /*
	 * Getting form rules
	 *
	 * @return array Form rules
	 */
	 
	 public function getRules() {
		 return $this->form['rules'];
	 }
	 
	 /*
	 * Getting form files
	 *
	 * @return array Form files
	 */
	 
	 public function getFiles() {
		 return $this->form['files'];
	 }
}