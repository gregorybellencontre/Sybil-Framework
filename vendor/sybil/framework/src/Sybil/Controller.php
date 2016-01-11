<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */
 
namespace Sybil; 
  
use Twig_Loader_Filesystem;
use Twig_Environment;

/**
 * The abstract controller class calls the requested action method, 
 * and provides methods for children controllers.
 *
 * @author Grégory Bellencontre
 */
abstract class Controller 
{
	private $params = null;
	protected $vars = null;
	protected $post = null;
	protected $files = null;
	protected $session = null;
	protected $server = null;
	protected $cookie = null;
	private $template = null;
	private $store = false;
	private $translation = null;
	private $trans_debug = ['bundle' => false, 'theme' => 'sybil', 'domain' => 'debug'];
	
	/*
	 * Class constructor.
	 *
	 * @param array $route_data Route parameters
	 * @param bool $db_connect Database connection activation
	 * @param bool $store_view Enables or not the storage of the view (for views inclusions)
	 */
	 
	public function __construct(Collection $route_data,$db_connect=true,$store_view=false) 
	{
        if (Config::$maintenance === true && $params->controller !== 'Unavailable') {
	        $this->redirect('maintenance',[],false);
        }
   
		// Checking application is installed
		
		if ($route_data->controller !== 'Install' && Config::$app_id == '##APP_ID##') {
        	$this->redirect('install');
    	}
   
		$this->route_data = $route_data;
		$this->store = $store_view;
		
		$controller_namespace = $this->route_data->namespace . $this->route_data->controller . 'Controller';
		
		// Storing and cleaning POST, FILES, SESSION and GET data.
			
		$this->setGlobals();
		
		// Loading the translation tool with the default controller file language.
		
		if ($this->translation === null) {
    	   $this->translation = new Translation(['bundle' => $this->route_data->bundle, 'theme' => null, 'domain' => null]);
    	   
    	   $GLOBALS['twig_vars'] = array();
    	   $GLOBALS['twig_vars']['trans_bundle'] = $this->route_data->bundle;
    	   $GLOBALS['twig_vars']['trans_vars'] = array();
    	}
		
		define('ROUTE_NAME',$this->route_data->route_name);
		define('BUNDLE_NAME',$this->route_data->bundle);
		  
		$class_methods = array_diff(get_class_methods($controller_namespace), get_class_methods(get_parent_class($this)));
		$method = $this->route_data->method . 'Action';  
		
		if (in_array($method,$class_methods)) {
			if ($db_connect === true && $route_data->controller !== 'Unavailable' && $route_data->controller !== 'Install' && Config::$dbms_to_use != '##DB_ENGINE##') {
				if (!\Sybil\ORM\Database::connect()) {
					if (ENVIRONMENT == 'production') {
						$this->redirect('unavailable');
					}
					else {
						App::translate("db_connection_failed",$this->translation,$this->trans_debug);
						die();
					}
				}
			}
			
			// Setting global vars for the view.
			
			$this->vars = new Collection();
			$this->vars->set('SITE_NAME',Config::$site_name == '##SITE_NAME##' ? FRAMEWORK_NAME : Config::$site_name);
			
			if ($this->session->has('auth_user')) {
				$this->vars->set('AUTH_USER',new Collection([
					'id' => $this->session->auth_user->id,
					'role' => $this->session->auth_user->role
				]));
			}
			
			// Model autoloading
			
			$this->autoloadModels();
			
			// Calling the requested controller method.
			
			call_user_func_array([$this,$method],$this->route_data->params->toArray());
		}
		else {
			if (ENVIRONMENT == 'production') {
				if ($this->route_data->controller !== 'Error') {
					$this->setError();
				}
				else {
					$this->redirect('unavailable');
				}
			}
			else {
				$this->translation->setVar('method',$method);
				App::translate("controller_method_missing",$this->translation,$this->trans_debug);
				die();
			}
		}
	}
	
	/*
	 * Activates model autoloading.
	 */
	
