<?php
/**
 * ORYGIN Framework
 * (c) 2015 Grégory Bellencontre
 */

namespace Sybil\ORM;

use Sybil\Config;
use Sybil\Yaml;
use Sybil\App;
use Sybil\Collection;

/**
 * The ORM class generates model files and database elements
 * using the schema of each bundle.
 *
 * @author Grégory Bellencontre
 */
final class ORM 
{
	private $app_schema = [];
	private $file_uses = [];
	private $last_model_directory = null;
	
	/**
	 * Main ORM function.
	 * Triggers update of each model file.
	 */
	
	public function update() 
	{
		require_once('app/Config.php');
		
		$this->loadSchemas();
		$this->setObjects();
		
		if (!empty($this->app_schema)) {
			foreach($this->app_schema as $bundle_name=>$models) {
				if (!empty($models)) {
					foreach($models as $model_name=>$model_data) {
						$this->createModelFile($bundle_name,$model_name,$model_data);
					}
				}
			}
		}
		
		$app_entities = $this->getEntities();
		
		$dbms_class = '\Sybil\ORM\\'.Config::$dbms_to_use . '\SchemaBuilder';
		$dbms = new $dbms_class();
		$dbms->build($app_entities);
			
		echo "Schema has been updated successfully.\n";
	}
	
	/**
	 * Generates an array containing entities,
	 * before sending them to the ORM DBMS.
	 *
	 * @return array Application entities
	 */
	
	public function getEntities()
	{
		$entities = [];
		
		if (!empty($this->app_schema)) {
			foreach($this->app_schema as $bundle_name=>$models) {
				if (!empty($models)) {
					foreach($models as $model_name=>$model_data) {
						if (!empty($model_data)) {
							foreach($model_data as $property_name=>$property_data) {
								if ($property_name != '_objects') {
									$entities[$bundle_name][$model_name][$property_name] = $property_data;
								}
							}
						}
					}
				}
			}
		}
		
		return $entities;
	}
	
	/**
	 * Loads, parses, and stores every bundle schema file.
	 */
	
	public function loadSchemas() 
	{
		$bundles = array_diff(scandir('bundle'), array('.', '..'));
		
		if (!empty($bundles)) {
			foreach($bundles as $bundle_name) {
				$file_path = 'bundle/'.$bundle_name.'/schema.yml';
				
				if (file_exists($file_path)) {
					$file_content = file_get_contents($file_path);
					$this->app_schema[$bundle_name] = Yaml::parse($file_content)->toArray();
				}
			}
		}
	}
	
	/**
	 * Generates a model file.
	 *
	 * @param string $bundle_name Name of the bundle
	 * @param string $model_name Name of the model
	 * @param array $model_data Model data
	 *
	 * @return bool true if file was created, false if not.
	 */
	
