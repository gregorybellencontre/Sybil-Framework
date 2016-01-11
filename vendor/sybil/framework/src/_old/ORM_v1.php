<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

/**
 * The ORM class reads model comments, generates
 * a schema, the class methods, and updates the database.
 *
 * @author Grégory Bellencontre
 */
final class ORM 
{
	private $column_types = ['integer','string','text','boolean','date','datetime','timestamp','array','object'];
	private $model_files = [];
	private $assoc_files = [];
	private $files_id = [];
	private $file_path = null;
	private $file_name = null;
	private $file_data = null;
	private $file_schema = [];
	private $file_model_id = [];
	private $file_elements = [
		'namespace' => null,
		'uses' => null,
		'class_name' => null,
		'properties' => [],
		'getters_setters' => null,
		'objects_methods' => null,
		'custom_methods' => null
	];
	
	/**
	 * Main ORM function.
	 * Triggers update on every model.
	 */
	
	public function update() 
	{
		$this->loadModels();
		
		$this->app_schemas = [];
		
		foreach($this->model_files as $model_name=>$model_file) {
			$this->file_path = $model_file;
			
			if (file_exists($model_file)) {
				// Storing data without the empty lines
				$this->file_data = preg_replace('/^[ \t]*[\r\n]+/m', '', file_get_contents($model_file));
				$this->file_name = str_replace('.php','',$model_name);
				
				$this->file_model_id = [];
				
				$this->loadModelElements();
				$this->createSchema();
				$this->createGettersAndSetters();
				$this->createMethods();
				$this->saveFile();
			}
		}
		
		$this->createAssocFiles();
		
		echo "Schema have been updated.\n";
		die();
	}
	
	/**
	 * Loads every model stored in bundle directory.
	 */
	
	public function loadModels() 
	{
		$bundles = array_diff(scandir('bundle'), array('.', '..'));
		
		if (!empty($bundles)) {
			foreach($bundles as $bundle_name) {
				if (is_dir('bundle/'.$bundle_name.'/model')) {
					$bundle_models = array_diff(scandir('bundle/'.$bundle_name.'/model'), array('.', '..'));
					
					if (!empty($bundle_models)) {
						foreach($bundle_models as $model_file) {
							if (!is_dir('bundle/'.$bundle_name.'/model/'.$model_file)) {
								$this->model_files[$model_file] = 'bundle/'.$bundle_name.'/model/'.$model_file;
							}
						}	
					}
				}
			}
		}
	}
	
	/**
	 * Extracts data from a model file.
	 */
	
	public function loadModelElements()
	{
		// Namespace
		preg_match_all('/namespace (.*);/', $this->file_data,$namespaces);
		$this->file_elements['namespace'] = $namespaces[1][0];
		
		// Uses
		$this->file_elements['uses'] = ['Sybil\Model'];
		
		// Class name
		preg_match_all('/\/\*(?:.*)@ORM:entity(?:.*)extends Model(?:[ ]?)\n?\{/s', $this->file_data,$class_name);
		$this->file_elements['class_name'] = array_map('trim',explode("\n",$class_name[0][0]));
		
		// Properties
		preg_match_all('/\/\*(?:.*)@ORM:(?:id|property|object)(?:.*)\[(?:.*)\](?:.*)\*\/\n(?:.*)(?:private|protected) \$([a-z_]+);/',$this->file_data,$properties_data);
		$this->file_elements['properties'] = [];
		
		for($i=0;$i<=count($properties_data[0])-1;$i++) {
			$this->file_elements['properties'][$properties_data[1][$i]] = array_map('trim',explode("\n",$properties_data[0][$i]));
		}
		
		// Getters, setters and objects methods are initialized to null (content generated later)
		$this->file_elements['getters_setters'] = null;
		$this->file_elements['objects_methods'] = null;
		
		// Custom methods
		$regex = '/\/\* @ORM:custom_method \*\/\n(?:\s*)(?:public|private) function(?:.+?)\}/s';
		
		if (preg_match_all($regex, $this->file_data, $matches) && isset($matches[0])) {
			foreach($matches[0] as $function) {
				$this->file_elements['custom_methods'][] = preg_replace('/^.+\n/', '', $function);
			}
		}
	}
	
	/**
	 * Creates a schema using model properties.
	 */
	