	public function autoloadModels() {
		spl_autoload_register(function($class) {
			list($model_namespace,$model_bundle,$model_directory,$model_name) = explode('\\',$class);
			
			if ($model_directory == 'Model') {
				$model_bundle = strtolower(str_replace('Bundle','',$model_bundle));
				
				$model_path = BUNDLE.$model_bundle.'/model/'.$model_name.'.php';
				
				if (file_exists($model_path)) {
					require_once($model_path);
				}
				else {
					$error_model = $model_name;
				}
			}
			else {
				$error_model = $class;
			}
			
			if (isset($error_model) && ENVIRONMENT != 'production') {
				$this->translation->setVar('model',$error_model);
				App::translate("model_file_missing",$this->translation,$this->trans_debug);
				die();
			}
		});
	}
	
	/*
	 * Creates collections using PHP global variables.
	 */
	
	public function setGlobals() 
	{
		$this->post = new Collection($_POST);
		$this->files = new Collection($_FILES);
		$this->session = new Collection($_SESSION);
		$this->server = new Collection($_SERVER);
		$this->cookie = new Collection($_COOKIE);
	}
	
	/*
	 * Sets 404 error page.
	 */
	 
	public function setError() 
	{
		$this->route_data = [
			'bundle'     => 'error',
			'prefix'     => null,
			'controller' => 'Error',
			'namespace' => '\Sybil\Controller\Error\\',
			'method'     => 'error404',
			'params'     => new Collection()
		];
		
		header("HTTP/1.0 404 Not Found");
		require_once(BUNDLE.'error/controller/ErrorController.php');
		$controller = '\Sybil\Controller\Error\\ErrorController';
		new $controller($this->route_data);
	}
	
	/*
	 * Redirects to another route.
	 *
	 * @param string $route_name Route name
	 * @param array $values Replacement values for the route
	 * @param bool $permanent Permanent redirection or not
	 */
	
	public function redirect($route_name,$values=array(),$permanent=true) 
	{
		if ($permanent === true) {
			header('Status: 301 Moved Permanently', false, 301);
		}
		else {
			header('Status: 302 Moved Temporarily', false, 302);
		}
		
		header('Location: /' . ROOT_DIRECTORY.Routing::get($route_name,$values));
		die();
	}
	
	/*
	 * Renders the view.
	 *
	 * @param string $view_name Name of the template file (without extension)
	 * @param bool $escape Escape HTML or not
	 */
	 
	public function render($view_name='index',$escape=true) 
	{
		$view_path = BUNDLE.$this->route_data->bundle . '/view/' . $view_name . '.html.twig';
		
		if (file_exists($view_path)) {
			$this->vars->extract();
			
			// Directories where Twig will search for templates.
			$template_directories = [BUNDLE.$this->route_data->bundle.'/view', APP.'theme/manager/view', APP.'theme/'.Config::$theme.'/view'];
			
			$loader = new Twig_Loader_Filesystem($template_directories);
			
			if (ENVIRONMENT == 'production') {
    			$twig = new Twig_Environment($loader, [
                    'cache'      => CACHE.'view',
                    'debug'      => false,
                    'autoescape' => $escape
                ]);
            }
			else {
    			$twig = new Twig_Environment($loader, [
    			     'cache'      => false,
    			     'debug'      => true,
                     'autoescape' => $escape
    			]);
			}
			
			TwigFunctions::initFunctions($twig); // Loading custom Twig functions
			
			if ($this->store === false) {
				echo $twig->render($view_name . '.html.twig', $this->vars->toArray());
			}
			else {
				$this->template = $twig->render($view_name . '.html.twig', $this->vars->toArray());
			}
		}
		else {
			if (ENVIRONMENT == 'production') {
				$this->setError();
			}
			else {
				$this->translate->setVar('view_path',$view_path);
				$this->translate->setVar('bundle',$this->route_data->bundle);
				App::translate("missing_view_file",$this->translate,$this->trans_debug);
				die();
			}
		}
	}
	 
	 /*
	 * Loads a template.
	 *
	 * @param string $target The route target (bundle:controller:method)
	 *
	 * @return string Template content or false
	 */
	
