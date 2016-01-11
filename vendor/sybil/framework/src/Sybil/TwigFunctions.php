<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

use Twig_SimpleFunction;

/**
 * The TwigFunctions class contains functions
 * that can be used in Twig templates.
 *
 * @author Grégory Bellencontre
 */
final class TwigFunctions 
{
	/*
	 * Loads each custom function in the Twig environment.
	 *
	 * @param object Twig environment object (passed by reference)
	 */

	public static function initFunctions(&$twig)
	{
		$methods = get_class_methods(get_called_class());
		$method_name = explode('::',__METHOD__)[1];
		
		if (!empty($methods)) {
			foreach($methods as $method) {
				if ($method != $method_name) {
					$twig->addFunction(self::$method());
				}
			}
		}
	}

	/*
	 * Loads a bundle in a view.
	 *
	 * @param string $target Route target
	 * 
	 * @return mixed The template content or false
	 */
	
	public static function loadBundle() 
	{
		return new Twig_SimpleFunction('loadBundle', function ($target) {
		    if (preg_match('#^([a-z]+):([A-Z][a-zA-Z]+):([a-zA-Z]+)$#',$target)) {
				list($bundle,$controller,$method) = explode(':',$target);
			}
			elseif (preg_match('#^([A-Z][a-zA-Z]+):([a-zA-Z]+)$#',$target)) {
				list($controller,$method) = explode(':',$target);
				$bundle = strtolower($controller);
			}
			elseif (preg_match('#^([a-z]+):([A-Z][a-zA-Z]+)$#',$target)) {
				list($bundle,$controller) = explode(':',$target);
				$method = 'index';
			}
			elseif (preg_match('#^([A-Z][a-zA-Z]+)$#',$target)) {
				$controller = $target;
				$bundle = strtolower($controller);
				$method = 'index';
			}
			
			if (isset($controller)) {
				$controller_path = BUNDLE.$bundle.'/controller/'.$controller.'Controller.php';
				
				$params = new Collection();
				$params->bundle = $bundle;
				$params->prefix = '';
				$params->controller = $controller;
				$params->namespace = '\Sybil\Controller\\' . $controller . '\\';
				$params->method = $method;
				$params->params = array();
				
				$controller = $params->namespace . $controller.'Controller';
				
				if (file_exists($controller_path)) {
					require_once($controller_path);
					$ctrl = new $controller($params,false);
					return $ctrl->template;
				}
				else {
					return false;
				}
			}
			else {
				return false;
			}
		});
	}
	
	/*
	 * Gets a route URL in a view
	 *
	 * @param string The route name
	 * @params variable Undefined number of variables to fill the URL params
	 * 
	 * @return string The route URL
	 */
	
	public static function path() 
	{
		return new Twig_SimpleFunction('path', function () {
			$args = func_get_args();
			$route_name = $args[0];
			unset($args[0]);
			
			if ($route_name == '@prev') {
    			return $_SERVER['HTTP_REFERER'];
			}
			else {
    			$params = array();
    			
    			foreach($args as $value) {
    				$params[] = $value;
    			}
    			
    			return ROOT.preg_replace('#^/#','',Routing::get($route_name,$params));
			}
		});
	}
	
	/*
	 * Gets an asset file
	 *
	 * @param string $path The path from the webroot directory
	 * 
	 * @return string The complete URL
	 */
	 
	public static function asset() 
	{
		return new Twig_SimpleFunction('asset', function($path,$alt=null) {
		    if (file_exists(FILE_ROOT.'web/'.$path)) {
			    return WEB.$path;
			}
			else {
    			return WEB.$alt;
			}
		});
	}
	
	/*
	 * Reduces a string and adds ... if necessary
	 * 
	 * @param string The string to reduce
	 *
	 * @return string The string reduced
	 */
	 
	public static function reduce() 
	{
		return new Twig_SimpleFunction('reduce', function($string,$limit) {
			if (strlen($string) > $limit) {
    			return substr($string,0,$limit) . '...';
			}
			else {
    			return $string;
			}
		});
	}
	
	/*
	 * Sets the translation bundle, theme and domain
	 * 
	 * @param string $bundle Bundle to use for translation
	 * @param string $theme Theme to use for translation
	 * @param string $domain Domain to use for translation
	 */
	 
