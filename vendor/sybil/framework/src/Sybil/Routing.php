<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

/**
 * The Routing class manages application routes.
 *
 * @author Grégory Bellencontre
 */ 
final class Routing 
{	
	/*
	 * Generates a file cache with every routes
	 * of the application.
	 *
	 * @return bool
	 */
	
	public static function cache() 
	{
		$app_routes_path = APP.'routing.yml';
		
		if (file_exists($app_routes_path)) {
			$root_routes = Yaml::parse(file_get_contents($app_routes_path));
			$cache_routes = '';
			
			if ($root_routes !== '' && $root_routes !== false) {
				foreach($root_routes as $route_name=>$route_details) {
					if (isset($route_details->prefix) && file_exists(BUNDLE.$route_details->bundle.'/routing.yml')) {
						$ctrl_routes = Yaml::parse(file_get_contents(BUNDLE.$route_details->bundle.'/routing.yml'));
						
						if ($ctrl_routes !== '' && $ctrl_routes !== false) {
							foreach($ctrl_routes as $ctrl_route_name=>$ctrl_route_details) {
								if (isset($ctrl_route_details->path)) {
									$cache_routes.= $ctrl_route_name . ":\n    " . $route_details->prefix . $ctrl_route_details->path . "\n\n";
								}
							}
						}
					}
					else {
						if (isset($route_details->path)) {
							$cache_routes.= $route_name . ":\n    " . $route_details->path . "\n\n";
						}
					}
				}
				
				$cache_routes = trim($cache_routes,"\n\n");
			}
			
			return file_put_contents(APP.'cache/routes.yml', $cache_routes);
		}
		else {
			if (ENVIRONMENT == 'production') {
				$this->setError();
			}
			else {
				$translation = new Translation(['bundle' => null, 'theme' => 'sybil', 'domain' => 'debug']);
				App::translate("app_routes_missing",$translation);
				die();
			}
		}
	}
	
	/*
	 * Returns the path of a route name.
	 *
	 * @param string $name Name of the route
	 * @param array $values Values for variables replacement
	 *
	 * @return string $route_path Route path
	 */
	
	public static function get($name,$values=array()) 
	{
		$file_path = APP.'cache/routes.yml';
	
		if (!file_exists($file_path)) {
			Routing::cache(); // Generates a cache file if missing.
		}
	
		if (file_exists($file_path)) {
			$routes = Yaml::parse(file_get_contents($file_path));
			
			$last_param_id = 0;
			$final_route = '/';
			
			foreach($routes as $route_name=>$route_path) {
				if ($route_name == $name) {
					if (preg_match('#([\(\[\)\{\},+?*])#',$route_path)) {
						$params = explode('/',trim($route_path,'/'));
						
						foreach($params as $key=>$param) {
							if (preg_match('#([\(\[\)\{\},+?*])#',$param)) {
								if (isset($values[$last_param_id]) && !empty($values[$last_param_id])) {
									$final_route.= $values[$last_param_id] . '/';
									$last_param_id++;
								}
							}
							else {
								$final_route.= $param . '/';
							}
						}
						
						return substr($final_route,1);
					}
					else {
						return substr($route_path,1);
					}
				}
			}
		}
		else {
			if (ENVIRONMENT == 'production') {
				$this->setError();
			}
			else {
				$translation = new Translation(['bundle' => null, 'theme' => 'sybil', 'domain' => 'debug']);
				App::translate("routes_cache_missing",$translation);
				die();
			}
		}
	}
	
}