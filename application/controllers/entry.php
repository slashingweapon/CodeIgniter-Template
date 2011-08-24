<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Entry extends CI_Controller {

	public function index()
	{
		$this->load->library('template');
		$this->load->library('fmp');

		$this->hello = "Hello, World!";
		$this->title = "Awesomeness, defined.";

		$this->template->display('entry.tpl');
	}
}
