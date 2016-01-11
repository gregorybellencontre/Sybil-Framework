<?php
/*
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

/**
 * The Yaml class initializes and parses
 * Yaml content into PHP array.
 *
 * @author Grégory Bellencontre
 */
final class Yaml 
{
	static $native = false;
	
	/*
	 * Sets the PHP extension or the Symfony vendor.
	 */
	
	public static function init() 
	{
		if (!function_exists('yaml_parse')) {
			self::$native = false;
		}
		else {
			self::$native = true;
		}
	}
	
	/*
	 * Parses Yaml content.
	 *
	 * @param string $content Yaml content to parse
	 *
	 * @return array The parsed version.
	 */
	
	public static function parse($content) 
	{
		if (self::$native === false) {
			$yaml = new \Symfony\Component\Yaml\Yaml();
			
			return new Collection($yaml->parse($content));
		}
		else {
			return new Collection(yaml_parse($content));
		}
	}
	
	/*
	 * Dump an array into Yaml.
	 *
	 * @param array $array Source array
	 *
	 * @return string The dumped version.
	 */
	
	public static function dump($array,$inline=2,$indent=2) 
	{
		$yaml = new \Symfony\Component\Yaml\Yaml();
			
		return $yaml->dump($array,$inline,$indent);
	}
	
}
?>