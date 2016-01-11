<?php
/*
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */
 
namespace Sybil;

/**
 * The App class contains utils functions.
 *
 * Some functions are taken and customized from 
 * http://github.com/brandonwamboldt/utilphp/
 * (c) Brandon Wamboldt <brandon.wamboldt@gmail.com>
 *
 * @author Grégory Bellencontre, Brandon Wamboldt
 */
final class App
{
	/**
	 * Debug icons
	 */
	 
	public static $icon_expand = 'iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJCAMAAADXT/YiAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2RpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo3MTlFRjQ2NkM5QzJFMTExOTA0MzkwRkI0M0ZCODY4RCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpFQzZERTJDNEMyQzkxMUUxODRCQzgyRUNDMzZEQkZFQiIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpFQzZERTJDM0MyQzkxMUUxODRCQzgyRUNDMzZEQkZFQiIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1IFdpbmRvd3MiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo3MzlFRjQ2NkM5QzJFMTExOTA0MzkwRkI0M0ZCODY4RCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo3MTlFRjQ2NkM5QzJFMTExOTA0MzkwRkI0M0ZCODY4RCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PkmDvWIAAABIUExURU9t2MzM/3iW7ubm59/f5urq85mZzOvr6////9ra38zMzObm5rfB8FZz5myJ4SNFrypMvjBStTNmzOvr+mSG7OXl8T9h5SRGq/OfqCEAAABKSURBVHjaFMlbEoAwCEPRULXF2jdW9r9T4czcyUdA4XWB0IgdNSybxU9amMzHzDlPKKu7Fd1e6+wY195jW0ARYZECxPq5Gn8BBgCr0gQmxpjKAwAAAABJRU5ErkJggg==';
	
	public static $icon_collapse = 'iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJCAMAAADXT/YiAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2RpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo3MjlFRjQ2NkM5QzJFMTExOTA0MzkwRkI0M0ZCODY4RCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpFNzFDNDQyNEMyQzkxMUUxOTU4MEM4M0UxRDA0MUVGNSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpFNzFDNDQyM0MyQzkxMUUxOTU4MEM4M0UxRDA0MUVGNSIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1IFdpbmRvd3MiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo3NDlFRjQ2NkM5QzJFMTExOTA0MzkwRkI0M0ZCODY4RCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo3MjlFRjQ2NkM5QzJFMTExOTA0MzkwRkI0M0ZCODY4RCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PuF4AWkAAAA2UExURU9t2DBStczM/1h16DNmzHiW7iNFrypMvrnD52yJ4ezs7Onp6ejo6P///+Tk5GSG7D9h5SRGq0Q2K74AAAA/SURBVHjaLMhZDsAgDANRY3ZISnP/y1ZWeV+jAeuRSky6cKL4ryDdSggP8UC7r6GvR1YHxjazPQDmVzI/AQYAnFQDdVSJ80EAAAAASUVORK5CYII=';

	/**
	 * Encrypts a string.
	 *
	 * @param string $string String to encrypt
	 *
	 * @return string String encrypted
	 */

	public static function hash($string) 
	{
		return sha1(Config::$app_id . md5(sha1($string)) . Config::$app_id);
	}
	
	/**
	 * Converts an underscore string to CamelCase
	 *
	 * @param string $string Underscored string
	 *
	 * @return string CamelCase string
	 */
	
	public static function toCamelCase($string) 
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
	
	public static function unCamelCase($string)
	{
		return strtolower(preg_replace('/(?|([a-z\d])([A-Z])|([^\^])([A-Z][a-z]))/', '$1_$2', $string));
	}
	
	/**
	 * Pluralizes an english word.
	 *
	 * @param string $singular Word to pluralize
	 *
	 * @return string Word pluralized
	 */
	
