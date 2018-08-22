<?php

/**
 * Class Geo
 */
class Geo extends Api_Controller
{
	public function __construct()
	{
		parent::__construct();

		// allow only post actions
		$checkApi = empty($this->input->post('api'));
		if($checkApi) { show_404(); }
	}

	/**
	 * @return mixed
	 */
	public function states()
	{
		$this->load->model('state_m');
		$rows = $this->state_m->selectAll();

		return $this->response(200, ['list' => $rows]);
	}

	/**
	 * @param int $stateId
	 * @return mixed
	 */
	public function cities($stateId = 0)
	{
		$this->load->model('city_m');
		if(empty($stateId)) {
			$rows = $this->city_m->selectAll();
		} else {
			$rows = $this->city_m->selectByIdState($stateId);
		}

		return $this->response(200, ['list' => $rows]);
	}
}
