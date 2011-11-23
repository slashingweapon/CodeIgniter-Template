<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once "Smarty/Smarty.class.php";

/**
 *	Provides a proper templating engine, via Smarty.  
 *
 *	INSTALLATION
 *
 *	This file (Template.php) goes into your application/libraries directory.  Just drop it in.
 *
 *	The following directory structure should have been created for you, when you obtained this
 *	project.  If not, create it now.
 *
 *	application
 *		templates	(server readable)
 *			config	(server readable)
 *	tmp
 *		compiled_templates (server read/writable)
 *		cached_templates   (server read/writable)
 *	
 *	You will need to install Smarty of course, preferably somewhere in PHP's include path.  On 
 *	MacOS X, I just drop it into /usr/lib/php/Smarty.
 *	
 *	USE
 *
 *	From inside your controller, load the 'template' library and then use it like the Smarty 
 *	object that it is:
 *
 *	<pre>
 *		$this->load->library('template', $args);  // $args are optional
 *		$this->template->assign('foo', $whatever);
 *		$this->template->display('stuff.tpl');
 *	</pre>
 *
 *	The $ci variable will be assigned as your controller, so you should have access to everything
 *	in your controller.  But you can assign as much other stuff as you like, display or fetch 
 *	templates, and generally go to town with your Smarty Badness.
 *
 *	The $url and $uri variables are also conveniently provided.  They are the current request
 *	URL and URI, respectively.
 *
 *	CONFIGURATION
 *
 *	Configuration is via an associative array, which has three possible sources.  In order of
 *	precedence:
 *	- What you pass in when you load the library
 *	- The $config array from the config/template.php file (or one of the specialized versions)
 *	- The built-in defaults
 *
 *	The available configuration parameters are in the static variable $defaultConfig.  They are:
 *	- template_dir: Where to find the templates
 *	- compile_dir: Where to put compiled files.  Must be server-writtable
 *	- cache_dir: Where to put cached templates.  Must be server-writtable
 *	- config_dir: Where to put config files.  Typically this folder is empty or doesn't even exist.
 *
 *	Sample config/template.php file:
 *
 *	$config['template_dir'] = APPPATH . 'templates/';
 *	$config['config_dir']   = APPPATH . 'templates/config';
 *	$config['compile_dir']  = APPPATH . '../tmp/compiled_templates';
 *	$config['cache_dir']    = APPPATH . '../tmp/cached_templates';
 *	$config['debugging']    = true; 		// turn on debugging
 *	$config['debug_tpl']    = 'debug.tpl';	// set debug template (included)
 *
 *	You can even create a config/<environment>/template.php file for development, staging, etc..
 *
 *	A debug template is included in this framework, which is a cooperation of the following files:
 *		templates/debug.tpl
 *		helpers/template_helper.php
 *
 *	Set the debugging and debug_tpl parameters as shown above, and have Krumo installed and working.
 *	If you do those thing, then all of your template-bound variables will be shown in the HTML 
 *	This is hugely useful during development.
 */
class Template extends Smarty {
		
    public function __construct($args=null)
    {
        parent::__construct();
        
        // make sure $args is an array
        if (!is_array($args))
        	$args = array();
        
        // take our defaults, and replace them with whatever was passed in
        $config = array_merge(self::getDefaultConfig(), $args);
        
        // plugin directory is handled a little differently than the other configs, so take care
        // of it first and then unset it.
        if (isset($config['plugins_dir'])) {
        	$this->plugins_dir = array_merge($this->plugins_dir, $config['plugins_dir']);
        	unset($config['plugins_dir']);
        }
        
		foreach ($config as $name => $value) {
			$this->{$name} = $value;
		}
	
		// if we don't set the timezone, we're toast
		$ci = get_instance();
		$tz = $ci->config->item('timezone');
		if ($tz !== false)
			date_default_timezone_set($tz);
		
		$ci->load->helper('url');
		
		$this->assign('home', site_url());
		$this->assign('uri', $ci->uri->uri_string());
		$this->assign('url', $ci->config->site_url($ci->uri->uri_string()));
    }
    
    private static function getDefaultConfig() {
    	return array (
			'template_dir'	=> APPPATH . 'templates/',
			'config_dir'	=> APPPATH . 'templates/config',
			'plugins_dir'	=> array(APPPATH . 'templates/plugins'),
			'compile_dir'	=> APPPATH . '../tmp/compiled_templates',
			'cache_dir'		=> APPPATH . '../tmp/cached_templates',
    	);
    }
}

?>
