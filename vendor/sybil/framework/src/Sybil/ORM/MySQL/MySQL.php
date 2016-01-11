<?php
/**
 * Sybil Framework
 * (c) 2015 Grégory Bellencontre
 */

namespace Sybil\ORM\MySQL;

use Sybil\ORM\Database;
use Sybil\Config;
use PDO;
use PDOException;

/**
 * The MySQL class manages tables and columns.
 *
 * @author Grégory Bellencontre
 */
final class MySQL 
{
	private $db = null;
	private $db_name = null;
	private $requests = [];
	
	public function __construct()
	{
		$this->db = $this->db === null ? $this->connect() : $this->db;
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db_name = Config::$databases[Config::$db_to_use]['dbname'];
	}
	
	/*
	 * Performs a connection to the database.
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
	 * Checks if a table exists on the database.
	 *
	 * @param string $table_name Table name
	 *
	 * @return bool true if exists, false if not exists
	 */
	
	public function tableExists($table_name)
	{
		$sql = "SHOW TABLES LIKE :table_name;";
		
		$request = $this->db->prepare($sql);
		$request->bindParam(':table_name',$table_name);
		$request->execute();		
		
		return $request->rowCount() == 1;
		
	}
	
	/*
	 * Renames a table with a new name.
	 *
	 * @param string $reference The reference of the table
	 * @param string $table_name The new name
	 * @param string $execute Execute the request (true) or save it (false)
	 */
	
