<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil\ORM\MySQL;

use Sybil\Config;
use PDO;
use PDOException;

/**
 * The MySQL SchemaBuilder class generates the structure 
 * of a MySQL database.
 *
 * @author Grégory Bellencontre
 */
final class SchemaBuilder 
{
	private $mysql = null;
	private $app_entities = null;
	
	/**
	 * Main class function.
	 * Triggers add or update using entities.
	 *
	 * @param array $app_entities Application entities
	 */
	
	public function build(array $app_entities)
	{
		$this->mysql = new MySQL();
		
		if (!empty($app_entities)) {
			$this->app_entities = $app_entities;
			
			$existing_tables = $this->mysql->getTables();
			$tables_to_delete = $existing_tables;
			
			foreach($this->app_entities as $bundle_name=>$bundle_entities) {
				if (!empty($bundle_entities)) {
					foreach($bundle_entities as $entity_name=>$entity_data) {
						$table_name = !empty($entity_data['_params']['alias']) ? $entity_data['_params']['alias'] : $entity_name;
						
						if (in_array($table_name,$existing_tables)) {
							if (!empty($entity_data)) {
								$reference = !empty($entity_data['_params']['_ref']) ? $entity_data['_params']['_ref'] : null;
								
								unset($tables_to_delete[$reference]);
								
								if ($reference !== null && $this->matchReference($existing_tables,$table_name,$reference)) {
									$this->updateColumns($bundle_name,$entity_name);
								}
								else {
									$response = 'undefined';
									$expected_responses = ['replace','update','cancel'];
									
									while (!in_array($response,$expected_responses)) {
										$response = $this->prompt("WARNING : The table \"" . $table_name . "\" already exists, but references doesn't match.\nDo you want to replace the table, update its structure, or cancel the whole update [replace|update|cancel] ?");
									}
									
									if ($response == 'replace') {
										$this->mysql->dropTable($table_name);
										$this->addTable($bundle_name,$entity_name);
									}
									elseif($response == 'update') {
										$this->updateColumns($bundle_name,$entity_name,true);
									}
									else {
										die("\nUpdate canceled.\n\n");
									}
								}
							}
							else {
								die("Fatal error : Updated version of the \"" . $table_name . "\" table is empty. Please check your schema.\n");
							}
						}
						else {
							$reference = !empty($entity_data['_params']['_ref']) ? $entity_data['_params']['_ref'] : null;
							
							unset($tables_to_delete[$reference]);
							
							if ($reference !== null) {
								$this->mysql->renameTable($reference,$table_name,false);
								
								if (!empty($entity_data)) {
									$this->updateColumns($bundle_name,$entity_name);
								}
							}
							else {
								$this->addTable($bundle_name,$entity_name);
							}
						}
					}
				}
			}
			
			$this->clearTables($tables_to_delete);
			
			if ($this->mysql->execute() === true) {
				$this->updateSchemas();
			}
		}
	}
	
	/**
	 * Updates the columns of a table.
	 *
	 * @param string $bundle_name Bundle name
	 * @param string $entity_name Entity name
	 * @param string $table_name Table name
	 * @param bool $reset_references Reset table and columns references
	 */
	
