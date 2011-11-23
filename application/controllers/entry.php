<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Entry extends CI_Controller {

	function __construct() {
		parent::__construct();
		// This is needed by both our functions
		$this->load->library('session');
	}
	
	public function index() {
		$this->load->library('template');
		$this->load->library('fmp');		

		if (empty($this->session->data['counter']))
			$this->session->data['counter'] = 0;
		$this->session->data['counter']++;

		$this->template->assign('hello', "Hello, World!");
		$this->template->assign('title', "Awesomeness, defined.");
		$this->template->assign('counter', $this->session->data['counter']);
		
		$this->template->display('entry.tpl');
	}
	
	public function reset() {
		$this->load->helper('url');
		unset($this->session->data['counter']);
		redirect('/');
	}
}