	public function createModelFile($bundle_name,$model_name,$model_data)
	{
		$model_id = $this->getIdentifier($model_data);
	
		$this->file_uses = [];
	
		$bundle_directory = 'bundle/'.$bundle_name;
		
		if (is_dir($bundle_directory)) {
			$model_directory = $bundle_directory.'/model';
			
			if (!is_dir($model_directory)) {
				mkdir($model_directory);
			}
			else {
				// Model directory cleanup
				
				if ($model_directory != $this->last_model_directory) {
					$model_directory_files = glob($model_directory.'/*.*');
					
					if (!empty($model_directory_files)) {
						foreach($model_directory_files as $file){
						    unlink($file);
						}
					}
				}
			}
			
			$this->last_model_directory = $model_directory;
			
			$model_file = $model_directory.'/'.App::toCamelCase($model_name).'.php';
			
			// Custom methods backup.
			
			if (file_exists($model_file)) {
				$file_content = file_get_contents($model_file);
				
				$regex = '/(?:(?:final )?)class (?:[A-Za-z_]+) extends Model\n\{(?:.*)\/\*(?:\n)(?:[ ]+)\* Custom methods.(?:[ ]*)(?:\n)(?:[ ]+)\*\/(?:[ ]*)(?:\n)(.*)\}/s';
		
				if (preg_match_all($regex, $file_content, $matches) && isset($matches[1][0]) && !preg_match('/^\n+$/',$matches[1][0])) {
					$custom_methods = $matches[1][0];
				}
			}
			
			$output_file = "<?php\n";
			$output_file.= "namespace " . $this->getNamespace($bundle_name) . ";\n\n";
			
			$output_file.= "/* use_emplacement */\n"; // Use lines can be injected at any moment with addUse() method.
			
			if (!empty($model_data)) {
				$output_file.= "final class " . App::toCamelCase($model_name) . " extends \Sybil\ORM\DBMS\Model\n";
				$output_file.= "{\n";
				
				if (isset($model_data['_params']['alias'])) {
					$output_file.= "    protected " . '$entity_name' . " = '" . $model_data['_params']['alias'] . "';\n";
				}
				else {
					$output_file.= "    protected " . '$entity_name' . " = '" . App::unCamelCase($model_name) . "';\n";
				}
				
				$output_file.= "    protected " . '$identifier' . " = ";
				
				if (count($model_id) == 1) {
					$output_file.= "'" . $model_id[0] . "';\n";
				}
				else {
					$model_id_array = "[";
					
					foreach($model_id as $id) {
						$model_id_array.= "'" . $id . "',";
					}
					
					$model_id_array = trim($model_id_array,',');
					
					$output_file.= $model_id_array . "];\n";
				}
				
				$output_file.= "\n";
				
				foreach($model_data as $property_name=>$property_data) {
					if ($property_name != '_params' && $property_name != '_objects') {
						$output_file.= "    protected $" . $property_name . ";\n";
					}
				}
				
				$output_file.= "\n    /*\n     * Getters & setters.\n     */\n\n";
				
				foreach($model_data as $property_name=>$property_data) {
					if ($property_name != '_params' && $property_name != '_objects') {
						$output_file.= "    public function get" . App::toCamelCase($property_name) . "()\n    {\n";
						$output_file.= "        return " . '$this->' . $property_name . ";\n";
						$output_file.= "    }\n\n";
						
						if (!isset($property_data['auto']) || $property_data['auto'] == false) {
							$namespace = isset($property_data['indexOf']) ? '\\' . $this->getNamespace(isset($property_data['indexOf']['bundle']) ? $property_data['indexOf']['bundle'] : $bundle_name,$property_data['indexOf']['model']) . ' ' : '';
						
							$output_file.= "    public function set" . App::toCamelCase($property_name) . '(' . $namespace . '$' . $property_name . ')' . "\n    {\n";
							$output_file.= "        " . '$this->' . $property_name . " = " . $this->applyFunctions($property_name,$property_data) . ";\n";
							$output_file.= "    }\n\n";
						}
					}
				}
				
				if (isset($this->app_schema[$bundle_name][$model_name]['_objects'])) {
					$output_file.= "    /*\n     * Objects methods.\n     */\n\n";
					
					foreach($this->app_schema[$bundle_name][$model_name]['_objects'] as $obj_bundle_name=>$obj_models) {
						foreach($obj_models as $obj_model_name=>$obj_properties) {
							foreach($obj_properties as $obj_property_name=>$obj_property_data) {
								$namespace = $this->getNamespace($obj_bundle_name,$obj_model_name);
								$target_field = $model_id[0];
								$this->addUse($namespace);
								
								$output_file.= "    public function get" . App::pluralize(App::toCamelCase($obj_property_name)) . "(" . '$params=null' . ")\n    {\n";
								$output_file.= "        $" . $obj_model_name . " = new " . App::toCamelCase($obj_model_name) . "();\n";
								$output_file.= "        return $" . $obj_model_name . "->match(['" . $obj_property_data['link'] . "' => " . '$this->' . $target_field . "])->params(" . '$params' . ")->execute();\n";
								$output_file.= "    }\n\n";
							}
						}
					}
				}
				
				$output_file.= "    /*\n     * Custom methods.\n     */\n";
				
				$output_file.= isset($custom_methods) ? $custom_methods : '';
				
				$output_file.= "}\n";
			}
			
			$output_file = $this->applyUses($output_file);
			
			if (file_put_contents($model_file, $output_file)) {
				chmod($model_file, 0777);
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
	
	/**
	 * Generates objects methods data, inside the schema.
	 */
	
	public function setObjects() 
	{
		foreach($this->app_schema as $bundle_name=>$models) {
			if (!empty($models)) {
				foreach($models as $model_name=>$model_data) {
					if (!empty($model_data)) {
						foreach($model_data as $property_name=>$property_data) {
							if (array_key_exists('identifier', $property_data) && array_key_exists('indexOf', $property_data)) {
								$property_data['indexOf']['bundle'] = isset($property_data['indexOf']['bundle']) ? $property_data['indexOf']['bundle'] : $bundle_name;
								
								$data = $model_data;
								
								foreach($data as $data_property_name=>$data_property_data) {
									$data_property_data['link'] = $property_name;
									$data[$data_property_name] = $data_property_data;
									
									if (!array_key_exists('identifier', $data_property_data) || !array_key_exists('indexOf', $data_property_data) || $property_data['indexOf']['model'] == $data_property_data['indexOf']['model']) {
										unset($data[$data_property_name]);
									}
								}
								
								if (!empty($data) && !empty($this->app_schema[$property_data['indexOf']['bundle']][$property_data['indexOf']['model']])) {
									$this->app_schema[$property_data['indexOf']['bundle']][$property_data['indexOf']['model']]['_objects'][$bundle_name][$model_name] = $data;
								}
							}
						}
					}
					else {
						die("WARNING : Your \"" . $model_name . "\" entity is empty. Check your schema and add properties to the related entity or remove it.\n\n");
					}
				}
			}
		}
	}
	
	/**
	 * Returns a namespace, generated from a bundle name and a model name.
	 *
	 * @param string $bundle_name Bundle name
	 * @param string $model_name Model name (optional)
	 *
	 * @return string The generated namespace
	 */
	
	public function getNamespace($bundle_name,$model_name=null) 
	{
		if ($model_name == null) {
			return Config::$app_namespace . '\\' . App::toCamelCase($bundle_name) . 'Bundle\\Model';
		}
		else {
			return Config::$app_namespace . '\\' . App::toCamelCase($bundle_name) . 'Bundle\\Model\\' . App::toCamelCase($model_name);
		}
	}
	
	/**
	 * Applies function(s) to a property, using properties parameters.
	 *
	 * @param string $property_name Name of the property
	 * @param string $property_data Property data
	 *
	 * @return string The updated property name
	 */
	
	public function applyFunctions($property_name,$property_data) 
	{
		$property_name = '$' . $property_name;
		
		if (!empty($property_data)) {
			if(array_key_exists('encrypt',$property_data)) {
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
		if (!in_array($namespace,$this->file_uses)) {
			if ($next) {
				$this->file_uses[] = $namespace;
			}
			else {
				array_unshift($this->file_uses,$namespace);	
			}
		}
	}
	
	/**
	 * Replaces the use emplacement with the necessary namespaces.
	 *
	 * @param string $output_file Output PHP file
	 *
	 * @return string Updated output PHP file
	 */
	
	public function applyUses($output_file) 
	{
		$uses_list = '';
		
		if (!empty($this->file_uses)) {
			foreach($this->file_uses as $use) {
				$uses_list.= "use " . $use . ";\n";
			}
			
			$uses_list.= "\n";
		}
		
		return str_replace("/* use_emplacement */\n",$uses_list,$output_file);
	}
	
	/**
	 * Returns a model identifier.
	 *
	 * @param array $model_data Model data
	 *
	 * @return array $model_id List of model identifiers
	 */
	
	public function getIdentifier($model_data) 
	{
		$model_id = [];
		
		foreach($model_data as $property_name=>$property_data) {
			if (isset($property_data['identifier']) && $property_data['identifier'] === true) {
				$model_id[] = $property_name;
			}
		}	
		
		return $model_id;	
	}
	
	/**
	 * Removes every references from Yaml schemas.
	 */
	
	public function removeReferences()
	{
		$this->loadSchemas();
		
		if (!empty($this->app_schema)) {
			foreach($this->app_schema as $bundle_name=>$models) {
				if (!empty($models)) {
					foreach($models as $model_name=>$model_data) {
						if (!empty($model_data)) {
							foreach($model_data as $property_name=>$property_data) {
								unset($this->app_schema[$bundle_name][$model_name][$property_name]['_ref']);
								
								if ($property_name == '_params' && empty($this->app_schema[$bundle_name][$model_name][$property_name])) {
									unset($this->app_schema[$bundle_name][$model_name][$property_name]);
								}
							}
						}
					}
				}
			}
			
			foreach($this->app_schema as $bundle_name=>$models) {
				$schema_content = \Sybil\Yaml::dump($models,2,4);
				$schema_directory = $_SERVER['PWD'].'/bundle/'.$bundle_name.'/';
				$schema_file = $schema_directory.'schema.yml';
				
				if (file_exists($schema_file)) {
					file_put_contents($schema_file, $schema_content);
					chmod($schema_file, 0777);
				}
				
				if (file_exists($schema_directory.'_schema.yml')) {
					unlink($schema_directory.'_schema.yml');
				}
			}
		}
		
		echo "References have been removed from every schema.\n";
	}
}