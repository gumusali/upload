<?php
	class Upload {
		// construction method
		public function __construct($upload_dir = null) {
			$this->root 	= rtrim($_SERVER['DOCUMENT_ROOT'], '/');
			$this->dir  	= ($upload_dir != null) ? $this->root . '/' . trim($upload_dir, '/') . '/' : $this->root . '/storage/uploads/';
			$this->multiple = false;
			$this->errors   = [];
			$this->files    = null;
			$this->uploaded = 0;
			$this->return   = ['status' => null, 'uploaded' => 0, 'names' => [], 'errors' => []];
		}

		/**
		*	get file input name and if multiple attribute is set
		*
		*	@param string input_name
		*	@param boolean multiple : false
		*	@return $this 
		*/
		public function input($input_name = null, $multiple = false) {
			// initialize
			self::__construct();

			// return false if input name is missing
			if($input_name == null)
				return false;

			// $this->files
			$this->files = $_FILES[$input_name];

			// set multiple upload
			if($multiple) {
				//
				$this->input 	= true;
				$this->multiple = true;
				$this->total 	= count((array) $this->files['name']);

				// variables
				$this->name 	 = [];
				$this->tmp_name  = [];
				$this->type 	 = [];
				$this->error 	 = [];
				$this->size 	 = [];
				$this->extension = [];
				$this->accepted  = [];

				// get file info
				for($i = 0; $i < $this->total; $i++) {
					$this->name[] 	   = $this->files['name'][$i];
					$this->tmp_name[]  = $this->files['tmp_name'][$i];
					$this->type[] 	   = $this->files['type'][$i];
					$this->error[] 	   = $this->files['error'][$i];
					$this->size[]      = $this->files['size'][$i];
					$explode 		   = explode(".", $this->files['name'][$i]);
					$this->extension[] = mb_strtolower(end($explode));
					$this->accepted[]  = 1;
				}
			} else {
				$this->input       = true;
				$this->name 	   = $this->files['name'];
				$this->tmp_name    = $this->files['tmp_name'];
				$this->type 	   = $this->files['type'];
				$this->error 	   = $this->files['error'];
				$this->size        = $this->files['size'];
				$explode 		   = explode(".", $this->name);
				$this->extension   = mb_strtolower(end($explode));
				$this->accepted    = 1;
			}

			return $this;
		}

		/**
		*	Check file extensions, if not accepted set accepted value to 0
		*
		*	@param string file_type
		*	@return $this
		*/
		public function type() {
			// get arguments
			$args = func_get_args();

			// 
			if($this->multiple) {
				for($i = 0; $i < $this->total; $i++) {
					// check extension
					if(!in_array($this->extension[$i], $args)) {
						$this->accepted[$i] = 0;
						$this->errors[] 	= "Unacceptable extension : ". $this->extension[$i];
					}
				}
			} else {
				// check extension
				if(!in_array($this->extension, $args)) {
					$this->accepted = 0;
					$this->errors[] = "Unacceptable extension : ". $this->extension;
				}
			}

			return $this;
		}

		/**
		* 	check file size
		*	
		* 	@param int max_file_size : 2097152
		*	@return $this
		*/
		public function size($max_file_size = 2097152) {
			if($this->multiple) {
				for($i = 0; $i < $this->total; $i++) {
					if($this->size[$i] > $max_file_size) {
						$this->accepted[$i] = 0;
						$this->errors[]     = "File oversized (limit = {$max_file_size}): ". $this->name[$i];
					}
				}
			} else {
				if($this->size > $max_file_size) {
					$this->accepted = 0;
					$this->errors[]     = "File oversized (limit = {$max_file_size}): ". $this->name;
				}
			}

			return $this;
		}

		/**
		*	set file names
		* 
		*	@param string name : null
		*	@return $this
		*/
		public function name($name = null) {
			// if null return $this
			if($name == null)
				return $this;

			// multiple
			if($this->multiple) {
				for($i = 0; $i < $this->total; $i++) {
					$name_prev = mb_strtolower(str_replace("." . $this->extension[$i], "", $this->name[$i]));		
					$replace   = str_ireplace(['{id}', '{name}', '{time}'], [$i+1, $name_prev, time()], $name);
					$replace   = preg_replace("#([^a-zA-Z0-9-_\.]+)#i", "", $replace);
					$replace   = preg_replace("#([-_]{2,})#i", "", $replace);
					$replace   = preg_replace("#([\.]{2,})#i", ".", $replace);

					$this->name[$i] = $replace .'.'. $this->extension[$i];
				}
			} else {
				$name_prev = mb_strtolower(str_replace("." . $this->extension, "", $this->name));		
				$replace   = str_ireplace(['{id}', '{name}', '{time}'], [$i+1, $name_prev, time()], $name);
				$replace   = preg_replace("#([^a-zA-Z0-9-_\.]+)#i", "", $replace);
				$replace   = preg_replace("#([-_]{2,})#i", "", $replace);
				$replace   = preg_replace("#([\.]{2,})#i", ".", $replace);

				$this->name = $replace .'.'. $this->extension;
			}

			return $this;
		}

		/**
		*	change file extension
		*
		*
		*	@param int index
		*/
		public function changeExtension($new_extension = null, $index = -1) {
			if($new_extension != null) {
				// get name
				$name = ($index != '-1') ? $this->name[$index] : $this->name;
				$exp  = explode(".", $name);
				$new  = array_slice($exp, 0, count($exp)-1);
				$imp  = implode("", $new);

				// set
				if($index != '-1') {
					$this->name[$index] = $imp . '.' . $new_extension;
				} else {
					$this->name = $imp . '.' . $new_extension;
				}
			}
		}

		/**
		*	upload files and return status
		*	
		*	@param string sub_folder : ''
		*	@return array
		*/
		public function upload($sub_folder = '') {
			// check if input method not used
			if(!$this->input) {
				$this->errors[] = "Error: input method not used";

				return ['status' => 'error', 'errors' => $this->errors];
			}

			// upload files
			if($this->multiple) {
				for($i = 0; $i < $this->total; $i++) {
					// check if file uploaded and accepted
					if($this->error[$i] == 0 && $this->accepted[$i] == 1) {
						$final_dir = ($sub_folder != '') ? $this->dir . $sub_folder .'/'. $this->name[$i] : $this->dir . $this->name[$i];
						$move      = move_uploaded_file($this->tmp_name[$i], $final_dir);
					
						if($move) {
							$this->uploaded++;
							$this->return['names'][] = $this->name[$i];
						} else {
							$this->errors[] = "File couldn't uploaded: " . $this->name[$i];
						}
					}
				}

				// set return array
				$this->return['status']   = 'ok';
				$this->return['uploaded'] = $this->uploaded;
				$this->return['errors']   = $this->errors;

				// return
				return $this->return;
			} else {
				if($this->error == 0 && $this->accepted == 1) {
					$final_dir = ($sub_folder != '') ? $this->dir . $sub_folder .'/'. $this->name : $this->dir . $this->name;
					$move      = move_uploaded_file($this->tmp_name, $final_dir);
					$this->final_dir = $final_dir;

					if($move) {
						return [
							'status' => 'ok',
							'file' => $this->name,
							'type' => $this->type,
							'dir' => $final_dir
						];
					} else {
						$this->errors[] = "File couldn't uploaded " . $this->name;

						return [
							'status' => 'error',
							'errors' => $this->errors
						];
					}
				} else {
					return [
						'status' => 'error',
						'errors' => $this->errors
					];
				}
			}
		}
	}
?>
