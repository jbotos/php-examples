<?php

class Login extends Admin_Controller
{
	private $auth;

	public function __construct()
	{
		parent::__construct();

		$this->load->library('admin_auth');
		$this->auth = new admin_auth;

		$action = !empty($this->uri->segments[3]) ? $this->uri->segments[3] : '';
		if($this->auth->current() && ($action !== 'out')) {
			redirect('/admin/dashboard', 'location', 301);
		}

		$this->load->helper(['form', 'url']);
		$this->load->library('form_validation');
	}

	/** form without layout */
	public function index()
	{
		$this->template->set_layout("login"); //i'll have a special layout for login
		$this->template->build('login', $this->data);
	}

	/** process login functionality */
	public function process()
	{
		$post = $this->input->post('l', false);
		if(empty($post)) { show_404(); }

		$this->form_validation->set_rules(
			'email', 'Email', 'required|valid_email'
		);
		$this->form_validation->set_rules(
			'pwd', 'Password', 'required', ['required' => 'You must provide a %s.']
		);

		// set form data from "l" array
		$this->form_validation->set_data($post);

		$formValidation = $this->form_validation->run();
		if(!$formValidation) {
			$message = ['class' => 'warning', 'message' => 'Please enter valid form'];
			redirectWithMessage('/admin/login', $message);
		}

		// call signin method with provided email and pass
		$authStatus = $this->auth->signIn($post['email'], $post['pwd']);
		if($authStatus !== admin_auth::SUCCESS_LOGGED) {
			$message = ['class' => 'warning', 'message' => 'Credentials not found'];
			redirectWithMessage('/admin/login', $message);
		}

		$message = ['class' => 'success', 'message' => 'You logged successfully'];
		redirectWithMessage('/admin/dashboard', $message);
	}

	/** logout functionality */
	public function out()
	{
		$this->auth->signOut();
		redirect('/admin/login');
	}
}
