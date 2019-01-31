<?php
	class upload {
		# construct
		public function __construct($upload_dir = null) {
			$this->root     = $_SERVER['DOCUMENT_ROOT'];
			$this->dir 	    = ($upload_dir) ? $this->root. '/' . $upload_dir : $this->root. '/files/upload/'; 
			$this->up_error = 0;
			$this->errors   = array();
		}

		# file info
		public function info($files = null) {
			if($files == null)
				return false;

			$this->info      = true;
			$this->name 	 = $_FILES[$files]['name'];
			$this->tmp_name  = $_FILES[$files]['tmp_name'];
			$this->type 	 = $_FILES[$files]['type'];
			$this->error 	 = $_FILES[$files]['error'];
			$this->size 	 = $_FILES[$files]['size'];
			$this->extension = end(explode('.', $_FILES[$files]['name']));

			return $this;
		}

		# extension control
		public function ftype() {
			# get parameters
			$types = func_get_args();

			#
			if(!in_array($this->extension, $types)) {
				$this->up_error = 1;
				$this->errors[] = "100";
			}

			
			return $this;
		}

		# size control
		public function size($max_file_size = 2097152 ) {
			if($this->size > $max_file_size){
				$this->up_error = 1;
				$this->errors[] = "101";
			}

			return $this;
		}

		# name file
		public function name($name = null) {
			if($name == null)
				return $this;

			$this->name = $name. '.' .$this->extension;

			return $this;
		}

		# upload
		public function upload($sub_folder = '') {
			# return false if info() not used
			if($this->info == false) {
				$this->up_error = 1;
				$this->errors[] = "102";
			}
				
			# is file uploaded complately
			if($this->error != 0) {
				$this->up_error = 1;
				$this->errors[] = "103";
			}

			# upload if no error ocurred
			if($this->up_error == 0) {
				# create files final dir
				$final_dir = ($sub_folder) ? $this->dir. $sub_folder . '/'. $this->name : $this->dir. $this->name;
				$move_file = move_uploaded_file($this->tmp_name, $final_dir);

				if(!$move_file) {
					$this->errors[] = "104";

					return array("status"=> "error", "errors"=> $this->errors);
				} else {
					return array(
						"status"=> "ok",
						"file_name" => $this->name,
						"file_extension"=> $this->extension,
						"file_size"=> $this->size,
						"file_dir"=> $final_dir
					);
				}
			} else {
				return array("status"=> "error", "errors"=> $this->errors);
			}
		}
	}
?>