	public static function pluralize($singular)
	{
		$exceptions = array(
		    'appendix' => 'appendices',
		    'barracks' => 'barracks',
		    'cactus' => 'cacti',
		    'child' => 'children',
		    'criterion' => 'criteria',
		    'deer' => 'deer',
		    'echo' => 'echoes',
		    'elf' => 'elves',
		    'embargo' => 'embargoes',
		    'focus' => 'foci',
		    'fungus' => 'fungi',
		    'goose' => 'geese',
		    'hero' => 'heroes',
		    'hoof' => 'hooves',
		    'index' => 'indices',
		    'knife' => 'knives',
		    'leaf' => 'leaves',
		    'life' => 'lives',
		    'man' => 'men',
		    'mouse' => 'mice',
		    'nucleus' => 'nuclei',
		    'person' => 'persons',
		    'phenomenon' => 'phenomena',
		    'potato' => 'potatoes',
		    'self' => 'selves',
		    'syllabus' => 'syllabi',
		    'tomato' => 'tomatoes',
		    'torpedo' => 'torpedoes',
		    'veto' => 'vetoes',
		    'woman' => 'women',
		);
		
		$vowels = array('a','e','i','o','u');
	
		if (array_key_exists($singular,$exceptions)) {
			return $exceptions[$singular];
		}
		
		$root = $singular;
		
		if (substr($singular, -1,1) == 'y' && !in_array(substr($singular, -2,1),$vowels)) {
			$root = substr($singular,0,-1);
			$suffix = 'ies';
		}
		elseif (substr($singular, -1,1) == 's') {
			if (in_array(substr($singular, -2,1),$vowels)) {
				$root = substr($singular, 0, -1);
				$suffix = 'ses';
			}
			else {
				$suffix = 'es';
			}
		}
		elseif (in_array(substr($singular, -2),array('ch','sh'))) {
			$suffix = 'es';
		}
		else {
			$suffix = 's';
		}
		
		return $root . $suffix;
	}
	
	/**
	 * Computes the difference of arrays with additional index chec
	 * [Recursive version]
	 *
	 * @param array $array1 The array to compare from
	 * @param array $array2 An array to compare against
	 *
	 * @return array Array differences
	 */

	public static function array_diff_assoc_recursive($array1, $array2) 
	{ 
	    foreach($array1 as $key => $value) 
	    { 
	        if(is_array($value)) 
	        { 
	              if(!isset($array2[$key])) 
	              { 
	                  $difference[$key] = $value; 
	              } 
	              elseif(!is_array($array2[$key])) 
	              { 
	                  $difference[$key] = $value; 
	              } 
	              else 
	              { 
	                  $new_diff = self::array_diff_assoc_recursive($value, $array2[$key]); 
	                  if($new_diff != FALSE) 
	                  { 
	                        $difference[$key] = $new_diff; 
	                  } 
	              } 
	          } 
	          elseif(!isset($array2[$key]) || $array2[$key] != $value) 
	          { 
	              $difference[$key] = $value; 
	          } 
	    } 
	    return !isset($difference) ? 0 : $difference; 
	} 

	/**
	 * Converts a Mb or Gb value in Mb.
	 *
	 * @param array $size Array containing value and format ([1024 => 'Gb'])
	 *
	 * @return integer Mb value
	 */

    public static function parseToMB(array $size) 
    {
	    if (strtolower($size[1]) == 'gb' || strtolower($size[1]) == 'go') {
    	    return $size[0] * 1024;
	    }
	    elseif (strtolower($size[1]) == 'mb' || strtolower($size[1]) == 'mo') { 
    	    return $size[0];
	    }
	    else {
    	    return 0;
	    }
    }
    
    /**
	 * Converts a Ko or Mo value in octets.
	 *
	 * @param array $size Array containing value and format ([1024 => 'Gb'])
	 *
	 * @return integer Octets value
	 */ 
     
    public static function parseToOctets($size) 
    {
	    $octets = 0;
	    
	    foreach($data as $value=>$unit) {
		    if (strtolower($unit) == 'ko' || strtolower($unit) == 'kb') {
			    $octets = $value * 1024;
		    }
		    elseif (strtolower($unit) == 'mo' || strtolower($unit) == 'mb') {
			    $octets = $value * 1048576;
		    }
	    }
	    
	    return $octets;
    }
    
