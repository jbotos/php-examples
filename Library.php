<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Page
 */
class Library extends Account_Controller
{
	public $folder = 'public/media';

	/** page contructor */
	public function __construct()
	{
		parent::__construct();

		$this->load->model('Page_library_model', 'pageLibrary');
	}

	public function getImages()
	{
		$this->pageLibrary->getImagesJsonString();
	}

	public function uploadImage()
	{
		$post = $this->input->post();
		//$domain = str_replace('.', '-', get_the_domain());
		$domain = get_the_domain();

		$folder = ROOTPATH . '/' . $this->folder . '/' . $domain;
		if(!is_dir($folder)) {
			@mkdir($folder, 0777, true);
		}

		$uploaderConfig = [
			'upload_path'   => $folder,
			'allowed_types' => 'gif|jpg|png|jpeg',
			'max_size'      => '5000',
			'file_name'     => generateFileName()
		];

		$this->load->library('upload', $uploaderConfig);
		$this->upload->initialize($uploaderConfig);

		if (!$this->upload->do_upload('file')) {
			$error = array('error' => $this->upload->display_errors());
			show_error($error['error']);
		}

		$uploadData = $this->upload->data();
		$this->pageLibrary->create([
			'path' => '/media/' . $domain . '/' . $uploadData['file_name'],
		]);

		if(!empty($post['image_width']) && !empty($post['image_height'])) {
			if(
				(intval($post['image_width']) > intval($uploadData['image_width'])) &&
				(intval($post['image_height']) > intval($uploadData['image_height']))
			) {
				header("Content-Type: application/json");
				die(json_encode([
					'error' => 'Warning! Image size less then need, quality can be broken. Normal size:'.$post['image_width'].'x'.$post['image_height'].'px',
					'error_code' => 5
				]));
			}
		}

		header("Content-Type: application/json");
		$this->pageLibrary->getImagesJsonString();
	}

	/**
	 *  Save Cropped functionality from base64 data(ngImgCrop)
	 */
	public function saveCroppedImage()
	{
		$request = angular_request();
		$domain = get_the_domain();

		//var_dump($request->image); exit;

		if($request->image->cropped) {
			$uri = 'media/'.$domain.'/'.generateFileName().'.'.$request->image->data->ext;
			saveImageFromHash($request->image->cropped, FCPATH.'/'.$uri);
			//saveImageFromHash($request->image->cropped, $uri);

			header("Content-Type: application/json");
			$request->image->path = '/'.$uri;
			die(json_encode(['image' => '/'.$uri, 'data' => $request]));
		}
	}

	/**
	 * AutoCrop Image by width and height
	 */
	public function autoCropImage()
	{
		$request = angular_request();
		if(!empty($request->size)) {
			$domain = get_the_domain();

			list($width, $height, $type, $attr) = getimagesize(FCPATH . $request->imagePath);

			$ext    = image_type_to_extension($type);
			$name   = '/public/media/' . $domain . '/' . uniqid() .'.'.$ext;

			$this->load->library('image_lib', [
				'image_library' => 'gd2',
				'source_image'  => FCPATH . $request->imagePath,
				'new_image'     => FCPATH. $name,
				'x_axis'        => ($width / 2) - ($request->size->width / 2),
				'y_axis'        => ($height / 2) - ($request->size->height / 2),
				'maintain_ratio'=> false,
				'width'  => $request->size->width,
				'height' => $request->size->height
			]);

			if($this->image_lib->crop()) {
				die(json_encode(['path' => $name]));
			} else {
				echo $this->image_lib->display_errors();
			}
		}
	}

	/**
	 * remove element functionality
	 */
	public function removeOldImage()
	{
		$request = angular_request();
		$filePath = FCPATH . $request->url;
		if(!empty($request->url) && file_exists($filePath)) {
			@unlink($filePath);
		}
	}
}