	private function updateColumns($bundle_name,$entity_name,$reset_references=false)
	{
		$entity_data = $this->app_entities[$bundle_name][$entity_name];
		$table_name = !empty($entity_data['_params']['alias']) ? $entity_data['_params']['alias'] : $entity_name;
		
		$existing_columns = $this->mysql->getColumns($table_name);
		$columns_to_delete = $existing_columns;
		
		foreach($entity_data as $column_name=>$column_data) {
			if ($column_name != '_params') {
				if (in_array($column_name,$existing_columns)) {
					if (!empty($column_data)) {
						$reference = !empty($column_data['_ref']) ? $column_data['_ref'] : null;
						unset($columns_to_delete[$reference]);
						
						if ($reference !== null && $this->matchReference($existing_columns,$column_name,$reference)) {
							$this->updateParams($table_name,$column_name,$column_data,$existing_columns[$reference]);
						}
						else {
							$response = 'undefined';
							$expected_responses = ['replace','update','cancel'];
							
							$old_reference = array_search($column_name,$columns_to_delete);
							unset($columns_to_delete[$old_reference]);
							
							while (!in_array($response,$expected_responses)) {
								$response = $this->prompt("WARNING : The column \"$column_name\" already exists in the table \"$table_name\", but reference doesn't match.\nDo you want to replace the column, update its configuration, or cancel the whole update [replace|update|cancel] ?");
							}
							
							if ($response == 'replace') {
								$this->mysql->dropColumn($table_name,$column_name);
								$this->addColumn($bundle_name,$entity_name,$column_name);
							}
							elseif($response == 'update') {
								$this->updateParams($table_name,$column_name,$column_data,$existing_columns,true);
							}
							else {
								die("\nUpdate canceled.\n\n");
							}
						}
					}
					else {
						die("Fatal error : The column \"$column_name\" from table \"$table_name\" contains error. Please check your schema.\n");
					}
				}
				else {
					$reference = !empty($column_data['_ref']) ? $column_data['_ref'] : null;
					
					unset($columns_to_delete[$reference]);
					
					if ($reference !== null) {
						$previous_column = $this->getPreviousColumn($column_name,$entity_data);
						$this->mysql->renameColumn($reference,$table_name,$column_name,$column_data,$previous_column,false);
						
						if (!empty($column_data)) {
							$this->updateParams($table_name,$column_name,$column_data,$existing_columns[$old_column_name]);
						}
					}
					else {
						$this->addColumn($bundle_name,$entity_name,$column_name);
					}
				}
			}
		}
		
		if ($reset_references === true) {
			$this->resetTableReferences($table_name);
		}
		
		$this->clearColumns($table_name,$columns_to_delete);
	}
	
	/**
	 * Updates configuration of columns in a table.
	 *
	 * @param string $table_name Table name
	 * @param string $column_name Column name
	 * @param array $params Column configuration
	 * @param array $current_params Actual column configuration
	 * @param bool $reset_reference Reset column reference
	 */
	
	private function updateParams($table_name,$column_name,array $params,$current_params,$reset_reference=false)
	{
		echo "UPDATE OF TABLE $table_name / COLUMN $column_name\n\n";
	}
	
	/**
	 * Adds a table and its keys to the database.
	 *
	 * @param string $bundle_name Bundle name
	 * @param string $entity_name Entity name
	 * @param string $table_name Table name
	 */
	
	public function addTable($bundle_name,$entity_name)
	{
		$entity_data = $this->app_entities[$bundle_name][$entity_name];
		$table_name = !empty($entity_data['_params']['alias']) ? $entity_data['_params']['alias'] : $entity_name;
		
		$entity_data = $this->addReferences($bundle_name,$entity_name);
		$table_data = $this->getTableData($entity_data);
		
		$indexes = $this->getIndexes($table_data);
		$foreign_keys = $this->getForeignKeys($table_data);
		
		$this->mysql->addTable($table_name,$table_data,false);
		$this->mysql->addIndexes($table_name,$indexes,false);
		$this->mysql->addForeignKeys($table_name,$foreign_keys,false);
	}
	
	/**
	 * Gets the name of the previous column.
	 *
	 * @param string $column_name Column name
	 * @param array $entity_data Entity data
	 *
	 * @param string $previous_column Name of the previous column
	 */
	
	public function getPreviousColumn($column_name,$entity_data)
	{
		$column_index = array_search($column_name,array_keys($entity_data));
		
		if ($column_index > 0) {
			$entity_columns = array_keys($entity_data);
			$previous_column = $entity_columns[$column_index-1];
		}
		else {
			$previous_column = null;
		}
		
		return $previous_column;
	}
	
	/**
	 * Adds a column and its keys into a database table.
	 *
	 */
	
	public function addColumn($bundle_name,$entity_name,$column_name)
	{
		$entity_data = $this->app_entities[$bundle_name][$entity_name];
		$table_name = !empty($entity_data['_params']['alias']) ? $entity_data['_params']['alias'] : $entity_name;
		
		$entity_data = $this->addReferences($bundle_name,$entity_name,$column_name);
		$column_data = $this->getColumnData($entity_data[$column_name]);
		
		$foreign_key = $this->getForeignKey($column_data);
		
		$previous_column = $this->getPreviousColumn($column_name,$entity_data);
		
		$primary_key = $this->getPrimaryKeyColumns($entity_data);
		
		$this->mysql->addColumn($table_name,$column_name,$column_data,$previous_column,$primary_key,false);
		
		if ($this->isIndexed($column_data)) {
			$this->mysql->addIndex($table_name,$column_name,false);
		}
		
		if (!empty($foreign_key)) {
			$this->mysql->addForeignKey($table_name,$column_name,$foreign_key,false);
		}
	}
	
