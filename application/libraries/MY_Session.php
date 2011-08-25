<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	Replaces the session handling, which is the one thing about CI I really don't like.  Of course,
 *	we're putting standard PHP sessions back in.
 *
 *	What would normally be the $_SESSION variable is added to your controller as 
 *	$this->session->data.  It is a reference to the super-global, so you can copy the reference
 *	around and make changes to it.  As usual, you can't put anything into the session data that
 *	can't be serialized:
 *		- recursive data structures
 *		- resources
 *
 *	CONFIG OPTIONS
 *		name:	the cookie name used on the client side
 *
 */
class MY_Session {

		
    public function __construct($args=null)
    {
    	if (isset($args['path']))
    		session_save_path($args['path']);
    	
    	if (isset($args['name']))
    		session_name($args['name']);
    		
    	session_start();
    	$this->data =& $_SESSION;
    }
    
    public function destroy() {
    	session_destroy();
    }
    
}

?>
