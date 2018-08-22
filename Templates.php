<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Templates extends Account_Controller
{
	/** @var array  */
	protected $templateList = [];

	protected $templateTypes = [
		'columns'	=> [],
		'prebuild'	=> [],
		'elements'	=> []
	];

	/** @var string  */
	protected $templatesPath = null;

	/** @contructor */
	public function __construct()
	{
		parent::__construct();

		$this->loadTemplateFileList();
	}

	/**
	 * @param string $type
	 */
	public function getTemplatesByType($type = 'columns')
	{
		if(!isset($this->templateTypes[$type]) && ($type !== 'all')) {
			show_404();
		}

		header("Content-Type: application/json");
		if($type == 'all') {
			die(json_encode($this->templateList));
		}
		die(json_encode($this->templateList[$type]));
	}

	/**
	 *  Load all templates
	 */
	public function loadTemplateFileList()
	{

		$this->templatePath =  FCPATH . '/builder/templates/**/*.html';
		//$this->themeTemplatePath =  FCPATH . '/themes/' . $siteTheme . '/templates/**/*.html';

		if(is_dir($this->templatesPath)) {
			return;
		}

		foreach(glob($this->templatePath) as $templateFile) {
			$templateData   = explode('/', $templateFile);
			$templateName = str_replace('.html', '', end($templateData));
			// getting type by sub folder name

			if(!isset($templateData[count($templateData)-2])) {
				continue;
			}

			$templateType   = $templateData[count($templateData)-2];

			$this->templateList[$templateType][] = [
				'name'    => $templateName,
				'title'   => str_replace('_', ' ', $templateName),
				'type'    => $templateType,
				//'description' => 'While not always necessary, sometimes you need to put your DOM in a box. For those situations, try the panel component.',
				'path'    => $templateType . '/' . $templateName . '.html',
				'image'    => '/builder/img/page-builder/preview/' .$templateType . '/' . $templateName . '.jpg',
			];
		}
	}

	public function getSingleElements()
	{
		header("Content-Type: application/json");
		die(file_Get_contents(FCPATH . 'builder/templates/elements.json'));
	}
}