	public function createSchema()
	{
		$this->file_schema = [];
		
		if (!empty($this->file_elements['properties'])) {
			foreach($this->file_elements['properties'] as $name=>$data) {
				
				$comment_regex = '/\/\*(?:.*)@ORM:(id|property|object)(?:.*)\[(.*)\](?:.*)\*\//';
				
				if (preg_match($comment_regex,$data[0])) {
					preg_match_all($comment_regex, $data[0],$comment_data);
					
					$property_data = explode(',',$comment_data[2][0]);
					
					if ($comment_data[1][0] != 'object') {
						$format_data = explode(':',$property_data[0]);
						
						if (in_array($format_data[0],$this->column_types)) {
							$format = $format_data[0];
							$maxsize = isset($format_data[1]) ? $format_data[1]*1 : null;
						}
						else {
							if (preg_match('/^([A-Z])/',$property_data[0])) {
								$format = 'index';
							}
						}
					}
					else {
						$format = 'object';
					}
					
					$this->file_schema[$name] = [
						'type' => $comment_data[1][0],
						'format' => isset($format) ? $format : 'string',
						'maxsize' => isset($maxsize) ? $maxsize : null
					];
					
					if ($comment_data[1][0] == 'id') {
						$this->file_model_id[] = [
							'name' => $name,
							'format' => $this->file_schema[$name]['format']
						];
					}
					
					if ($comment_data[1][0] != 'object') {
						if ($this->file_schema[$name]['format'] != 'index') {
							$this->file_schema[$name]['params'] = array_slice($property_data,1);
						}
						else {
							$this->file_schema[$name]['reference'] = [
								'model' => $this->getNamespace($property_data[0]),
								'property' => $property_data[1]
							];
						}
					}
					else {
						$this->file_schema[$name]['reference'] = [
							'model' => $this->getNamespace($property_data[0])
						];
					}
				}
			}
		}
		
		$this->files_id[$this->file_name] = $this->file_model_id;
		
		//print_r($this->file_schema);
	}
	
	/**
	 * Generates getters and setters using model properties.
	 */
	
	public function createGettersAndSetters()
	{
		if (!empty($this->file_schema)) {
			foreach($this->file_schema as $property_name=>$property_data) {
				if ($property_data['type'] != 'object') {
					$this->file_elements['getters_setters'].= "    public function get" . $this->toCamelCase($property_name) . "()\n    {\n";
					$this->file_elements['getters_setters'].= "        return " . '$this->' . $property_name . ";\n";
					$this->file_elements['getters_setters'].= "    }\n\n";
					
					$namespace = isset($property_data['reference']['model']) ? $property_data['reference']['model'] . ' ' : '';
					
					if (!isset($property_data['params']) || !in_array('auto',$property_data['params'])) {
						$this->file_elements['getters_setters'].= "    public function set" . $this->toCamelCase($property_name) . '(' . $namespace . '$' . $property_name . ')' . "\n    {\n";
						$this->file_elements['getters_setters'].= "        " . '$this->' . $property_name . " = " . $this->applyParams($property_name) . ";\n";
						$this->file_elements['getters_setters'].= "    }\n\n";
					}
				}
			}
		}
	}
	
	/**
	 * Generates objects methods using model properties.
	 */
	
