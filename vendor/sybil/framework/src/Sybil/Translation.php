<?php
/**
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;

/**
 * The Translation class manages languages files
 * and returns the correct translation.
 *
 * @author Grégory Bellencontre
 */
final class Translation 
{
    private $locale = null;
    private $file_path = null;
    private $file_content = null;
    private $vars = [];
    private $params = [
    	'bundle' => false,
    	'theme' => 'default',
    	'domain' => 'default'
    ];
    
    /**
	 * Class constructor.
	 * Initializes translation parameters.
	 */
    
    public function __construct(array $params = []) 
    {
        $this->locale = LOCALE;
        $this->params = [
	    	'bundle' => isset($params['bundle']) ? $params['bundle'] : $this->params['bundle'],
	    	'theme' => isset($params['theme']) ? $params['theme'] : $this->params['theme'],
	    	'domain' => isset($params['domain']) ? $params['domain'] : $this->params['domain']
	    ];
    }
    
    /**
	 * Opens a translation file, and stores its content.
	 *
	 * @param string $file_path The path of the file to open
	 */
    
    private function openFile($file_path,$stop=false) 
    {
        if (file_exists($file_path)) {
            $this->file_content = Yaml::parse(file_get_contents($file_path));
            
            if (!empty($this->file_content)) {
                $this->file_path = $file_path;
            }
            
            return true;
        }
        else {
        	 if (ENVIRONMENT == 'production') {
                $this->file_content = '';
                
                return true;
            }
            else {
	        	if ($stop == false) {
		        	$this->setVar('file_path',$file_path);
		            App::translate("translation_file_missing",$this,['bundle' => false, 'theme' => 'sybil', 'domain' => 'debug'],true);
		            die();
	            }
	            else {
	            	App::debug('[TRANSLATION_ERROR: File "' . $file_path . '" is missing.]');
		            die();
	            }
            }
        }
    }
    
    /**
	 * Defines the bundle.
	 *
	 * @param string $bundle The bundle to use
	 */
	
	public function useBundle($bundle) 
	{
        $this->params['bundle'] = $bundle;
        $this->params['theme'] = null;
	}
    
    /**
	 * Defines the theme.
	 *
	 * @param string $theme The theme to use
	 */
	
	public function useTheme($theme) 
	{
        $this->params['theme'] = $theme;
        $this->params['bundle'] = false;
	}
	
	/**
	 * Defines the domain.
	 *
	 * @param string $domain The domain to use
	 */
	
	public function useDomain($domain) 
	{
    	$this->params['domain'] = $domain;
	}
	
	/**
	 * Sets var(s) for the next translation.
	 *
	 * @param mixed $key Variable name or Array of multiple variables
	 * @param string $value Value for the given variable name
	 */
	
	public function setVar($var_name,$value=null) {
		if (is_array($var_name)) {
			$this->vars += $var_name;
		}
		else {
			$this->vars[$var_name] = $value;
		}
	}
	
	/**
	 * Remove all variables
	 */
	
	public function clearVars() {
		$this->vars = [];
		
		return true;
	}
    
    /**
	 * Translates a string.
	 *
	 * @param string $identifier Identifier
	 * @param string $params The translation parameters
	 *
	 * @return string Translated string
	 */
    
    public function translate($identifier,$params=null,$stop=false) 
    {
    	// Initialize parameters
    	
    	if ($params === null) {
	    	$params = $this->params;
    	}
    	else {
	    	$params['bundle'] = isset($params['bundle']) ? $params['bundle'] : $this->params['bundle'];
	    	$params['theme'] = isset($params['theme']) ? $params['theme'] : $this->params['theme'];
	    	$params['domain'] = isset($params['domain']) ? $params['domain'] : $this->params['domain'];
    	}
    	
    	// Build the translation file path
    
	    if ($params['bundle'] == null) {
	    	if ($params['theme'] === 'sybil') {
		    	$file_path = CORE.'lang/'.$this->locale.'/'.$params['domain'].'.yml';
	    	}
	    	else {
            	$file_path = THEME.$params['theme'].'/lang/'.$this->locale.'/'.$params['domain'].'.yml';
            }
        }
        else {
            $file_path = BUNDLE.$params['bundle'].'/lang/'.$this->locale.'/'.$params['domain'].'.yml';
        }
        
        // Open file
        
        if ($this->file_path != $file_path || empty($this->file_content)) {
            $this->openFile($file_path,$stop);
        }
        
        // Get translated string
        
        $line = isset($this->file_content[$identifier]) ? $this->file_content[$identifier] : '';
        
        // Variables and pluralization management
        
        if (!empty($line)) {
	        foreach($this->vars as $var_name=>$value) {
		        if (is_array($value)) {
			        foreach($value as $key=>$subvalue) {
				        $line = preg_match('#(%'.$var_name.'.'.$key.'%)#',$line) ? str_replace('%'.$var_name.'.'.$key.'%',$subvalue,$line) : $line;
			        }
		        }
		        else {
			        $line = preg_match('#(%'.$var_name.'%)#',$line) ? str_replace('%'.$var_name.'%',$value,$line) : $line;
		        }
	        }
	        
	        preg_match_all('#\(([a-zA-Z0-9_]+)\|([a-zA-Z0-9_]+),([0-9]+)\)#',$line,$result);
	        
	        if (isset($result[0]) && !empty($result[0])) {
		        $max = count($result[0]) - 1;
		        
		        for($i=0;$i<=$max;$i++) {
		        	$value = $result[3][$i]*1 > 1 ? $result[2][$i] : $result[1][$i];
		        
			        $line = str_replace($result[0][$i],$value,$line);
		        }
	        }
	        
	        if (ENVIRONMENT == 'production') {
	        	$line = preg_replace('#\(([a-zA-Z0-9_]+)\|([a-zA-Z0-9_]+),%([a-zA-Z0-9.-_]+)%\)#','[##]',$line);
	        	$line = preg_replace('#%([a-z.-_]+)%#','[##]',$line);
	        }
	        
	        $this->clearVars();
	        
	        return $line;
        }
        else {
	        if (ENVIRONMENT == 'production') {
                $line = '';
            }
            else {
            	if ($stop == false) {
                	$this->setVar('identifier',$identifier);
                	$parameters = ['bundle' => false, 'theme' => 'sybil', 'domain' => 'debug'];
                    App::translate("translation_missing",$this,$parameters,true);
                }
                else {
                    echo '[Missing translation]';
                }
            }
        }
    }
    
}