<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	Fmp wrapper for CodeIgniter
 *
 *	This gives you an easy way to instantiate and configure FileMaker connections when using
 *	CodeIgniter.  
 *
 *	INSTALLATION
 *
 *	Drop this file into your application/libraries directory.
 *
 *	Make sure the FileMaker PHP library is somewhere in your PHP include path.
 *
 *	Add the following statement to index.php.  This is necessary because PHP dies when the FileMaker
 *	libraries are included from inside the CodeIgniter context.  I don't know why, but it's true.
 *
 *		<pre>
 *			require_once('FileMaker.php');
 *		</pre>
 *
 *	USE
 *
 *	From inside your controller:
 *
 *	<pre>
 *		$this->load->library('fmp');
 *		$this->fmp->getLayout('my_layout_name');
 *		// etc..
 *	</pre>
 *
 *	Once you have the fmp object set up, you use it like the FileMaker object that it is.
 *
 *	CONFIGURATION
 *
 *	You can pass an associative array, or provide a configuration file that will automatically
 *	be read when you load the library.  The four understood keys are:
 *
 *	- database: the name of the database
 *	- username: the login name
 *	- password: the password
 *	- hostspec: If the database isn't on localhost, then the URL to use.  (eg: http://login.sls.com)
 *
 *	Like most other configuration files, just assign these keys to the $config object.
 *
 *	<pre>
 *		<?php
 *			// config/fmp.php
 *			$config['database'] = 'my_database';
 *			$config['username'] = 'Joe Bloe';
 *			$config['password'] = 'kakjf8eq98faH';
 *			$config['hostspec'] = 'http://fmserver.mydomain.com';
 *		?>
 *	</pre>
 *
 */
class Fmp extends FileMaker {

	public function __construct($args=null) {
		parent::__construct();

		if (isset($args['database']))
			$this->setProperty('database', $args['database']);
		
		if (isset($args['username']))
			$this->setProperty('username', $args['username']);
		
		if (isset($args['password']))
			$this->setProperty('password', $args['password']);
		
		if (isset($args['hostspec']))
			$this->setProperty('hostspec', $args['hostspec']);
	}
}
