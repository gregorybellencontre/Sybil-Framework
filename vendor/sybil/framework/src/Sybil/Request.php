<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */
 
namespace Sybil;

/**
 * The Request class parses the URL query string, builds the route, 
 * and sends its data to the right controller.
 *
 * @author Grégory Bellencontre
 */
final class Request 
{
	private $request = null;
	private $app_routes = null;
	private $bundle_routes = null;
	private $translation = null;
	private $route_data = null;
	
	/**
	 * Class constructor.
	 * Cleans and stores the URL query string.
	 *
	 * @param string $query The URL query string
	 */
	
	public function __construct($query) 
	{
		$this->route_data = new Collection([
			'route_name' => '',
			'bundle'     => '',
			'prefix'     => null,
			'controller' => '',
			'namespace'  => '',
			'method'     => '',
			'params'     => new Collection()
		]);
	
		$this->request = filter_var($query,FILTER_SANITIZE_STRING);
		$this->request = empty($this->request) ? '/' : $this->request;
	}
	
	/**
	 * Executes the request builder.
	 */
	
	public function process() {
		if ($this->translation === null) {
			$this->translation = new Translation(['bundle' => null, 'theme' => 'sybil', 'domain' => 'debug']);
    	}
	    	
		if ($this->loadAppRoutes() && !empty($this->app_routes)) {
			$this->buildRouteData();
		}
		else {
			if (ENVIRONMENT == 'production') {
				$this->setError();
			}
			else {
				App::translate("app_routes_missing",$this->translation);
				die();
			}
		}
	}
	
	/**
	 * Loads the application routes (Global routes).
	 *
	 * @return bool
	 */
	