	public function createMethods()
	{
		if (!empty($this->file_schema)) {
			foreach($this->file_schema as $property_name=>$property_data) {
				if ($property_data['type'] == 'object') {
					if (!in_array($property_data['reference']['model'],$this->file_elements['uses'])) {
						$this->file_elements['uses'][] = $property_data['reference']['model'];
					}
					
					$model_namespace = $property_data['reference']['model'];
					$file_namespace = $this->file_elements['namespace'];
					
					$model_name = explode('\\',$model_namespace);
					$model_name = end($model_name);
					
					$assoc_entities = [
						$model_namespace => $this->toCamelCase($property_name), 
						$file_namespace => $this->toCamelCase($this->file_name)
					];
					
					asort($assoc_entities);
					$assoc_name = reset($assoc_entities) . end($assoc_entities);
					
					if (!isset($this->assoc_files[$assoc_name])) {
						$this->assoc_files[$assoc_name] = [
							'namespace' => reset($assoc_entities) == $this->toCamelCase($this->file_name) ? $this->file_elements['namespace'].$this->toCamelCase($property_name) : preg_replace('/\\([A-Z][a-z]+)$/',"$1".$this->toCamelCase($this->file_name),$this->file_elements['namespace']),
							'properties' => $assoc_entities
						];
					}
					
					$this->addUse($this->assoc_files[$assoc_name]['namespace']);
					
					$model_identifier = count($this->file_model_id) == 1 ? $this->file_model_id[0]['name'] : 'id';
					
					$this->file_elements['objects_methods'].= "    /* @ORM:method */\n";
					$this->file_elements['objects_methods'].= "    public function get" . $this->toCamelCase($property_name) . "()\n    {\n";
					$this->file_elements['objects_methods'].= "        $" . $this->unCamelCase($assoc_name) . " = new " . $assoc_name . "();\n\n";
					$this->file_elements['objects_methods'].= "        return $" . $this->unCamelCase($assoc_name) . "->match(['" . strtolower($this->file_name) . '_' . $model_identifier . "'," . '$this->id' . "])->execute();\n";
					$this->file_elements['objects_methods'].= "    }\n\n";
				}
			}
		}
	}
	
	/**
	 * Constructs and saves the updated model file.
	 */
	
	public function saveFile()
	{
		$output_file = "<?php\n";
		$output_file.= "namespace " . $this->file_elements['namespace'] . ";\n\n";
		
		if (!empty($this->file_elements['uses'])) {
			foreach($this->file_elements['uses'] as $use) {
				$output_file.= "use " . $use . ";\n";
			}
			$output_file.= "\n";
		}
		
		$output_file.= $this->file_elements['class_name'][0] . "\n";
		$output_file.= $this->file_elements['class_name'][1] . "\n";
		$output_file.= isset($this->file_elements['class_name'][2]) ? $this->file_elements['class_name'][2] . "\n" : "";
		
		if (!empty($this->file_elements['properties'])) {
			foreach($this->file_elements['properties'] as $property) {
				$output_file.= "    " . $property[0] . "\n";
				$output_file.= "    " . $property[1] . "\n\n";
			}
		}
		
		$output_file.= "    /*\n     * Getters & setters.\n     */\n\n";
		
		$output_file.= $this->file_elements['getters_setters'];
		
		if (!empty($this->file_elements['objects_methods'])) {
			$output_file.= "    /*\n     * Generated methods.\n     */\n\n";
			
			$output_file.= $this->file_elements['objects_methods'];
		}
		
		$output_file.= "    /*\n     * Custom methods.\n     */\n\n";
		
		if (!empty($this->file_elements['custom_methods'])) {
			foreach($this->file_elements['custom_methods'] as $method) {
				$output_file.= "    /* @ORM:custom_method */\n";
				$output_file.= str_replace("\t","    ",$method) . "\n\n";
			}
		}
		
		$output_file.= "}\n";
		
		file_put_contents($this->file_path, $output_file);
	}
	
	/**
	 * Creates association file(s) to connect models' objects.
	 */
	
	public function createAssocFiles() 
	{
		if (!empty($this->assoc_files)) {
			foreach($this->assoc_files as $name=>$file) {
				$output_file = "<?php\n";
				$output_file.= "namespace " . $file['namespace'] . ";\n\n";
				
				foreach($file['properties'] as $namespace=>$property) {
					$output_file.= "use " . $namespace . ";\n";
				}
				
				$output_file.= "\n";
				
				$output_file.= "/* @ORM:association [" . $this->unCamelCase($name) . "] */\n";
				$output_file.= "class " . $name . " extends Model\n{\n";
				
				if (!empty($file['properties'])) {
					foreach($file['properties'] as $namespace=>$property) {
						$output_file.= "    /* @ORM:id [" . $this->getTarget($namespace) . "] */\n";
						$output_file.= "    private $" . $this->unCamelCase($property) . ";\n\n";
					}
					
					$output_file.= "    /*\n     * Getters & setters.\n     */\n\n";
					
					foreach($file['properties'] as $namespace=>$property) {
						$output_file.= "    public function get" . $this->toCamelCase($property) . "()\n    {\n";
						$output_file.= "        return " . '$this->' . $this->unCamelCase($property) . ";\n";
						$output_file.= "    }\n\n";
						
						$output_file.= "    public function set" . $this->toCamelCase($property) . '(' . $namespace . ' $' . $this->unCamelCase($property) . ')' . "\n    {\n";
						$output_file.= "        " . '$this->' . $this->unCamelCase($property) . " = " . $this->applyParams($this->unCamelCase($property)) . ";\n";
						$output_file.= "    }\n\n";
					}
				}
				
				$output_file.= "}\n";
				
				$bundle_name = $this->getBundleName($file['namespace']);
				$bundle_directory = strtolower($bundle_name);
				$directory_path = 'bundle/'.$bundle_directory.'/model/';
				$file_path = $directory_path.$name.'.php';
				
				if (!file_exists($file_path)) {
					if (!is_dir($directory_path)) {
						mkdir($directory_path);
					}
				
					file_put_contents($file_path, $output_file);
				}
				
				echo $output_file;
			}
		}
	}
	