	/**
	 * Returns table configuration and columns.
	 *
	 * @param array $entity_data Entity data
	 *
	 * @return array $table_data Table data
	 */
	
	public function getTableData($entity_data)
	{
		$table_data = $entity_data;
		
		foreach($table_data as $column_name=>$column_data) {
			if ($column_name != '_params') {
				$table_data[$column_name] = $this->getColumnData($column_data);
			}
		}
		
		return $table_data;
	}
	
	/**
	 * Returns column configuration.
	 *
	 * @param array $column_data Column data
	 *
	 * @return array $column_data Column data
	 */
	
	public function getColumnData($column_data)
	{
		if (isset($column_data['indexOf']) && !empty($column_data['indexOf']['model'])) {
			$reference_column = $this->getReferenceColumn($column_data['indexOf']);
			
			$column_length = isset($reference_column['column_data']['length']) ? $reference_column['column_data']['length'] : null;
			$column_decimal = isset($reference_column['column_data']['decimal']) ? $reference_column['column_data']['decimal'] : null;
			
			$reference_column['column_data']['type'] = $this->convertType($reference_column['column_data']['type'],$column_length,$column_decimal);
			
			$column_data['indexOf']['reference'] = $reference_column;
		}
		else {
			$column_length = isset($column_data['length']) ? $column_data['length'] : null;
			$column_decimal = isset($column_data['decimal']) ? $column_data['decimal'] : null;
			
			$column_data['type'] = !isset($column_data['type']) ? null : $column_data['type'];
			$type = $this->convertType($column_data['type'],$column_length,$column_decimal);
			$column_data['type'] = $type;
		}
		
		return $column_data;
	}
	
	/**
	 * Adds references to an entity and its properties.
	 *
	 * @param string $bundle_name Bundle name
	 * @param string $entity_name Entity name
	 *
	 * @return array $entity Entity data
	 */
	
	public function addReferences($bundle_name,$entity_name,$column_name=null)
	{
		$entity = $this->app_entities[$bundle_name][$entity_name];
		
		$element = [];
		
		if ($column_name === null) {
			foreach($entity as $column_name=>$column_data) {
				$element = [];
				
				if ($column_name == '_params') {
					if (isset($entity['_params'])) {
						$element['_ref'] = self::generateReference();
						$entity['_params'] = $element + $entity['_params'];
					}
					else {
						$element['_params']['_ref'] = self::generateReference();
						$entity = $element + $entity;
					}
				}
				else {
					$element['_ref'] = self::generateReference();
					$entity[$column_name] = $element + $entity[$column_name];
				}
			}
			
			$element = [];
			
			if (!isset($entity['_params'])) {
				$element['_params']['_ref'] = self::generateReference();
				$entity = $element + $entity;
			}
			
			$this->app_entities[$bundle_name][$entity_name] = $entity;
		}
		else {
			$column = $entity[$column_name];
			$element['_ref'] = self::generateReference();
			$column = $element + $column;
			$entity[$column_name] = $column;
			
			$this->app_entities[$bundle_name][$entity_name][$column_name] = $column;
		}
		
		return $entity;
	}
	
	/**
	 * Tests if a table/column name matches with a given reference.
	 *
	 * @param array $list Tables or columns list (key:reference, value:name)
	 * @param string $name Table or column name
	 * @param string $reference Reference to test
	 *
	 * @return bool
	 */
	
	public function matchReference($list,$name,$reference)
	{
		return array_key_exists($reference, $list) && $list[$reference] == $name;
	}
	
	/**
	 * Updates schemas with new references.
	 */
	