	/**
	 * Converts an array into JSON.
	 *
	 * @param mixed $key Key or array with keys and values
	 * @param mixed $value A value for the given key (if it's a string)
	 *
	 * @return object JSON object
	 */
     
    public static function JSONcallback($key,$value=null) 
    {
        if (is_array($key)) {
	        return json_encode((object) $key);
	    }
	    else {
    	    return json_encode((object) array($key=>$value));
	    }
    }
    
    /**
     * Translates and debugs a string.
     *
     * @param string $string String to translate
     * @param object $trans_object The translation object
     * @param array $params Custom parameters (controller,theme,domain)
     *
     * @return html
     */
     
    public static function translate($string,$trans_object,$params=null,$stop=false) 
    {
	    self::debug($trans_object->translate($string,$params,$stop));
    }
     
    /**
     * Displays a variable content properly.
     *
     * @param mixed $var The variable to display
     * @param bool $object Option to active when variable is a model object
     * @param bool $backtrace Option for backtrace activation
     *
     * @return html
     */
     
    public static function debug($var, $backtrace=true)
    {
        header('Content-type: text/html; charset=utf-8');
        
        $html = '<pre style="margin-bottom: 18px;' .
            'background: #f7f7f9;' .
            'border: 1px solid #e1e1e8;' .
            'padding: 8px;' .
            'border-radius: 4px;' .
            '-moz-border-radius: 4px;' .
            '-webkit-border radius: 4px;' .
            'display: block;' .
            'font-size: 12.05px;' .
            'white-space: pre-wrap;' .
            'word-wrap: break-word;' .
            'color: #333;' .
            'font-family: Menlo,Monaco,Consolas,\'Courier New\',monospace;">';
            
        if (isset($var)) {
			$html.= self::var_dump_plain($var);
		}
		else {
			if (ENVIRONMENT == 'development') {
				$html = "ERROR: Variable to debug is not defined.";
			}
		}
		
		if ($backtrace === true) {
			$backtrace = debug_backtrace();
			
			$html.= '<br><br>';
			
			foreach($backtrace as $key=>$file) {
				$html.= (isset($file['file']) && $key <= 3) ? '<strong>' . $file['file'] . '</strong> - line ' . $file['line'] . '<br>' : '';
			}
		}
		
        $html .= '</pre>';

        echo $html;
    }

    /**
     * Converts a string into slug.
     *
     * @param string $string The string to convert
     *
     * @return string
     */
     
    public static function slugify($string)
    {
        $slug = preg_replace('/([^a-z0-9]+)/', '-', strtolower(self::remove_accents($string)));

        return $slug;
    }

    /**
     * Check if a string starts with the given string.
     *
     * @param string $string The complete string
     * @param string $starts_with The string piece to test
     *
     * @return boolean
     */
     
    public static function starts_with($string, $starts_with)
    {
        return (strpos($string, $starts_with) === 0);
    }

    /**
     * Check if a string ends with the given string.
     *
     * @param string $string The complete string
     * @param string $ends_with The string piece to test
     *
     * @return boolean
     */
     
    public static function ends_with($string, $ends_with)
    {
        return substr($string, -strlen($ends_with)) === $ends_with;
    }

    /**
     * Check if a string ($haystack) contains another string ($needle).
     *
     * @param string $haystack First string
     * @param string $needle Second string
     *
     * @return boolean
     */
     
    public static function str_contains($haystack, $needle)
    {
        return (strpos($haystack, $needle) !== false);
    }

    /**
     * Check if a string ($haystack) contains another string ($needle). 
     * This version is case insensitive.
     *
     * @param string $haystack First string
     * @param string $needle Second string
     *
     * @return boolean
     */
     
    public static function str_icontains($haystack, $needle)
    {
        return (stripos($haystack, $needle) !== false);
    }

