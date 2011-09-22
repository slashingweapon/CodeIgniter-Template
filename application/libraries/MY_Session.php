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
 *	I have also reimplemented most of the CI session methods, so you can use this library with 
 *	existing code.  However, I don't currently have support for flash data.
 *
 *	EXAMPLE
 *
 *		$this->session->data['username'] = 'mike';
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
    	
    	if (isset($args['lifetime']))
    		ini_set("session.gc_maxlifetime", $args['lifetime']);
    		
		session_start();
		$this->data =& $_SESSION;
    	
    	// CI normally puts these things into the session, so existing code might be expecting it.
    	// TODO: validate ip_address and user_agent for re-used sessions, and throw an exception if
    	// they don't match.
    	$this->data['session_id'] = session_id();
    	if (isset($_SERVER['REMOTE_ADDR']))
	    	$this->data['ip_address'] = $_SERVER['REMOTE_ADDR'];
	    if (isset($_SERVER['HTTP_USER_AGENT']))
	    	$this->data['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'], 0, 120);
    	$this->data['last_activity'] = time();
    }
    
    /**
     *	Reimplement the CI Session::userdata() method.
     *
     *	@param string $item	The name of an item in the session.
     *	@return mixed The session data, or FALSE if $item wasn't set.
     */
    public function userdata($item) {
    	$retval = false;
    	
    	if (isset($this->data[$item]))
    		$retval = $this->data[$item];
    		
		return $retval;
    }
    
    /**
     *	Reimplement the CI CI Session::set_userdata() method.
     *
     *	If $array_or_key is an array, the keys and values from the array are added to the session
     *	data.  
     *	
     *	If $array_or_key is a scalar value then $value is saves as ->data[$array_or_key] = $value;
     *
     *	@param array|scalar	$array_or_key	The array to add, or the key to use
     *	@param mixed $value  If provided, the value to be saved with the key $array
     */
    public function set_userdata($array_or_key, $value=null) {
    	if (is_array($array_or_key))
    		$this->data = array_merge($this->data, $array_or_key);
    	else
    		$this->data[$array_or_key] = $value;
    }
    
    /**
     *	Reimplement the CI Session::unset_userdata() method.
     *
     *	If $array_or_key is an array, then all of the keys in it are removed from the session data.
     *
     *	If $array_or_key is a scalar, then that key is removed from the session data.
     *
     *	@param array|string|int $array_or_key The key or keys to remove
     */
    public function unset_userdata($array_or_key) {
    
    	if (is_array($array_or_key)) {
    		foreach ($array_or_key as $key => $value)
    			unset($this->data[$key]);
    	} else
    		unset($this->data[$array_or_key]);
    }
    
    /**
     *	Reimplementation of the CI Session::all_userdata() method.
     *
     *	Returns the entire session associative array.
     *
     *	@return	array	An associative array.
     */
    public function all_userdata() {
    	return $this->data;
    }
    
    /**
     *	Destroy the session.
     *
     *	After you call this, the session ID is invalid an no session variables are available.
     */
    public function destroy() {
    	session_destroy();
    	unset($this->data);
    }
    
    /**
     *	Reimplement the CI method Session::sess_destroy()
     *
     *	All we do is call $this->destroy();
     */
    public function sess_destroy() {
    	$this->destroy();
    }
}

?>