	/**
	 * Returns a namespace, generated from a target string
	 *
	 * @param string $target ["Bundle:ModelName", or "Bundle" if model has the same name]
	 *
	 * @return string The generated namespace
	 */
	
	public function getNamespace($target) 
	{
		require_once('app/Config.php');
		
		if (preg_match('/:/',$target)) {
			$target_data = explode(':',$target);
			
			return Config::$app_namespace . '\\' . $target_data[0] . 'Bundle\Model\\' . $target_data[1];
		}
		else {
			return Config::$app_namespace . '\\' . $target . 'Bundle\Model\\' . $target;
		}
	}
	
	/**
	 * Returns a target, generated from a namespace
	 *
	 * @param string $namespace
	 *
	 * @return string The generated target
	 */
	
	public function getTarget($namespace) 
	{
		preg_match_all('/(?:[A-Z][a-z]+)\\\([A-Z][a-z]+)Bundle\\\Model\\\([A-Z][a-z]+)/', $namespace, $data);
		
		if (isset($data[1][0]) && isset($data[2][0])) {
			return $data[1][0] == $data[2][0] ? $data[1][0] : $data[1][0] . ':' . $data[2][0];
		}
	}
	
	/**
	 * Returns a bundle name, extracted from a namespace
	 *
	 * @param string $namespace
	 *
	 * @return string The bundle name
	 */
	
	public function getBundleName($namespace) 
	{
		preg_match_all('/(?:[A-Z][a-z]+)\\\([A-Z][a-z]+)Bundle\\\Model\\\(?:[A-Z][a-z]+)/', $namespace, $data);
		
		if (isset($data[1][0])) {
			return $data[1][0];
		}
	}
	
	/**
	 * Applies function(s) to a property, using properties parameters.
	 *
	 * @param string $property_name Name of the property
	 *
	 * @return string The updated property name
	 */
	
	public function applyParams($property_name) 
	{
		$params = $this->file_schema[$property_name]['params'] or array();
		
		$property_name = '$' . $property_name;
		
		if (!empty($params)) {
			if(in_array('encrypt',$params)) {
				$this->addUse('Sybil\App',false);
				$property_name = 'App::hash(' . $property_name . ')';
			}
		}
		
		return $property_name;
	}
	
	/**
	 * Adds a namespace in model uses.
	 *
	 * @param string $namespace The namespace to add
	 * @param bool $next Add the use, following the existing ones
	 */
	
	public function addUse($namespace,$next=true) 
	{
		if (!in_array($namespace,$this->file_elements['uses'])) {
			if ($next) {
				$this->file_elements['uses'][] = $namespace;
			}
			else {
				array_unshift($this->file_elements['uses'],$namespace);	
			}
		}
	}
	
	/**
	 * Converts an underscore string to CamelCase
	 *
	 * @param string $string Underscored string
	 *
	 * @return string CamelCase string
	 */
	
	public function toCamelCase($string) 
	{
		return str_replace(' ','',ucwords(str_replace('_',' ',$string)));
	}
	
	/**
	 * Converts a CamelCase string to an undescored one.
	 *
	 * @param string $string CamelCase string
	 *
	 * @return string Underscored string
	 */
	
	public function unCamelCase($string)
	{
		return strtolower(preg_replace('/(?|([a-z\d])([A-Z])|([^\^])([A-Z][a-z]))/', '$1_$2', $string));
	}
	
}