	public function loadAppRoutes() 
	{
		$routes_path = APP.'routing.yml';
		
		if (file_exists($routes_path)) {
			$content = file_get_contents($routes_path);
			
			$this->app_routes = Yaml::parse($content);
			
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Loads the routes of a bundle.
	 *
	 * @param string $name Bundle name
	 *
	 * @return bool
	 */
	
	public function loadBundleRoutes($name) 
	{
		$routes_path = BUNDLE.$name.'/routing.yml';
		
		if (file_exists($routes_path)) {
			$content = file_get_contents($routes_path);
			
			$this->bundle_routes = Yaml::parse($content);
			
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Sets a 404 error route and loads its controller.
	 *
	 * @param bool $load_controller Loading controller or not
	 */
	
	public function setError($load_controller = true) 
	{
		header("HTTP/1.0 404 Not Found");
		
		$this->route_data = new Collection([
			'bundle'     => 'error',
			'prefix'     => null,
			'controller' => 'Error',
			'namespace' => '\\' . Config::$app_namespace . '\ErrorBundle\Controller\\',
			'method'     => 'error404',
			'params'     => new Collection()
		]);
		
		if ($load_controller === true) {
			$this->loadController();
			die();
		}
	}
	
	/**
	 * Parses a route target and extracts the bundle, controller and method name.
	 *
	 * @param string $target The route target
	 *
	 * @return array The route bundle, controller and method names
	 */
	
	public static function parseTarget($target) {
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
		
		if (isset($bundle,$controller,$method) && !empty($bundle) && !empty($controller) && !empty($method)) {
			return new Collection([
				'bundle' => $bundle,
				'controller' => $controller,
				'method' => $method
			]);
		}
		else {
			return false;
		}
	}
	
	/**
	 * Builds the route and loads its controller.
	 */
	
	public function buildRouteData() 
	{
		// Application routes
	
		while ($route = $this->app_routes->fetch()) {
			$this->route_data->route_name = $route->key();
			
			if (isset($route->prefix) && isset($route->bundle)) {
				if (preg_match('#^'.$route->prefix.'#',$this->request)) { // If the request starts with the current prefix
				    $this->route_data->prefix = $route->prefix;
				    $this->route_data->bundle = $route->bundle;
				    $this->route_data->namespace = '\\' . Config::$app_namespace . '\\' . ucfirst($this->route_data->bundle) . 'Bundle\Controller\\';
					break;
				}
			}
			elseif (isset($route->path) && isset($route->target)) {
				$route->path = !preg_match('#/$#',$route->path) ? $route->path.'/' : $route->path;
				$target = self::parseTarget($route->target);
				
				if ($target) {
					if (preg_match('#([\(\[\)\{\},+?*])#',$route->path)) { // If the path is a regex
						preg_match_all('#' . $route->path . '#',$this->request,$results);
						
						if (isset($results[0][0]) && $results[0][0] == $this->request) {
							$this->route_data->controller = $target->controller;
							$this->route_data->bundle = $target->bundle;
							$this->route_data->namespace = '\\' . Config::$app_namespace . '\\' . ucfirst($this->route_data->bundle) . 'Bundle\Controller\\';
							$this->route_data->prefix = null;
							$this->route_data->method = $target->method;
							
							unset($results[0]);
							$this->route_data->params = array_values(array_map(function($a) {  return array_pop($a); }, $results));
							break;
						}
					}
					elseif ($this->request == $route->path) {
						$this->route_data->controller = $target->controller;
						$this->route_data->bundle = $target->bundle;
						$this->route_data->namespace = '\\' . Config::$app_namespace . '\\' . ucfirst($this->route_data->bundle) . 'Bundle\Controller\\';
						$this->route_data->prefix = null;
						$this->route_data->method = $target->method;
						break;
					}
				}
				else {
					if (ENVIRONMENT == 'production') {
						$this->setError();
					}
					else {
						App::translate("bad_route_target",$this->translation);
						die();
					}
				}
			}
		}
		
		// Controller routes
		
		if ($this->route_data->prefix != null && !empty($this->route_data->bundle)) {
			if ($this->loadBundleRoutes($this->route_data->bundle)) {
				foreach($this->bundle_routes as $route_name=>$details) {
					$this->route_data->route_name = $route_name;
					
					if (isset($details->path) && isset($details->target)) {
						$details->path = !preg_match('#/$#',$details-path) ? $details->path.'/' : $details->path;
						$target = self::parseTarget($details->target);
				
						if ($target) {
							if (preg_match('#([\(\[\)\{\},+?*])#',$details->path)) { // If the path is a regex
								preg_match_all('#' . $details->path . '#',$this->request,$results);
								
								if (isset($results[0][0]) && $this->route_data->prefix.$results[0][0] == $this->request) {
									$this->route_data->controller = $target->controller;
									$this->route_data->method = $target->method;
									
									unset($results[0]);
									$this->route_data->params = array_values(array_map(function($a) {  return array_pop($a); }, $results));
									break;
								}
							}
							elseif ($this->request === $this->route_data->prefix.$details->path) {
								$this->route_data->controller = $target->controller;
								$this->route_data->method = $target->method;
								break;
							}
						}
						else {
							if (ENVIRONMENT == 'production') {
								$this->setError();
							}
							else {
								App::translate("bad_route_target",$this->translation);
								die();
							}
						}
					}
				}
			}
			else {
				if (ENVIRONMENT == 'production') {
					$this->setError();
				}
				else {
					$this->translation->setVar('bundle',$this->route_data->bundle);
					App::translate("bundle_routing_file_missing",$this->translation);
					die();
				}
			}
		}
		
		// Checks the $route_data array, before loading the controller
		foreach($this->route_data as $key=>$value) {
			if ($value === '' && $key != 'params') {
				$this->setError();
			}
		}
		
		// Loads the controller
		$this->loadController();
	}
	
	/**
	 * Loads the route controller.
	 */
	
	public function loadController() 
	{
		$controller_path = BUNDLE.$this->route_data->bundle.'/controller/'.$this->route_data->controller.'Controller.php';
		
		$db_connect = Config::$db_to_use === false ? false : true;
		
		if (file_exists($controller_path)) {
			require_once($controller_path);
			$controller = $this->route_data->namespace . $this->route_data->controller .'Controller';
			new $controller($this->route_data,$db_connect);
		}
		else {
			if (ENVIRONMENT == 'production' && $this->route_data->controller != 'Error') {
				$this->setError(false);
				
				require_once(BUNDLE.'error/controller/ErrorController.php');
				$controller = '\\' . Config::$app_namespace . '\ErrorBundle\Controller\Error\\ErrorController';
				new $controller($this->route_data,false);
			}
			else {
				$this->translation->setVar('controller',$this->route_data->controller);
				$this->translation->setVar('bundle',$this->route_data->bundle);
				App::translate("controller_missing",$this->translation);
				die();
			}
		}
	}
}