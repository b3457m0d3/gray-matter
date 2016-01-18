<?php
class Upload {
	public $file = array();
	protected $default_chmod  = 750;
	protected $files_post     = array();
	protected $mime_types     = array();
	protected $tmp_name,$filename,$path,$root,$finfo,$max_size;
	protected $ext_cb_object,
	          $ext_cb_methods = array();
	private $callbacks = array();

	public static function factory($path, $root = false) { return new Upload($path, $root); }
	public function __construct($path, $root = false) {
		$this->root = ($root)? $root : $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;
		if (!$this->set_path($path)) throw new Exception('Upload: Can\'t create destination. '.$this->root . $this->path);
		$this->finfo = new finfo();
	}
	public function set_filename($filename){ $this->filename = $filename; }
	public function upload($filename = '') {
		if ($this->check()) $this->save();
		return $this->get_state();
	}
	public function save() {
		$this->save_file();
		return $this->get_state();
	}
	public function check() {
		$this->validate();
		$this->file['errors'] = $this->get_errors();
		$this->file['status'] = empty($this->validation_errors);
		return $this->file['status'];
	}
	public function get_state() { return $this->file; }
	public function set_allowed_mime_types($mime_types) {
		$this->mime_types		= $mime_types;
		$this->callbacks[]	= 'check_mime_type';
	}
	public function set_max_file_size($size) {
		$this->max_size	= $size;
		$this->callbacks[]	= 'check_file_size';
	}
	public function set_error($message) { $this->validation_errors[] = $message; }
	public function get_errors() { return $this->validation_errors; }
	public function callbacks($cb_object, $callback_methods) {
		if (empty($cb_object)) throw new Exception('Upload: $cb_object can\'t be empty.');
		if (!is_array($callback_methods)) throw new Exception('Upload: $callback_methods needs to be array.');
		$this->ext_cb_object	= $cb_object;
		$this->ext_cb_methods = $callback_methods;
	}
	public function file($file) { $this->set_file_array($file); }
	protected function save_file() {
		if(empty($this->filename)) $this->generate_name();
		$this->file['filename']	 = $this->filename;
		$this->file['full_path'] = $this->root.$this->path.$this->filename;
		$this->file['path']      = $this->path.$this->filename;
		$status = move_uploaded_file($this->tmp_name, $this->file['full_path']);
		if (!$status) throw new Exception('Upload: Can\'t upload file.');
		$this->file['status']	= true;
	}
	protected function get_file_size() { return filesize($this->tmp_name); }
  protected function get_file_mime() { return $this->finfo->file($this->tmp_name, FILEINFO_MIME_TYPE); }
	protected function set_file_data() {
		$file_size = $this->get_file_size();
		$this->file = array(
			'status'				    => false,
			'path'      		  	=> $this->path,
			'size_in_bytes'			=> $file_size,
			'size_in_mb'			  => $this->bytes_to_mb($file_size),
			'mime'					    => $this->get_file_mime(),
			'original_filename'	=> $this->file_post['name'],
			'tmp_name'				  => $this->file_post['tmp_name'],
			'post_data'				  => $this->file_post,
		);
	}
	protected function set_file_array($file) {
		if (!$this->check_file_array($file)) $this->set_error('Please select file.');
		$this->file_post = $file;
		$this->tmp_name  = $file['tmp_name'];
	}
	protected function check_file_array($file) {
		return isset($file['error'])
			&& !empty($file['name'])
			&& !empty($file['type'])
			&& !empty($file['tmp_name'])
			&& !empty($file['size']);
	}

	protected function set_path($path) {
		$this->path = $path . DIRECTORY_SEPARATOR;
		return $this->path_exist() ? TRUE : $this->create_path();
	}
	protected function path_exists() { return is_writable($this->root . $this->path); }
	protected function create_path() { return mkdir($this->root . $this->path, $this->default_chmod, true); }
	protected function generate_name() {
		$filename = sha1(mt_rand(1, 9999) . $this->path . uniqid()) . time();
		$this->set_filename($filename);
	}
	protected function bytes_to_mb($bytes) { return round(($bytes / 1048576), 2); }
} // end of Upload