	public function updateSchemas()
	{
		foreach($this->app_entities as $bundle_name=>$bundle_data) {
			$schema_content = \Sybil\Yaml::dump($bundle_data,2,4);
			$schema_directory = $_SERVER['PWD'].'/bundle/'.$bundle_name.'/';
			$schema_file = $schema_directory.'schema.yml';
			
			if (file_exists($schema_file)) {
				copy($schema_file,$schema_directory.'_schema.yml');
				chmod($schema_directory.'_schema.yml', 0777);
				
				file_put_contents($schema_file, $schema_content);
				chmod($schema_file, 0777);
			}
		}
	}
	
	public function getPrimaryKeyColumns($entity_data)
	{
		$primary_key = [];
		
		foreach($entity_data as $column_name=>$column_data) {
			if ($column_name != '_params' && isset($column_data['identifier']) && $column_data['identifier'] == 1) {
				$primary_key[] = $column_name;
			}
		}
		
		return $primary_key;
	}
	
	/**
	 * Checks if a column must be indexed.
	 *
	 * @param array $column_data Column data
	 *
	 * @return bool
	 */
	
	public function isIndexed($column_data)
	{
		return isset($column_data['index']) && $column_data['index'] == 1;
	}
	
	/**
	 * Returns a list containing indexed fields.
	 *
	 * @param array $entity_data Entity data
	 *
	 * @return array $indexes Indexes list
	 */
	
	public function getIndexes($entity_data)
	{
		$indexes = [];
		
		foreach($entity_data as $column_name=>$column_data) {
			if ($column_name != '_params') {
				if ($this->isIndexed($column_data)) {
					$indexes[] = $column_name;
				}
			}
		}
		
		return $indexes;
	}
	
	/**
	 * Returns foreign key data.
	 *
	 * @param array $column_data Column data
	 *
	 * @return array Foreign key data
	 */
	
	public function getForeignKey($column_data)
	{
		$foreign_key = [];
		
		if (isset($column_data['indexOf']) && !empty($column_data['indexOf']['model'])) {
			$foreign_key = $column_data['indexOf'];
		}
		
		return $foreign_key;
	}
	
	/**
	 * Returns a list containing foreign keys.
	 *
	 * @param array $entity_data Entity data
	 *
	 * @return array $foreign_keys Foreign keys list
	 */
	
	public function getForeignKeys($entity_data)
	{
		$foreign_keys = [];
		
		foreach($entity_data as $column_name=>$column_data) {
			if ($column_name != '_params') {
				if (isset($column_data['indexOf']) && !empty($column_data['indexOf']['model'])) {
					$foreign_keys[$column_name] = $column_data['indexOf'];
				}
			}
		}
		
		return $foreign_keys;
	}
	
	/**
	 * Returns a MySQL type after checking its validity.
	 *
	 * @param string $mysql_types MySQL type
	 *
	 * @return string $mysql_type MySQL type
	 */
	
	public static function getType($mysql_type)
	{
		$integer = ['tinyint','smallint','mediumint','int','bigint'];
		
		if (in_array($mysql_type,$integer)) {
			return 'integer';
		}
		elseif ($mysql_type == 'varchar') {
			return 'string';
		}
		else {
			return $mysql_type;
		}
	}
	
	/*
	 * Converts an ORM property type into MySQL field type.
	 *
	 * @param string $orm_type ORM property type
	 * @param integer $length Property max length
	 *
	 * @return string MySQL field type
	 */
	
	public function convertType($orm_type,$length=null,$decimal=null)
	{
		$type = '';
		
		if ($orm_type != null) {
			switch($orm_type) {
				case 'integer':
					$type = $length == null ? 'int(11)' : $this->getIntegerLength($length);
					break;
				case 'float':
					$type = $length != null && $decimal != null ? "float($length,$decimal)" : ($length != null ? "float($length,1)" : "float");
					break;
				case 'string':
					$type = $length == null ? 'varchar(255)' : "varchar($length)";
					break;
				default:
					$type = $orm_type;
					break;
			}
		}
		else {
			$type = 'varchar(255)';
		}
		
		return $type;
	}
	
	/*
	 * Returns the right MySQL integer type depending 
	 * on a given length.
	 *
	 * @param integer $length Integer length
	 *
	 * @return string MySQL integer type
	 */
	