	public static function trans_init() 
	{
		return new Twig_SimpleFunction('trans_init', function($bundle=null,$theme=null,$domain=null) {
			$GLOBALS['twig_vars']['trans_bundle'] = $bundle;
			$GLOBALS['twig_vars']['trans_theme'] = $theme;
			$GLOBALS['twig_vars']['trans_domain'] = $domain;
		});
	}
	
	/*
	 * Sets the translation bundle
	 *
	 * @param string $bundle Bundle to use for translation
	 */
	
	public static function trans_bundle() 
	{
		return new Twig_SimpleFunction('trans_bundle', function($bundle=null) {
			$GLOBALS['twig_vars']['trans_bundle'] = $bundle;
			$GLOBALS['twig_vars']['trans_theme'] = null;
		});
	}
	
	/*
	 * Sets the translation theme
	 *
	 * @param string $theme Theme to use for translation
	 */
	
	public static function trans_theme() 
	{
		return new Twig_SimpleFunction('trans_theme', function($theme=null) {
			$GLOBALS['twig_vars']['trans_theme'] = $theme;
			$GLOBALS['twig_vars']['trans_bundle'] = false;
		});
	}
	
	/*
	 * Sets the translation domain.
	 *
	 * @param string $domain Domain to use for translation
	 */
	
	public static function trans_domain() 
	{
		return new Twig_SimpleFunction('trans_domain', function($domain=null) {
			$GLOBALS['twig_vars']['trans_domain'] = $domain;
		});
	}
	
	/*
	 * Sets vars for the next translation.
	 *
	 * @param string $var_name Variable name
	 * @param string $value Variable value
	 */
	
	public static function trans_var() 
	{
		return new Twig_SimpleFunction('trans_var', function($var_name,$value=null,$add=false) {
			if (is_array($var_name)) {
				$GLOBALS['twig_vars']['trans_vars'] = array_merge($GLOBALS['twig_vars']['trans_vars'],$var_name);
			}
			else {
				if ($add === false) {
					$GLOBALS['twig_vars']['trans_vars'][$var_name] = $value;
				}
				else {
					$GLOBALS['twig_vars']['trans_vars'][$var_name] += $value;
				}
			}
		});
	}
	
	/*
	 * Translates a string.
	 *
	 * @param string $identifier Identifier
	 * @param array $params Custom translation parameters
	 *
	 * @return string Translated string
	 */
	
	public static function trans() 
	{
		return new Twig_SimpleFunction('trans', function($identifier,$params=null) {
		    $bundle = isset($params['bundle']) ? $params['bundle'] : $GLOBALS['twig_vars']['trans_bundle'];
            $theme = isset($params['theme']) ? $params['theme'] : (isset($GLOBALS['twig_vars']['trans_theme']) && !empty($GLOBALS['twig_vars']['trans_theme']) ? $GLOBALS['twig_vars']['trans_theme'] : 'default');
    	    $domain = isset($params['domain']) ? $params['domain'] : (isset($GLOBALS['twig_vars']['trans_domain']) && !empty($GLOBALS['twig_vars']['trans_domain']) ? $GLOBALS['twig_vars']['trans_domain'] : 'default');
			
			$translation = new Translation(['bundle' => $bundle, 'theme' => $theme, 'domain' => $domain]);
			
			$translation->setVar($GLOBALS['twig_vars']['trans_vars']);
    	    
    	    $translated_string = $translation->translate($identifier);
    	    
    	    $GLOBALS['twig_vars']['trans_vars'] = [];
    	    
    	    return $translated_string;
		});
	}
	
	/*
	 * #### À REVOIR ####
	 *
	 * Gets the active menu for navigation
	 * 
	 * @param string $route The page route
	 * @param bool $complete Complete class, or just "active"
	 *
	 * @return html Class active or nothing
	 */
	 
	public static function navActive() 
	{
		return new Twig_SimpleFunction('navActive', function($route,$complete=false) {
			if (preg_match('#^'.$route.'#',ROUTE_NAME)) {
    			return $complete === true ? ' class="active"' : ' active';
			}
			else {
    			return '';
			}
		}, array('is_safe' => array('html')));
	}
	 
	public static function subNavActive() 
	{
		return new Twig_SimpleFunction('subNavActive', function($route,$complete=false) {
			if ($route == ROUTE_NAME) {
    			return $complete === true ? ' class="active"' : ' active';
			}
			else {
    			return '';
			}
		}, array('is_safe' => array('html')));
	}
}