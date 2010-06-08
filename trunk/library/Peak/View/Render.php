<?php

/**
 * Peak_View_Render Engine base
 * 
 * @author  Francois Lajoie
 * @version 20100608
 */
abstract class Peak_View_Render
{
    
    protected $_scripts_file;          //controller action script view path used 
    protected $_scripts_path;          //controller action script view file name used
    
    protected $_use_cache = false;     //use scripts view cache, false by default
    protected $_cache_expire;          //script cache expiration time
    protected $_cache_path;            //scripts view cache path. generate by enableCache()
    protected $_cache_id;              //current script view md5 key. generate by preOutput() 
    
    /**
     * Point to Peak_View __get method
     *
     * @param  string $name represent view var name
     * @return misc
     */
    public function __get($name)
    {
        return Peak_Registry::obj()->view->$name;
    }
    
    /**
     * Silent call to unknow method or
     * Throw trigger error when DEV_MODE is activated 
     * 
     * @param string $method
     * @param array  $args
     */
    public function  __call($method, $args)
    {
        if((defined('DEV_MODE')) && (DEV_MODE)) {
            trigger_error('DEV_MODE: View Render method '.$method.'() doesn\'t exists');
        }
    }
    
    /**
     * Return view helpers object from Peak_View::helper()
     *
     * @param  string $name
     * @return object
     */
    public function helper($name = null)
    {
        return Peak_Registry::obj()->view->helper($name);
    }
    
    /**
     * Return public root url of your application
     *
     * @param  string $path Add custom paths/files to the end
     * @return string
     */
    public function baseUrl($path = null)
    {
        return ROOT_URL.'/'.$path;
    }
    
    
    /**
     * Enable output caching. 
     * Avoid using in controllers actions that depends on $_GET, $_POST or any dynamic value for setting the view
     *
     * @param integer $time set cache expiration time(in seconds)
     */
    public function enableCache($time)
    {
        if(is_integer($time)) {
            $this->_use_cache = true;
            $this->_cache_expire = $time;
            $this->_cache_path = Peak_Core::getPath('theme_cache');
        }
    }
    
    /**
     * Desactivate output cache
     */
    public function disableCache()
    {
        $this->_use_cache = false;
    }
    
    /**
     * Call child output method and cache it if $_use_cache is true;
     *
     * @param misc $data
     */
    protected function preOutput($data)
    {
        if(!$this->_use_cache) $this->output($data);
        else {         
            //generate script view cache id
            $this->genCacheId();
            
            //use cache instead outputing and evaluating view script
            if($this->isCached()) {
                include($this->getCacheFile());
            }
            //cache and output current view script
            else {
                ob_start();
                $this->output($data);
                //if(is_writable($cache_file)) { //fail if file cache doesn't already exists
                    file_put_contents($this->getCacheFile(),ob_get_contents());
                //}
                ob_get_flush();
            }           
        }        
    }
    
    /**
     * Check if current view script file is cached/expired
     *
     * @return bool
     */
    public function isCached()
    {
        if(!$this->_use_cache) return false;   
        
        //when checking isCached in controller action. $_scripts_file, $_scripts_path, $_cache_id are not set yet
        if(!isset($this->_cache_id)) {            
            $app = Peak_Application::getInstance();            
            $this->genCacheId($app->controller->path, $app->controller->file);
        }
        
        $filepath = $this->getCacheFile();
            
        if(file_exists($this->getCacheFile())) {
            $file_date = filemtime($this->getCacheFile());
            $now = time();
            $delay = $now - $file_date;
            return ($delay >= $this->_cache_expire) ? false : true;
        }
        else return false;
    }
    
    /**
     * Generate cache id from script view and path
     *
     * @param string $path
     * @param string $file
     */
    protected function genCacheId($path = null,$file = null)
    {
        //use current $this->_script_file and _script_path if no path/file scpecified
        if(!isset($path))  $key = $this->_scripts_path.$this->_scripts_file;
        else $key = $this->_cache_id = $path.$file;

        $this->_cache_id = hash('md5', $key);
    }
    
    /**
     * Get current cached view script filepath
     *
     * @return string
     */
    protected function getCacheFile()
    {
        return $this->_cache_path.'/'.$this->_cache_id.'.php';
    }
        
}