	public function loadTemplate($target) 
	{
		$params = Request::parseTarget($target);
		
		if ($params != false) {
			extract($params);
			$ctrl_path = BUNDLE.$bundle . '/controller/' . $controller . 'Controller.php';
			
			if (file_exists($ctrl_path)) {
				$params->bundle = $bundle;
				$params->prefix = '';
				$params->controller = $controller;
				$params->namespace = '\\' . Config::$app_namespace . '\\' . ucfirst($params->bundle) .'Bundle\Controller\\' . ucfirst($params->bundle) .'\\';
				$params->method = $method;
				$params->params = new Collection();
				
				$controller = $params->namespace.$controller.'Controller';
			
				require_once($ctrl_path);
				$ctrl = new $controller($params,false,true);
				return $ctrl->template;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/*
	 * Loads a form.
	 *
	 * @param string $name Name of the form file to load
	 * @param mixed $record ID of a record to load in the form
	 *
	 * @return mixed Instance of form, or false
	 */
	
	public function loadForm($name,$record=null) 
	{
    	$form_path = BUNDLE.$this->route_data->bundle . '/form/' . ucfirst(strtolower($name)) . 'Form.php';
    	
    	if (file_exists($form_path)) {
        	require_once($form_path);
        	$class_name = '\Sybil\Form\\' . ucfirst($this->route_data->bundle) . '\\' . ucfirst(strtolower($name)) . 'Form';
        	return new $class_name($record);
    	}
    	else {
        	return false;
    	}
	}
	
	/*
	 * Changes the language locale in SESSION.
	 *
	 * @param string $locale Locale code
	 */
	
	public function setAppLocale($locale) 
	{
    	$this->session->set('locale', filter_var($locale,FILTER_SANITIZE_STRING));
	}
	
	/*
	 * Sets the translation bundle.
	 *
	 * @param string $bundle Bundle to use
	 */
	
	public function useBundle($bundle) 
	{
        $this->translation->useBundle($bundle);	
	}
    
    /*
	 * Sets the translation theme.
	 *
	 * @param string $theme Theme to use
	 */
	
	public function useTheme($theme) 
	{
        $this->translation->useTheme($theme);
	}
	
	/*
	 * Sets the translation domain.
	 *
	 * @param string $domain Domain to use
	 */
	
	public function useDomain($domain) 
	{
    	$this->translation->useDomain($domain);
	}
	
	/**
	 * Sets var(s) for the next translation.
	 *
	 * @param mixed $key Variable name or Array of multiple variables
	 * @param string $value Value for the given variable name
	 */
	
	public function setTranslationVar($var_name,$value=null) 
	{
		$this->translation = $this->translation === null ? new Translation() : $this->translation;
    	
    	$this->translation->setVar($var_name,$value);
	}
	
	/*
	 * Translates a string.
	 *
	 * @param string $string Identifier
	 * @param string $params Translation parameters
	 *
	 * @return string Translated string
	 */
	
	public function translate($identifier,$params=null) 
	{
	    $this->translation = $this->translation === null ? new Translation() : $this->translation;
    	
    	return $this->translation->translate($identifier,$params);
	}
	
	/*
	 * Cleans a variable content (string or array).
	 *
	 * @param mixed String or array to clean
	 *
	 * @return mixed Cleaned element
	 */
	 
	public function clean($element) 
	{
		if (is_array($element)) {
			$clean_recursive = function(&$item, $key) {
			    $item = is_string($item) ? htmlspecialchars($item) : $item;
			};
	
			array_walk_recursive($element, $clean_recursive);
		}
		else {
			$element = htmlspecialchars($element);
		}
		
		unset($clean_recursive);
		return $element;
	}
	
	/*
	 * Checks authentification state.
	 *
	 * @param bool $expected Expected state of authentification
	 * @param string $route Route name for redirection
	 * @param array $params Values to replace in the URL
	 */
	 
	 public function checkAuth($expected,$route='dashboard_login',$params=array()) 
	 {
    	 if (!empty($this->session->auth_user->id)) {
        	 if ($expected === false) {
            	 $this->redirect($route,$params,false);
        	 }
    	 }
    	 else {
        	 if ($expected === true) {
            	 $this->redirect($route,$params,false);
        	 }
    	 }
	 }
	 
	 /*
	 * Checks user role before granting access to a page.
	 *
	 * @param mixed $roles Authorized role(s)
	 * @param string $user_id Authentified user ID
	 */
	 
	 public function restrictedArea($roles,$user_id=0) 
	 {
    	 $roles = is_array($roles) ? $roles : [$roles];
    	 $roles[] = 'superadmin';
    	 
    	 if (!in_array($this->session->auth_user->role,$roles)) {
    	     if (!empty($this->session->auth_user->id) && $this->session->auth_user->id == $user_id) {
        	     return true;
        	 }
        	 else {
            	 $this->redirect('dashboard_home',$params,false);
        	 }
    	 }
	 }
}