<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

use Sybil\ORM\ORM;

/**
 * The Command class contains development functions
 * available in the framework command line tool.
 *
 * @author Grégory Bellencontre
 */
final class Command 
{

	public static function DBSchemaUpdate() 
	{
		$orm = new ORM();
		$orm->update();
	}
	
	public static function DBSchemaReset()
	{
		$orm = new ORM();
		$orm->removeReferences();
	}
	
}