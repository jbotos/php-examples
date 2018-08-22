<?php

class Sites extends Admin_Controller
{
	/**  */
	public function __construct()
	{
		parent::__construct();

		$this->load->helper('frontend_helper');
		$this->load->model('site_databases_m');
	}

	/**  */
	public function index()
	{
		$data["sites"] = $this->site_m->get_sites();
		$this->template->build('sites/index', $data);
	}

	/**
	 * Create method
	 */
	public function create()
	{
		$pageData["site"] = [];
		$pageData['assign'] = (int)$this->input->get('assign');
		$pageData['databases'] = $this->site_databases_m->get_databases();

		$this->form_validation->set_rules($this->site_m->create_rules);

		if(crf_token()) {

			$data = $this->input->post(NULL, TRUE);

			if($this->form_validation->run() == FALSE) {
				$message = ['class' => 'danger', 'message' => 'All form fields are required.'];
				redirectWithMessage('/admin/sites/create', $message);
			}
			$data['assign'] = (int)$data['assign'];

			$id = $this->site_m->createWebsite($data);
			if($data['assign']) {
				$message = ['class' => 'success', 'message' => 'Site created and assigned to account'];
				redirectWithMessage('/admin/accounts/edit/' . $data['assign'], $message);
			}

			$message = ['class' => 'success', 'message' => 'Site created successfully'];
			redirectWithMessage('/admin/sites/edit/' . $id, $message);
		}

		$this->template->build('sites/create', $pageData);
	}

	/**
	 * @param null $id
	 */
	public function edit($id = NULL)
	{
		if(empty($id)) { redirect('/'); }

		$data['site'] = $this->site_m->selectById($id);
		if(empty($data['site'])) {
			redirect('/');
		}

		if(crf_token()) {
			$input = $this->input->post(NULL, TRUE);

			$this->form_validation->set_rules($this->site_m->edit_rules);

			if($this->form_validation->run() === FALSE) {
				$message = ['class' => 'warning', 'message' => 'Please correct the form'];
				redirectWithMessage('/admin/sites/edit/' . $id, $message);
			}

			$data = [
				'primary_business_name' => $input['primary_business_name'],
				'domain_name' => $input['domain_name'],
				'temp_domain_name' => $input['temp_domain_name'],
				'temp_domain_redirect' => $input['temp_domain_redirect'],
				'active' => $input['status']
			];
			$this->site_m->updateSite($id, $data);
			$message = ['class' => 'success', 'message' => 'Site updated successfully'];
			redirectWithMessage('/admin/sites/edit/' . $id, $message);
		}

		$this->template->build('sites/edit', $data);
	}

	/**
	 * Assign site to account
	 *
	 * @return mixed
	 */
	public function assign()
	{
		$input = $this->input->post(NULL, TRUE);
		if(empty($input['account']) || empty($input['site'])) {
			return $this->response(404, []);
		}

		$this->assignSiteToAccount($input['account'], $input['site']);
		return $this->response(200, []);
	}

	/**
	 * @return mixed
	 */
	public function update_status()
	{
		$input = $this->input->post(NULL, TRUE);
		if(empty($input['account']) || !isset($input['status'])) {
			return $this->response(404, []);
		}

		$this->site_m->updateSiteByAccountId($input['account'], ['active' => $input['status']]);
	}

	/**
	 * @param $accountId
	 * @param $siteId
	 */
	private function assignSiteToAccount($accountId, $siteId)
	{
		// remove account id for another sites
		$this->site_m->updateSiteByAccountId($accountId, ['account_id' => 0]);

		// update account_id for selected
		$this->site_m->updateSite($siteId, ['account_id' => $accountId]);
	}

	/**
	 * @param $id
	 */
	public function admin_auth($id)
	{
		if(empty($id)) { redirect('/'); }

		$data['site'] = $this->site_m->selectById($id);
		if(empty($data['site'])) {
			redirect('/');
		}

		$user = admin_auth::getInstance()->current();
		if(empty($user) || empty($user->authkey)) {
			redirect('/admin');
		}
	}

	public function delete($id, $db_name)
	{
		$this->site_m->deleteSite($id, $db_name);
		$message = ['class' => 'success', 'message' => 'Site removed successfully'];
		redirectWithMessage('/admin/sites/', $message);
	}

	public function databases() {
		$data["site_databases"] = $this->site_databases_m->get_site_databases();
		$this->template->build('site_databases/index', $data);
	}

	public function create_database() {
		$data["sites"] = $this->site_m->get_sites();
		$this->template->build('site_databases/create', $data);
	}
	
}