	public function renameTable($reference,$table_name,$execute=true)
	{
		$current_table_name = $this->getTableByReference($reference);
		
		$sql = "RENAME TABLE $current_table_name TO $table_name";
		
		if ($execute === true) {
			try {
				$this->db->exec($sql);
			}
			catch(PDOException $e) {
				die("Fatal error: Table rename failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
		else {
			$this->requests[2][] = $sql;
		}
	}
	
	/*
	 * Renames a table column with a new name.
	 *
	 * @param string $reference The reference of the table
	 * @param string $table_name The table name
	 * @param string $column_name The new column name
	 * @param string $column_data The column data
	 * @param string $execute Execute the request (true) or save it (false)
	 */
	
	public function renameColumn($reference,$table_name,$column_name,$column_data,$previous_column,$execute=true)
	{
		$current_column = $this->getColumnByReference($table_name,$reference);
		$current_column_name = $current_column['name'];
		$column_type = $current_column['type'];
		
		$sql = "ALTER TABLE $table_name CHANGE $current_column_name $column_name";
		
		$sql.= $this->buildColumnParams($column_data,$previous_column);
		
		if ($execute === true) {
			try {
				$this->db->exec($sql);
			}
			catch(PDOException $e) {
				die("Fatal error: Table rename failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
		else {
			$this->requests[2][] = $sql;
		}
	}
	
	/*
	 * Returns existing tables in the database.
	 *
	 * @return array Tables list
	 */
	
	public function getTables()
	{
		$tables = [];
		
		$sql = "SHOW TABLE STATUS;";
		$request = $this->db->query($sql);
		
		if ($request->rowCount() != 0) {
			while ($result = $request->fetchObject()) {
				$tables[$result->Comment] = $result->Name;
			}
		}
		
		return $tables;
	}
	
	/*
	 * Creates "CREATE TABLE" request for a table.
	 *
	 * @param string $table_name Table name
	 * @param array $entity_data Table structure informations
	 * @param bool $execute Execute request (true) or save it (false)
	 */
	 
	public function addTable($table_name,$table_data,$execute=true)
	{
		$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (\n";
					
		$primary_key = [];
		$foreign_keys = [];
		$auto_increment = false;
		
		foreach($table_data as $column_name=>$column_data) {
			if ($column_name != '_params') {
				$sql.= "    `" . $column_name . "`";
				
				$sql.= $this->buildColumnParams($column_data,null);
				
				$sql.= ",\n";
			}
		}
		
		if (!empty($primary_key)) {
			$sql.= "    PRIMARY KEY (" . implode(',',$primary_key) . ")\n";
		}
		
		$sql = trim($sql,",\n");
		
		$sql.= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='" . $table_data['_params']['_ref'] . "'";
		
		if ($auto_increment === true) {
			$sql.= " AUTO_INCREMENT=0";
		}
		
		$sql.= ";\n";
		
		if ($execute === true) {
			try {
				$this->db->exec($sql);
			}
			catch(PDOException $e) {
				die("Fatal error: Table creation failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
		else {
			$this->requests[1][] = $sql;
		}
		
		return true;
	}
	
	/*
	 * Creates "ALTER TABLE" request for creating a new column.
	 *
	 * @param string $table_name Table name
	 * @param string $column_name Column name
	 * @param array $column_data Column data
	 * @param string $previous_column Previous column name
	 * @param array $primary_key List of table primary key columns
	 * @param bool $execute Execute request (true) or save it (false)
	 */
	
	public function addColumn($table_name,$column_name,$column_data,$previous_column,$primary_key,$execute=true)
	{
		$sql = "ALTER TABLE $table_name ADD $column_name";
		
		$sql.= $this->buildColumnParams($column_data,$previous_column);
		
		$sql.= ";\n";
		
		if (isset($column_data['identifier']) && $column_data['identifier'] == 1) {
			$sql_pk = "ALTER TABLE $table_name DROP PRIMARY KEY, ADD PRIMARY KEY(".implode(',',$primary_key).");\n";
		}
		
		if ($execute === true) {
			try {
				$sql = $sql . $sql_pk;
				$this->db->exec($sql);
			}
			catch(PDOException $e) {
				die("Fatal error: Column insertion failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
		else {
			$this->requests[1][] = $sql;
			
			if (isset($sql_pk)) {
				$this->requests[2][] = $sql_pk;
			}
		}
		
		return true;
	}
	
	/*
	 * Builds column configuration.
	 *
	 * @param array $column_data Column data
	 * @param string $previous_column Previous column name
	 *
	 * @return $sql SQL request part
	 */
	
	public function buildColumnParams($column_data,$previous_column)
	{
		if (isset($column_data['indexOf']) && !empty($column_data['indexOf']['model'])) {
			$column_type = $column_data['indexOf']['reference']['column_data']['type'];
			$column_zerofill = isset($column_data['indexOf']['reference']['column_data']['zerofill']) ? ' ZEROFILL' : '';
			
		}
		else {
			$column_type = $column_data['type'];
			$column_zerofill = isset($column_data['zerofill']) && $column_data['zerofill'] == 1 ? ' ZEROFILL' : '';
		}
		
		$sql = " " . $column_type . $column_zerofill;
		
		if (isset($column_data['identifier']) && $column_data['identifier'] == 1) {
			$sql.= ' NOT NULL';
		}
		else {
			if (isset($column_data['nullable'])) {
				$sql.= $column_data['nullable'] == 1 ? "" : "NOT NULL";
			}
			
			if (isset($column_data['default'])) {
				$sql.= ' DEFAULT ' . ($column_data['default'] == 'null' ? "NULL" : "'" . $column_data['default'] . "'");
			}
		}
		
		if (isset($column_data['auto']) && $column_data['auto'] == 1) {
			$sql.= ' AUTO_INCREMENT';
		}
		
		$sql.= " COMMENT '" . $column_data['_ref'] . "'";
		
		if ($previous_column != null) {
			$sql.= " AFTER " . $previous_column;
		}
		
		return $sql;
	}
	
	/*
	 * Create index request for a table column.
	 *
	 * @param string $table_name Table name
	 * @param string $column_name Column name
	 * @param bool $execute Execute request (true) or save it (false)
	 */
	
	public function addIndex($table_name,$column_name,$execute=true)
	{
		$sql = "ALTER TABLE `$table_name` ADD INDEX `index_$column_name` (`$column_name`);";
		
		if ($execute === false) {
			$this->requests[2][] = $sql;
		}
		else {
			try {
				$this->db->exec($sql);
			}
			catch(PDOException $e) {
				die("Fatal error: Table creation failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
	}
	
	/*
	 * Create indexes requests for a table.
	 *
	 * @param string $table_name Table name
	 * @param array $indexes Indexes list
	 * @param bool $execute Execute requests (true) or save them (false)
	 */
	
	public function addIndexes($table_name,$indexes,$execute=true)
	{
		$requests = [];
		
		foreach($indexes as $index) {
			$sql = "ALTER TABLE `$table_name` ADD INDEX `index_$index` (`$index`);";
			$requests[] = $sql;
		}
		
		if ($execute === false) {
			$this->requests[2] = array_merge($this->requests[2],$requests);
		}
		else {
			try {
				foreach($requests as $request) {
					$this->db->exec($request);
				}
			}
			catch(PDOException $e) {
				die("Fatal error: Table creation failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
	}
	
	/*
	 * Defines the action triggered when a reference
	 * is updated or removed.
	 *
	 * @param array $column_data Column data
	 * 
	 * @param array Array containing the update and delete actions
	 */
	
	public function defineActions($column_data)
	{
		$update = 'RESTRICT';
			
		if (!empty($foreign_key['update'])) {
			switch($foreign_key['update']) {
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
		
		if (!empty($foreign_key['delete'])) {
			switch($foreign_key['delete']) {
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
		
		return [
			'update' => $update,
			'delete' => $delete
		];
	}
	
	/*
	 * Create foreign key request for a table column.
	 *
	 * @param string $table_name Table name
	 * @param string $column_name Column name
	 * @param array $foreign_key Foreign key data
	 * @param bool $execute Execute request (true) or save it (false)
	 */
	
	public function addForeignKey($table_name,$column_name,$foreign_key,$execute=true)
	{
		$actions = $this->defineActions($foreign_key);
		$update = $actions['update'];
		$delete = $actions['delete'];
	
		$reference_table = $foreign_key['reference']['table_name'];
		$reference_column = $foreign_key['reference']['column_name'];
	
		$sql = "ALTER TABLE `$table_name` ADD CONSTRAINT `fk_{$table_name}_{$column_name}` FOREIGN KEY(`$column_name`) REFERENCES `$reference_table`(`$reference_column`) ON UPDATE $update ON DELETE $delete;";
		
		if ($execute === false) {
			$this->requests[2][] = $sql;
		}
		else {
			try {
				$this->db->exec($sql);
			}
			catch(PDOException $e) {
				die("Fatal error: Table creation failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
	}
	
	/*
	 * Create foreign keys requests for a table.
	 *
	 * @param string $table_name Table name
	 * @param array $foreign_keys Foreign keys list
	 * @param bool $execute Execute requests (true) or save them (false)
	 */
	
	public function addForeignKeys($table_name,$foreign_keys,$execute=true)
	{
		$requests = [];
		
		foreach($foreign_keys as $column_name=>$column_data) {
			$actions = $this->defineActions($column_data);
			$update = $actions['update'];
			$delete = $actions['delete'];
		
			$reference_table = $column_data['reference']['table_name'];
			$reference_column = $column_data['reference']['column_name'];
		
			$sql = "ALTER TABLE `$table_name` ADD CONSTRAINT `fk_{$table_name}_{$column_name}` FOREIGN KEY(`$column_name`) REFERENCES `$reference_table`(`$reference_column`) ON UPDATE $update ON DELETE $delete;";
			$requests[] = $sql;
		}
		
		if ($execute === false) {
			$this->requests[2] = array_merge($this->requests[2],$requests);
		}
		else {
			try {
				foreach($requests as $request) {
					$this->db->exec($request);
				}
			}
			catch(PDOException $e) {
				die("Fatal error: Table creation failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
	}
	
	/*
	 * Returns the table name linked to a reference.
	 *
	 * @param string $reference Reference string
	 * 
	 * @param string Table name or false
	 */
	
	public function getTableByReference($reference)
	{
		$sql = "SHOW TABLE STATUS WHERE Comment=:reference";
		
		$request = $this->db->prepare($sql);
		$request->bindParam(':reference',$reference);
		$request->execute();
		
		if ($request->rowCount() != 0) {
			$result = $request->fetchObject();
			
			return $result->Name;
		}
		
		return false;
	}
	
	/*
	 * Returns the column name linked to a reference.
	 *
	 * @param string $table_name Table name
	 * @param string $reference Reference string
	 * 
	 * @param string Column name or false
	 */
	
	public function getColumnByReference($table_name,$reference)
	{
		$sql = "SHOW FULL COLUMNS FROM $table_name WHERE Comment=:reference;";
		
		$request = $this->db->prepare($sql);
		$request->bindParam(':reference',$reference);
		$request->execute();
		
		if ($request->rowCount() != 0) {
			$result = $request->fetchObject();
			
			return [
				'name' => $result->Field,
				'type' => $result->Type
			];
		}
		
		return false;
	}
	
	/*
	 * Returns existing fields for a table.
	 *
	 * @param string $table_name Table name
	 *
	 * @return array Fields list
	 */
	
	public function getColumns($table_name)
	{
		$fields = [];
		
		$sql = "SHOW FULL COLUMNS FROM $table_name";
		
		$request = $this->db->query($sql);	
		
		if ($request->rowCount() != 0) {
			while ($result = $request->fetchObject()) {
				$fields[$result->Comment] = $result->Field;
			}
		}
		
		return $fields;
	}
	
	/*
	 * Returns every references of a table.
	 *
	 * @param string $table_name Table name
	 *
	 * @return array References list
	 */
	
	public function getReferences($table_name)
	{
		$references = [];
		
		$sql = "SELECT * FROM information_schema.key_column_usage WHERE REFERENCED_TABLE_NAME=:table_name;";
		
		$request = $this->db->prepare($sql);
		$request->bindParam(':table_name',$table_name);
		$request->execute();		
		
		if ($request->rowCount() != 0) {
			while ($result = $request->fetchObject()) {
				$references[] = $result;
			}
		}
		
		return $references;
	}
	
	/*
	 * Removes references passed in parameter.
	 *
	 * @param array $references References data
	 */
	
	public function removeReferences($references)
	{
		if (!empty($references)) {
			try {
				$this->db->beginTransaction();
		
				foreach($references as $reference) {
					$constraint_name = $reference->CONSTRAINT_NAME;
					$table_name = $reference->TABLE_NAME;
					
					$sql = "ALTER TABLE $table_name DROP FOREIGN KEY $constraint_name;";
					
					$this->db->exec($sql);
				}
				
				$this->db->commit();
			}
			catch(PDOException $e) {
				$this->db->rollback();
				
				die("Fatal error: Table references removing failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
	}
	
	/*
	 * Removes a table of the database.
	 *
	 * @param string $table_name Table to remove
	 */
	
	public function dropTable($table_name)
	{
		try {
			$sql = "DROP TABLE $table_name;";
			
			$this->db->exec($sql);
		}
		catch(PDOException $e) {
			die("Fatal error: Table removing failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
		}
	}
	
	/*
	 * Removes a column from its table.
	 *
	 * @param string $table_name Table name
	 * @param string $column_name Column name to drop
	 */
	
	public function dropColumn($table_name,$column_name)
	{
		try {
			$sql = "ALTER TABLE $table_name DROP COLUMN $column_name;";
			
			$this->db->exec($sql);
		}
		catch(PDOException $e) {
			die("Fatal error: Column removing failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
		}
	}
	
	/*
	 * Executes requests on the database.
	 *
	 * @return bool
	 */
	
	public function execute()
	{
		$requests = '';
		ksort($this->requests);
		
		foreach($this->requests as $priority_id=>$requests_list) {
			foreach($requests_list as $request) {
				$requests.= $request;
			}
		}
		
		echo $requests;
	
		if (!empty($this->requests)) {
			try {
				$this->db->exec($requests);
				return true;
			}
			catch(PDOException $e) {
				die("Fatal error: Database update queries failed.\n> Callback message: " . $e->getMessage() . "\n> Error code: " . $e->getCode() . "\n\n");
			}
		}
		else {
			return true;
		}
	}
	
	/*
	 * Builds a schema using a table configuration.
	 */
	
	public function getSchema($table_name)
	{
		$auth = Config::$databases[Config::$db_to_use];
		$schema = [$table_name => []];
		
		$request = $this->db->prepare("SELECT TABLE_COMMENT
										FROM information_schema.tables 
										WHERE TABLE_SCHEMA=:db_name 
										AND TABLE_NAME=:table_name");
		$request->bindParam(':db_name',$auth['dbname']);
		$request->bindParam(':table_name',$table_name);
		$request->execute();
		
		if ($request->rowCount() != 0) {
			$result = $request->fetchObject();
			
			if ($result->TABLE_COMMENT != '') {
				$schema[$table_name]['_params']['_ref'] = str_replace('#','',$result->TABLE_COMMENT);
			}
			
			$request = $this->db->prepare("SELECT * 
											FROM information_schema.columns 
											WHERE TABLE_SCHEMA=:db_name 
											AND TABLE_NAME=:table_name 
											ORDER BY ORDINAL_POSITION ASC");
			$request->bindParam(':db_name',$auth['dbname']);
			$request->bindParam(':table_name',$table_name);
			$request->execute();
			
			if ($request->rowCount() != 0) {
				while ($result = $request->fetchObject()) {
					$request_key = $this->db->prepare("SELECT KCU.REFERENCED_TABLE_NAME, KCU.REFERENCED_COLUMN_NAME, RC.UPDATE_RULE, RC.DELETE_RULE
														FROM information_schema.referential_constraints AS RC, information_schema.key_column_usage AS KCU
														WHERE RC.CONSTRAINT_NAME=KCU.CONSTRAINT_NAME
														AND KCU.CONSTRAINT_SCHEMA=:db_name
														AND KCU.TABLE_NAME=:table_name 
														AND KCU.COLUMN_NAME=:column_name 
														AND KCU.CONSTRAINT_NAME!='PRIMARY'");
														
					$request_key->bindParam(':db_name',$auth['dbname']);
					$request_key->bindParam(':table_name',$table_name);
					$request_key->bindParam(':column_name',$result->COLUMN_NAME);
					$request_key->execute();
					
					$type = SchemaBuilder::getType($result->DATA_TYPE);
					
					$schema[$table_name][$result->COLUMN_NAME]['type'] = $type;
					
					if ($result->CHARACTER_MAXIMUM_LENGTH !== null) {
						$schema[$table_name][$result->COLUMN_NAME]['length'] = $result->CHARACTER_MAXIMUM_LENGTH;
					}
					
					if ($result->COLUMN_DEFAULT !== null) {
						$schema[$table_name][$result->COLUMN_NAME]['default'] = $result->COLUMN_DEFAULT;
					}
					
					if ($result->IS_NULLABLE !== null) {
						$schema[$table_name][$result->COLUMN_NAME]['null'] = $result->IS_NULLABLE == 'YES' ? true : false;
					}
					
					if ($type == 'float') {
						$schema[$table_name][$result->COLUMN_NAME]['length'] = $result->NUMERIC_PRECISION;
						$schema[$table_name][$result->COLUMN_NAME]['decimal'] = $result->NUMERIC_SCALE;
					}
					
					if ($result->COLUMN_KEY == 'PRI') {
						$schema[$table_name][$result->COLUMN_NAME]['identifier'] = true;
					}
					
					if ($result->EXTRA == 'auto_increment') {
						$schema[$table_name][$result->COLUMN_NAME]['auto'] = true;
					}
					
					if (preg_match('/zerofill/',$result->COLUMN_TYPE)) {
						$schema[$table_name][$result->COLUMN_NAME]['zerofill'] = true;
					}
					
					if ($result->COLUMN_COMMENT != '') {
						$schema[$table_name][$result->COLUMN_NAME]['_ref'] = str_replace('#','',$result->COLUMN_COMMENT);
					}
					
					if ($request_key->rowCount() != 0) {
						$result_key = $request_key->fetchObject();
						
						$schema[$table_name][$result->COLUMN_NAME]['indexOf'] = [
							'table' => $result_key->REFERENCED_TABLE_NAME,
							'property' => $result_key->REFERENCED_COLUMN_NAME,
							'update' => $result_key->UPDATE_RULE,
							'delete' => $result_key->DELETE_RULE
						];
					}
				}
			}
		}
		
		return $schema;
	}
	
}