<?php


class Admin_auth
{
	/** @var Auth|bool */
	private static $instance = false;

	/** @var  array */
	private $current;

	/** @var object  */
	private $ci;

	/** @var object  */
	private $model;


	// errors consts
	const ERROR_EMPTY_USER = -1;

	const ERROR_WRONG_PWD = -2;

	const ERROR_WRONG_SITE_OWNER = -3;

	const SUCCESS_LOGGED = 1;

	/**  */
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('admin_user_m', 'users');
		$this->model = $this->ci->users->workWithCoreDb();
	}

	/**
	 * Check if current user logged and he's site admin
	 *
	 * @return bool
	 */
	public static function isLogged()
	{
		$instance = self::getInstance();

		$user = $instance->current();
		if(empty($user)) {
			return false;
		}

		// check if account re-assign to new site or delete acc from site
		return true;
	}

	/**
	 * @return Auth|bool
	 */
	public static function getInstance()
	{
		if(self::$instance) {
			return self::$instance;
		}

		self::$instance = new self;
		return self::$instance;
	}

	/**
	 * @return bool|Object
	 */
	public function current()
	{
		$data = $this->ci->session->userdata('admin_user');
		if(empty($data['data'])) {
			return false;
		}
		return $data['data'];
	}

	/**
	 * @param $email
	 * @param $password
	 * @return int
	 */
	public function signIn($email, $password)
	{

		$user = $this->userByEmail($email);
		if(empty($user)) {
			return self::ERROR_EMPTY_USER;
		}

		if(!password_verify($password, $user->password)) {
			return self::ERROR_WRONG_PWD;
		}

		// remove password hash from object stored in session
		unset($user->password);
		$this->current = $this->ci->session->set_userdata('admin_user', ['data' => $user, 'logged' => time()]);

		return self::SUCCESS_LOGGED;
	}

	/**
	 * @param $email
	 * @return mixed
	 */
	protected function userByEmail($email)
	{
		return $this->model->selectByEmail($email);
	}

	/**
	 * @return bool|Object
	 */
	public function signOut()
	{
		// updates
		$this->ci->session->unset_userdata('admin_user');
		return !$this->current();
	}

	/**
	 * @param $controller
	 * @param $rules
	 * @param bool $resultCallback
	 * @return bool
	 */
	public function checkPermissions($controller, $rules, $resultCallback = false)
	{
		$result = true;
		$user = $this->current();
		if(!$user) {
			redirect('/admin/login');
		}

		$currentAction = (
			!empty($controller->uri->segments[3]) && ($controller->uri->segments[1] === 'admin')
				? $controller->uri->segments[3]
				: 'index'
		);

		if(!$currentAction) { return true; }

		foreach($rules as $action => $perm) {
			if($currentAction !== $action) {
				continue;
			}

			$result = self::comparePermissions($user->permissions, $perm);
			if(!$result){
				break;
			}
		}

		// default
		if(empty($resultCallback)) {
			$resultCallback = function($result, $controller){
				if(!$result) {
					$controller->errortype->noPermissions();
				}
			};
		}

		return $resultCallback($result, $controller);
	}

	/**
	 * @param $currentPermissions
	 * @param $perm
	 * @return bool
	 */
	public static function comparePermissions($currentPermissions, $perm)
	{
		$result = true;
		// parse permissions
		$matchedPermission = null;

		// if permissions not valid just keep
		if(!preg_match("/^(\>\=|\=\<|\=|\>|\<)([0-9]*)$/iU", $perm, $matchedPermission)) {
			return true;
		}

		list($permissionString, $operator, $index) = $matchedPermission;

		switch($operator) {
			case ">=":
				$result = (intval($currentPermissions) >= intval($index));
				break;
			case "=<":
				$result = (intval($currentPermissions) <= intval($index));
				break;
			case ">":
				$result = (intval($currentPermissions) > intval($index));
				break;
			case "<":
				$result = (intval($currentPermissions) < intval($index));
				break;
			case "=":
				$result = ((int)$currentPermissions === (int)$index);
				break;
		}

		return $result;
	}
}