    /**
     * Returns the file extension of the given filename.
     *
     * @param string $filename File path
     *
     * @return string The file extension
     */
     
    public static function file_ext($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * Converts all accent characters to ASCII characters.
     *
     * If there are no accent characters, then the string given is just
     * returned.
     *
     * @param  string $string  Text that might have accent characters
     * @return string Filtered string with replaced "nice" characters
     */
     
    public static function remove_accents($string)
    {
        if (!preg_match('/[\x80-\xff]/',$string)) {
            return $string;
        }

        if (self::seems_utf8($string)) {
            $chars = array(
                // Decompositions for Latin-1 Supplement
                chr(194).chr(170) => 'a', chr(194).chr(186) => 'o',
                chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
                chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
                chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
                chr(195).chr(134) => 'AE',chr(195).chr(135) => 'C',
                chr(195).chr(136) => 'E', chr(195).chr(137) => 'E',
                chr(195).chr(138) => 'E', chr(195).chr(139) => 'E',
                chr(195).chr(140) => 'I', chr(195).chr(141) => 'I',
                chr(195).chr(142) => 'I', chr(195).chr(143) => 'I',
                chr(195).chr(144) => 'D', chr(195).chr(145) => 'N',
                chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
                chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
                chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
                chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
                chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
                chr(195).chr(158) => 'TH',chr(195).chr(159) => 's',
                chr(195).chr(160) => 'a', chr(195).chr(161) => 'a',
                chr(195).chr(162) => 'a', chr(195).chr(163) => 'a',
                chr(195).chr(164) => 'a', chr(195).chr(165) => 'a',
                chr(195).chr(166) => 'ae',chr(195).chr(167) => 'c',
                chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
                chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
                chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
                chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
                chr(195).chr(176) => 'd', chr(195).chr(177) => 'n',
                chr(195).chr(178) => 'o', chr(195).chr(179) => 'o',
                chr(195).chr(180) => 'o', chr(195).chr(181) => 'o',
                chr(195).chr(182) => 'o', chr(195).chr(184) => 'o',
                chr(195).chr(185) => 'u', chr(195).chr(186) => 'u',
                chr(195).chr(187) => 'u', chr(195).chr(188) => 'u',
                chr(195).chr(189) => 'y', chr(195).chr(190) => 'th',
                chr(195).chr(191) => 'y', chr(195).chr(152) => 'O',

                // Decompositions for Latin Extended-A
                chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
                chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
                chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
                chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
                chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
                chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
                chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
                chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
                chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
                chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
                chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
                chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
                chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
                chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
                chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
                chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
                chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
                chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
                chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
                chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
                chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
                chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
                chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
                chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
                chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
                chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
                chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
                chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
                chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
                chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
                chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
                chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
                chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
                chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
                chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
                chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
                chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
                chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
                chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
                chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
                chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
                chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
                chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
                chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
                chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
                chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
                chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
                chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
                chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
                chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
                chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
                chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
                chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
                chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
                chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
                chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
                chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
                chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
                chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
                chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
                chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
                chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
                chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
                chr(197).chr(190) => 'z', chr(197).chr(191) => 's',

                // Decompositions for Latin Extended-B
                chr(200).chr(152) => 'S', chr(200).chr(153) => 's',
                chr(200).chr(154) => 'T', chr(200).chr(155) => 't',

                // Euro Sign
                chr(226).chr(130).chr(172) => 'E',
                // GBP (Pound) Sign
                chr(194).chr(163) => ''
            );

            $string = strtr( $string, $chars );
        } else {

            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
                 .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
                 .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
                 .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
                 .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
                 .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
                 .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
                 .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
                 .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
                 .chr(252).chr(253).chr(255);

            $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';

            $string = strtr( $string, $chars['in'], $chars['out'] );
            $double_chars['in'] = array( chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254) );
            $double_chars['out'] = array( 'OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th' );
            $string = str_replace( $double_chars['in'], $double_chars['out'], $string );
        }

        return $string;
    }

