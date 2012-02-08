<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
	This is just a bit of exploratory work, thinking about OAuth2.  Originally, the idea was that I
	would convince the application to self-authenticate, so it could access its own cloud storage
	data without any intervention from the user. But Google doesn't support the client_credential
	or implicit authentication types.  So I've settled for just getting the callback called.

	It's not a hard system, actually.  I'll probably come back to this and flesh it out a lot.
	There's some thinking to be done about configuration, how you keep state information, and proper
	abstractions.  If you have a protocol library, it would want to accept a variety of
	authentication mechanisms, and re-authorize when appropriate.

	- The OAuth2 protocol is detailed at http://tools.ietf.org/html/draft-ietf-oauth-v2-23
	- Google OAuth documentation: http://code.google.com/apis/accounts/docs/OAuth2.html
	
	For the immediate future, my plan is to revert to using the developer key/secret mechanism to
	have the application access its own Google Cloud Storage buckets.  But I don't like the
	libraries that other people have written, so there's work to be done there.
 */
class oauth extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
		
		$this->load->helper('url');
		
		$this->logfile = fopen(APPPATH . '../tmp/events.txt', 'a');
		if(!$this->logfile)
			throw new Exception("You suck, log file!");
			
		$this->authEndpoint = "https://accounts.google.com/o/oauth2/auth";
		$this->tokenEndpoint = "https://accounts.google.com/o/oauth2/token";
		$this->redirectionEndpoint = base_url() . "oauth/callback";
		$this->client_id = '1086240007505.apps.googleusercontent.com';
	}
	
	public function getAuth() {
		
		$formData = array(
			'client_id' => $this->client_id,
			'response_type' => 'code',
			'redirect_uri' => $this->redirectionEndpoint,
			'scope' => 'https://www.googleapis.com/auth/devstorage.read_only',
			'state' => 'gooseEggs',
			'access_type' => 'online',
		);

		$queryString = http_build_query($formData);
		$url = "{$this->authEndpoint}?{$queryString}";
		fwrite($this->logfile, "redirecting user to $url\n");
		redirect($url);
	}
	
	public function callback() {
		$url = current_url();
		fwrite($this->logfile, "got a callback! at $url\n");
		fwrite($this->logfile, json_encode($this->input->get()));
		echo(file_get_contents("php://input"));
		echo("\nthat's all folks!\n");
	}
	
}
