<?php
private function updateParams($table_name,$column_name,array $params,array $current_params,$reset_reference=false)
	{
		// Primary key
		
		if (isset($params['identifier']) && $params['identifier'] == 1) {
			if ($current_params['primaryKey'] === false) {
				$this->addPrimaryKey($table_name,$column_name);
				$this->setNull($table_name,$column_name,false);
			}
		}
		else {
			if ($current_params['primaryKey'] === true) {
				$this->dropPrimaryKey($table_name,$column_name);
			}
		}
		
		// Foreign key
		
		if (isset($params['indexOf']) && !empty($params['indexOf']['model'])) {
			$reference = $this->getReferenceTable($params['indexOf']);
			$type = $this->getPropertyType($reference);
		
			if ($current_params['foreignKey'] != false) {
				$this->unsetForeignKey($table_name,$column_name);
				$this->setType($table_name,$column_name,$type);
				$this->addForeignKey($table_name,$column_name,$reference[0],$reference[1]);
				$this->setNull($table_name,$column_name,false);
			}
			else {
				$this->setType($table_name,$column_name,$type);
				$this->addForeignKey($table_name,$column_name,$reference[0],$reference[1]);
				$this->setNull($table_name,$column_name,false);
			}
		}
		else {
			if ($current_params['foreignKey'] != false) {
				$this->unsetForeignKey($table_name,$column_name);
			}
			
			$type = $this->getPropertyType($reference);
			$this->setType($table_name,$column_name,$type);
		}
		
		// Default value
		
		if (!empty($params['default'])) {
			$this->setDefault($table_name,$column_name,$params['default']);
		}
		else {
			if ($current_params['default'] != false) {
				$this->unsetDefault($table_name,$column_name);
			}
		}
		
		// Null
		
		if (!empty($params['null'])) {
			$this->setNull($table_name,$column_name,$params['null']);
		}
		else {
			$this->setNull($table_name,$column_name,false);
		}
		
		// Zerofill
		
		
		
		/*
					
			
			
			
			Si le champ doit être rempli de zéros et qu'il s'agit d'un champ numérique
				$this->mysql->zeroFill($table_name,$column_name);
			Sinon
				S'il l'était
					$this->mysql->unZeroFill($table_name,$column_name);
					
					
					
					
			Si le champ doit être auto incrémenté et qu'il s'agit d'un champ numérique
				$this->mysql->addAutoIncrement($table_name,$column_name);
			Sinon
				S'il l'était
					$this->mysql->removeAutoIncrement($table_name,$column_name);
				
				
				
			Si le champ doit être indexé
				Si l'index n'existe pas
					$this->mysql->addIndex($table_name,$column_name);
			Sinon
				S'il l'était
					$this->mysql->dropIndex($table_name,$column_name);
			
			
			
			if ($reset_reference === true) {
				$this->resetReference($table_name,$column_name);
			} 	
			
			
		*/
	}


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
	private $app_entities = [];
	private $requests = [];

	/*
	 * Triggers an update on database structure,
	 * using the application entities.
	 *
	 * @param array $app_schema Application entities
	 */
	
	public function updateDB(array $app_entities)
	{
		$this->mysql = new MySQL();
		
		if (!empty($app_entities)) {
			$this->app_entities = $app_entities;
			
			$requests = '';
			$existing_tables = $this->mysql->getTables();
			
			foreach($this->app_entities as $bundle_name=>$bundle_entities) {
				foreach($bundle_entities as $entity_name=>$entity_data) {
					$table_name = !empty($entity_data['_params']['alias']) ? $entity_data['_params']['alias'] : $entity_name;
					
					if ($this->mysql->issetTable($table_name)) {
						unset($existing_tables[$table_name]);
						$table_properties = $this->mysql->getFields($table_name);
						
						$indexes = $this->getIndexes($table_name,$entity_data);
						$this->alterTable($table_name,$indexes);
						
						//$request_data = $this->mysql->compare($table_properties,$entity_data);
						//$this->alterTable($table_name,$request_data);
					}
					else {
						$request_data = $this->createTable($table_name,$entity_data);
						$this->alterTable($table_name,$request_data);
					}
				}
			}
			
			foreach($existing_tables as $table_name=>$table_data) {
				$this->alterTable($table_name,['drop'=>true]);
			}
			
			if (!empty($this->requests['create_tables'])) {
				$requests.= implode("\n",$this->requests['create_tables']) . "\n";
			}
			
			if (!empty($this->requests['alter_tables'])) {
				$requests.= implode("\n",$this->requests['alter_tables']) . "\n";
			}
			
			if (!empty($requests)) {
				$this->mysql->execute($requests);
			}
		}
		
		// Pour chaque entité
			// Si la table existe
				// On récupère la liste de ses propriétés
				// On mixe un nouveau tableau avec les propriétés en plus/en moins
				// Ajout/suppression des propriétés (si besoin : suppression des clés étrangères des champs à supprimer. Vérifier les références. Demande de confirmation en console ?)
				// Ajout des clés étrangères à créer dans un tableau (execution à la fin)
	}
		
	/*
	 * Generates a CREATE TABLE request.
	 *
	 * @param string $table_name Table name
	 * @param array $entity_data Entity data
	 *
	 * @return array Request data
	 */
	
	public function createTable($table_name,$entity_data)
	{
		$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (\n";
					
		$primary_key = [];
		$indexes = [];
		$foreign_keys = [];
		$auto_increment = false;
		
		foreach($entity_data as $property_name=>$property_data) {
			if ($property_name != '_params') {
				$sql.= "    `" . $property_name . "`";
				
				if (isset($property_data['indexOf']) && !empty($property_data['indexOf']['model'])) {
					$indexes[] = $property_name;
					$foreign_keys[$property_name] = $property_data['indexOf'];
					
					$reference_field = $this->getReferenceField($property_data['indexOf'])[2];
					
					$property_type = isset($reference_field['type']) ? $reference_field['type'] : null;
					$property_length = isset($reference_field['length']) ? $reference_field['length'] : null;
					$property_decimal = isset($reference_field['decimal']) ? $reference_field['decimal'] : null;
					$property_zerofill = isset($reference_field['zerofill']) ? ' ZEROFILL' : '';
				
					$sql.= " " . $this->convertType($property_type,$property_length,$property_decimal) . $property_zerofill;
					
				}
				else {
					$property_type = isset($property_data['type']) ? $property_data['type'] : null;
					$property_length = isset($property_data['length']) ? $property_data['length'] : null;
					$property_decimal = isset($property_data['decimal']) ? $property_data['decimal'] : null;
				
					$sql.= " " . $this->convertType($property_type,$property_length,$property_decimal);
				}
				
				if (isset($property_data['zerofill']) && $property_data['zerofill'] == 1 && ($property_type == 'integer' || $property_type == 'float')) {
					$sql.= ' ZEROFILL';
				}
				
				if (isset($property_data['identifier']) && $property_data['identifier'] == 1) {
					$primary_key[] = $property_name;
					$sql.= ' NOT NULL';
				}
				else {
					if (isset($property_data['nullable'])) {
						$sql.= $property_data['nullable'] == 1 ? "" : "NOT NULL";
					}
					
					if (isset($property_data['default'])) {
						$sql.= ' DEFAULT ' . ($property_data['default'] == 'null' ? "NULL" : "'" . $property_data['default'] . "'");
					}
				}
				
				if (isset($property_data['auto']) && $property_data['auto'] == 1) {
					$sql.= ' AUTO_INCREMENT';
					$auto_increment = true;
				}
				
				$sql.= ",\n";
			}
		}
		
		if (!empty($primary_key)) {
			$sql.= "    PRIMARY KEY (" . implode(',',$primary_key) . ")\n";
		}
		
		$sql = trim($sql,",\n");
		
		$sql.= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		
		if ($auto_increment === true) {
			$sql.= " AUTO_INCREMENT=0";
		}
		
		$sql.= ";\n";
		
		$this->requests['create_tables'][] = $sql;
		
		$indexes = array_diff($indexes,$primary_key);
		
		return [
			'indexes' => $indexes,
			'foreign_keys' => $foreign_keys
		];
	}
	
	/*
	 * Returns array of indexes.
	 *
	 * @param string $table_name Table name
	 * @param array $entity_data Entity data
	 *
	 * @return array Indexes
	 */
	
	public function getIndexes($table_name,$entity_data)
	{
		$primary_key = [];
		$indexes = [];
		$foreign_keys = [];
		
		foreach($entity_data as $property_name=>$property_data) {
			if ($property_name != '_params') {
				if (isset($property_data['indexOf']) && !empty($property_data['indexOf']['model'])) {
					$indexes[] = $property_name;
					$foreign_keys[$property_name] = $property_data['indexOf'];
				}
				
				if (isset($property_data['identifier']) && $property_data['identifier'] == 1) {
					$primary_key[] = $property_name;
				}
			}
		}
		
		$indexes = array_diff($indexes,$primary_key);
		
		return [
			'indexes' => $indexes,
			'foreign_keys' => $foreign_keys
		];
	}
	
	/*
	 * Generates ALTER TABLE requests for primary keys, 
	 * indexes and foreign keys.
	 *
	 * @param string $table_name Table name
	 * @param array $request_data Request data
	 */
	
	public function alterTable($table_name,$request_data)
	{
		extract($request_data);
	
		if (!empty($primary_key)) {
			$this->addPrimaryKey($primary_key,$table_name);
		}
		
		if (!empty($indexes)) {
			$this->addIndexes($indexes,$table_name);
		}
		
		if (!empty($foreign_keys)) {
			$this->addForeignKeys($foreign_keys,$table_name);
		}
		
		if (!empty($drop)) {
			$this->dropTable($table_name);
		}
	}
	
	/*
	 * Adds one or multiple fields as primary key.
	 *
	 * @param array $primary_key Array of field(s) name(s) to use
	 * @param string $table_name Table name
	 */
	
	public function addPrimaryKey($primary_key)
	{
		$primary_key = '`' . implode('`,`',$primary_key) . '`';
		$this->requests['alter_tables'][] = "ALTER TABLE ADD PRIMARY KEY ($primary_key);";
		// Champ NOT NULL
	}
	
	/*
	 * Adds one or multiple indexes.
	 *
	 * @param array $indexes Array of field(s) to use
	 * @param string $table_name Table name
	 */
	
	public function addIndexes(array $indexes,$table_name)
	{
		foreach($indexes as $index) {
			$sql = "ALTER TABLE `$table_name` ADD INDEX `index_$index` (`$index`);";
			$this->requests['alter_tables'][] = $sql;
		}
		
		return true;
	}
	
	/*
	 * Adds one or multiple foreign keys constraints.
	 *
	 * @param array $foreign_keys Array of field(s) to use
	 * @param string $table_name Name of field(s)'s table
	 */
	
	public function addForeignKeys($foreign_keys,$table_name)
	{
		$sql = "";
		
		foreach($foreign_keys as $property_name=>$property_data) {
			$reference = $this->getReferenceField($property_data);
			$reference_table = $reference[0];
			$reference_field = $reference[1];
			
			$update = 'RESTRICT';
			
			if (!empty($property_data['update'])) {
				switch($property_data['update']) {
					case 'restrict':
						$update = 'RESTRICT';
						break;
					case 'cascade':
						$update = 'CASCADE';
						break;
					case 'null':
						$update = 'SET NULL';
						break;
					case 'no_action':
						$update = 'NO ACTION';
						break;
				}
			}
			
			$delete = 'RESTRICT';
			
			if (!empty($property_data['delete'])) {
				switch($property_data['delete']) {
					case 'restrict':
						$delete = 'RESTRICT';
						break;
					case 'cascade':
						$delete = 'CASCADE';
						break;
					case 'null':
						$delete = 'SET NULL';
						break;
					case 'no_action':
						$delete = 'NO ACTION';
						break;
				}
			}
			
			$sql = "ALTER TABLE `$table_name` ADD CONSTRAINT `fk_{$table_name}_{$property_name}` FOREIGN KEY(`$property_name`) REFERENCES `$reference_table`(`$reference_field`) ON UPDATE $update ON DELETE $delete;";
			
			$this->requests['alter_tables'][] = $sql;
		}
		
		return true;
	}
	
	/*
	 * Removes a table into the database.
	 *
	 * @param string $table_name Name of the table
	 */
	
	public function dropTable($table_name)
	{
		$references = $this->mysql->getReferences($table_name);
		
		if (!empty($references)) {
			echo "Removing '$table_name' table will remove every related references with others tables. Continue ? (Y/n) :";
			$handle = fopen ("php://stdin","r");
			$line = fgets($handle);
			
			if(trim($line) != 'Y'){
			    echo "Table drop cancelled.\n";
			    exit;
			}
			
			$this->mysql->removeReferences($references);
		}
	
		$this->mysql->dropTable($table_name);
	}
	
	/*
	 * Returns the reference table and field of a given index field.
	 *
	 * @param array $foreign_key Reference informations
	 *
	 * @return array The reference table name and field name
	 */
	
	public function getReferenceField($foreign_key)
	{
		$reference = [];
		
		if (!empty($foreign_key['bundle']) && !empty($foreign_key['model'])) {
			$model_properties = isset($this->app_entities[$foreign_key['bundle']][$foreign_key['model']]) ? $this->app_entities[$foreign_key['bundle']][$foreign_key['model']] : null;
			
			if ($model_properties != null) {
				$table_name = isset($model_properties['_params']['alias']) ? $model_properties['_params']['alias'] : $foreign_key['model'];
			}
		}
		elseif (!empty($foreign_key['model'])) {
			$table_name = '';
			
			foreach($this->app_entities as $bundle_name=>$bundle_entities) {
				foreach($bundle_entities as $model_name=>$model_data) {
					if ($foreign_key['model'] == $model_name) {
						$model_properties = $this->app_entities[$bundle_name][$model_name];
						$table_name = isset($model_data['_params']['alias']) ? $model_data['_params']['alias'] : $foreign_key['model'];
						break;
					}
				}
			}
		}
		
		if (!empty($foreign_key['property'])) {
			$reference = [$table_name,$foreign_key['property'],$model_properties[$foreign_key['property']]];
		}
		else {
			if (!empty($model_properties)) {
				foreach($model_properties as $property_name=>$property_data) {
					if (isset($property_data['identifier']) && $property_data['identifier'] == 1) {
						$reference = [$table_name,$property_name,$model_properties[$property_name]];
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
	
}