    /**
     * Removes all witespaces from the given string.
     *
     * @param string $string The string to parse
     *
     * @return string
     */
     
    public static function remove_spaces($string)
    {
        return preg_replace('/\s+/', '', $string);
    }

    /**
     * Sanitizes a string by performing the following operation :
     * - Remove accents
     * - Lower the string
     * - Remove punctuation characters
     * - Strip whitespaces
     *
     * @param string $string The string to sanitize
     *
     * @return string
     */
     
    public static function sanitize_string($string)
    {
        $string = self::remove_accents($string);
        $string = strtolower($string);
        $string = preg_replace('/[^a-zA-Z 0-9]+/', '', $string);
        $string = self::remove_spaces($string);
        return $string;
    }

    /**
     * Pads a given string with zeroes on the left.
     *
     * @param int $number The number to pad
     * @param int $length The wanted length
     *
     * @return string
     */
     
    public static function pad_number($number, $length)
    {
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }
    
    /**
     * Transforms a variable into readable content, with indentations
     * and color.
     *
     * @param mixed $var The variable to parse
     *
     * @return html
     */
     
    public static function var_dump_plain($var)
    {
        $html = '';

        if (is_bool($var)) {
            $html .= '<span style="color:#588bff;">bool</span><span style="color:#999;">(</span><strong>' . (($var) ? 'true' : 'false') . '</strong><span style="color:#999;">)</span>';
        } elseif (is_int($var)) {
            $html .= '<span style="color:#588bff;">int</span><span style="color:#999;">(</span><strong>' . $var . '</strong><span style="color:#999;">)</span>';
        } elseif (is_float($var)) {
            $html .= '<span style="color:#588bff;">float</span><span style="color:#999;">(</span><strong>' . $var . '</strong><span style="color:#999;">)</span>';
        } elseif (is_string($var)) {
            $html .= '<span style="color:#588bff;">string</span><span style="color:#999;">(</span>' . strlen($var) . '<span style="color:#999;">)</span> <strong>"' . htmlentities($var) . '"</strong>';
        } elseif (is_null($var) ) {
            $html .= '<strong>NULL</strong>';
        } elseif (is_resource($var)) {
            $html .= '<span style="color:#588bff;">resource</span>("' . get_resource_type($var) . '") <strong>"' . $var . '"</strong>';
        } elseif (is_array($var)) {
            $uuid = 'include-php-' . uniqid();

            $html .= '<span style="color:#588bff;">array</span>(' . count($var) . ')';

            if (!empty($var)) {
                $html .= ' <img id="' . $uuid . '" data-expand="data:image/png;base64,' . self::$icon_expand . '" style="position:relative;left:-5px;top:-1px;cursor:pointer;" src="data:image/png;base64,' . self::$icon_collapse . '" /><br /><span id="' . $uuid . '-collapsable">[<br />';

                $indent = 4;
                $longest_key = 0;

                foreach($var as $key=>$value) {
                    $longest_key = is_string($key) ? max($longest_key, strlen($key) + 2) : max($longest_key, strlen($key));
                }

                foreach ($var as $key=>$value) {
                    $html .= is_numeric($key) ? str_repeat(' ',$indent) . str_pad($key,$longest_key,' ') : str_repeat(' ',$indent) . str_pad('"' . htmlentities($key) . '"', $longest_key, ' ');

                    $html .= ' => ';

                    $value = explode('<br />', self::var_dump_plain($value));

                    foreach ($value as $line=>$val) {
                        if ($line != 0) {
                            $value[$line] = str_repeat(' ', $indent * 2) . $val;
                        }
                    }

                    $html .= implode('<br />', $value) . '<br />';
                }

                $html .= ']</span>';

                $html .= preg_replace('/ +/', ' ', '<script type="text/javascript">(function(){var e=document.getElementById("' . $uuid . '");e.onclick=function(){if(document.getElementById("' . $uuid . '-collapsable").style.display=="none"){document.getElementById("' . $uuid . '-collapsable").style.display="inline";e.src=e.getAttribute("data-collapse");var t=document.getElementById("' . $uuid . '-collapsable").previousSibling;while(t!=null&&(t.nodeType!=1||t.tagName.toLowerCase()!="br")){t=t.previousSibling}if(t!=null&&t.tagName.toLowerCase()=="br"){t.style.display="inline"}}else{document.getElementById("' . $uuid . '-collapsable").style.display="none";e.setAttribute("data-collapse",e.getAttribute("src"));e.src=e.getAttribute("data-expand");var t=document.getElementById("' . $uuid . '-collapsable").previousSibling;while(t!=null&&(t.nodeType!=1||t.tagName.toLowerCase()!="br")){t=t.previousSibling}if(t!=null&&t.tagName.toLowerCase()=="br"){t.style.display="none"}}}})()</script>');
            }
        } else if (is_object($var)) {
            $uuid = 'include-php-' . uniqid();

            $html .= '<span style="color:#588bff;">object</span>(' . get_class($var) . ') <img id="' . $uuid . '" data-expand="data:image/png;base64,' . self::$icon_expand . '" style="position:relative;left:-5px;top:-1px;cursor:pointer;" src="data:image/png;base64,' . self::$icon_collapse . '" /><br /><span id="' . $uuid . '-collapsable">[<br />';

            $original = $var;
            $var = (array) $var;

            $indent = 4;
            $longest_key = 0;

            foreach($var as $key=>$value) {
                if (substr($key, 0, 2) == "\0*") {
                    unset($var[$key]);
                    $key = 'protected:' . substr($key, 2);
                    $var[$key] = $value;
                } else if (substr($key, 0, 1) == "\0") {
                    unset($var[$key]);
                    $key = 'private:' . substr($key, 1, strpos(substr($key, 1), "\0")) . ':' . substr($key, strpos(substr($key, 1), "\0") + 1);
                    $var[$key] = $value;
                }

                $longest_key = is_string($key) ? max($longest_key, strlen($key) + 2) : max($longest_key, strlen($key));
            }

            foreach ($var as $key=>$value) {
				$html .= is_numeric($key) ? str_repeat(' ', $indent) . str_pad($key, $longest_key, ' ') : str_repeat(' ', $indent) . str_pad('"' . htmlentities($key) . '"', $longest_key, ' ');
                $html .= ' => ';

                $value = explode('<br />', self::var_dump_plain($value));

                foreach ($value as $line => $val) {
                    if ($line != 0) {
                        $value[$line] = str_repeat(' ', $indent * 2) . $val;
                    }
                }

                $html .= implode('<br />', $value) . '<br />';
            }

            $html .= ']</span>';

            $html .= preg_replace('/ +/', ' ', '<script type="text/javascript">(function(){var e=document.getElementById("' . $uuid . '");e.onclick=function(){if(document.getElementById("' . $uuid . '-collapsable").style.display=="none"){document.getElementById("' . $uuid . '-collapsable").style.display="inline";e.src=e.getAttribute("data-collapse");var t=document.getElementById("' . $uuid . '-collapsable").previousSibling;while(t!=null&&(t.nodeType!=1||t.tagName.toLowerCase()!="br")){t=t.previousSibling}if(t!=null&&t.tagName.toLowerCase()=="br"){t.style.display="inline"}}else{document.getElementById("' . $uuid . '-collapsable").style.display="none";e.setAttribute("data-collapse",e.getAttribute("src"));e.src=e.getAttribute("data-expand");var t=document.getElementById("' . $uuid . '-collapsable").previousSibling;while(t!=null&&(t.nodeType!=1||t.tagName.toLowerCase()!="br")){t=t.previousSibling}if(t!=null&&t.tagName.toLowerCase()=="br"){t.style.display="none"}}}})()</script>');
        }

        return $html;
    }
}