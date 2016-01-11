<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil\ORM\DBMS;

use PDO;
use PDOException;

/**
 * The MySQL class provides query methods 
 * for children models.
 *
 * @author Grégory Bellencontre
 */
abstract class Model extends \Sybil\ORM\Model
{
	/*
	 * Sends a save operation, for this object, 
	 * in the pending queue.
	 */
	
	public function save()
	{
		
	}
	
	/*
	 * Sends a remove operation, for this object, 
	 * in the pending queue.
	 */
	
	public function remove()
	{
		
	}
	
	/*
	 * Executes the pending operations.
	 */
	
	public function persist()
	{
		
	}
	
}