	public function getIntegerLength($length)
	{
		if ($length < 3) {
			$integer = "tinyint($length)";
		}
		elseif ($length < 5) {
			$integer = "smallint($length)";
		}
		elseif ($length < 7) {
			$integer = "mediumint($length)";
		}
		elseif ($length < 10) {
			$integer = "int($length)";
		}
		else {
			$integer = "bigint($length)";
		}
		
		return $integer;
	}
	
	/*
	 * Returns the reference table and column of a given index field.
	 *
	 * @param array $foreign_key Reference informations
	 *
	 * @return array The reference table name and column name
	 */
	
	public function getReferenceColumn($foreign_key)
	{
		$reference = [];
		
		if (!empty($foreign_key['bundle']) && !empty($foreign_key['model'])) {
			$table_columns = isset($this->app_entities[$foreign_key['bundle']][$foreign_key['model']]) ? $this->app_entities[$foreign_key['bundle']][$foreign_key['model']] : null;
			
			if ($table_columns != null) {
				$table_name = isset($table_columns['_params']['alias']) ? $table_columns['_params']['alias'] : $foreign_key['model'];
			}
		}
		elseif (!empty($foreign_key['model'])) {
			$table_name = '';
			
			foreach($this->app_entities as $bundle_name=>$bundle_entities) {
				foreach($bundle_entities as $entity_name=>$entity_data) {
					if ($foreign_key['model'] == $entity_name) {
						$table_columns = $this->app_entities[$bundle_name][$entity_name];
						$table_name = isset($entity_data['_params']['alias']) ? $entity_data['_params']['alias'] : $foreign_key['model'];
						break;
					}
				}
			}
		}
		
		if (!empty($foreign_key['property'])) {
			$reference = [
				'table_name' => $table_name,
				'column_name' => $foreign_key['property'],
				'column_data' => $table_columns[$foreign_key['property']]
			];
		}
		else {
			if (!empty($table_columns)) {
				foreach($table_columns as $column_name=>$column_data) {
					if (isset($column_data['identifier']) && $column_data['identifier'] == 1) {
						$reference = [
							'table_name' => $table_name,
							'column_name' => $column_name,
							'column_data' => $table_columns[$column_name]
						];
						break;
					}
				}
			}
			else {
				die("Fatal error: An error occurred during the database update. Check your bundle schemas.\n\n");
			}
		}
		
		return $reference;
	}
	
	/**
	 * Removes missing tables from the schema.
	 *
	 * @param array $tables_to_delete Tables list
	 */
	
	public function clearTables($tables_to_delete)
	{
		if (!empty($tables_to_delete)) {
			foreach($tables_to_delete as $table_name) {
				$response = $this->prompt('A "' . $table_name . '" table exists in database but is missing from your schema. Do you want to remove it ? [Y/n] :');
				
				if($response == 'Y'){
				    $this->mysql->dropTable($table_name);
				}
				else {
					continue;
				}
			}
		}
	}
	
	/**
	 * Removes missing columns from the schema.
	 *
	 * @param string $table_name Table name
	 * @param array $columns_to_delete Columns list
	 */
	
	public function clearColumns($table_name,$columns_to_delete)
	{
		if (!empty($columns_to_delete)) {
			foreach($columns_to_delete as $column_name) {
				$response = $this->prompt('A "' . $column_name . '" column exists in table "' . $table_name . '" but is missing from your schema. Do you want to remove it ? [Y/n] :');
				
				if($response == 'Y'){
				    $this->mysql->dropColumn($table_name,$column_name);
				}
				else {
					continue;
				}
			}
		}
	}
	
	/**
	 * Generates a unique reference.
	 *
	 * @return string Reference string
	 */
	
	public static function generateReference()
	{
		$rnd_id = sha1(uniqid(rand(),1)); 
		$rnd_id = strip_tags(stripslashes($rnd_id)); 
		$rnd_id = str_replace(".","",$rnd_id); 
		$rnd_id = strrev(str_replace("/","",$rnd_id)); 
		
		return substr($rnd_id,0,8); 
	}
	
	/**
	 * Prompts a message to user and returns the answer.
	 *
	 * @param string Prompt message
	 *
	 * @return string Answer from the user
	 */
	
	public function prompt($message)
	{
		echo $message . " ";
		
		$handle = fopen ("php://stdin","r");
		
		return trim(fgets($handle));
	}
}