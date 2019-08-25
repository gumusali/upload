<?php
	class upload {
		# construct
		public function __construct($upload_dir = null) {
			$this->root     = $_SERVER['DOCUMENT_ROOT'];
			$this->dir 	    = ($upload_dir) ? $this->root. '/' . $upload_dir : $this->root. '/files/uploads/'; 
			$this->up_error = 0;
			$this->errors   = array();
			$this->multiple = false;
			$this->files    = null;
			$this->total    = 0;
			$this->uploaded = 0;
			$this->return   = array("status" => null, "uploaded" => 0, "names" => array(), "errors" => "");
		}

		# file info
		public function info($files = null, $multiple = false) {
			# check files
			if($files == null)
				return false;

			# define $this->files
			$this->files = $_FILES[$files];

			#
			if($multiple == true) {
				$this->info      = true;
				$this->multiple  = true;
				$this->total     = count($this->files['name']);

				# get image info
				for($i = 0; $i < $this->total; $i++) {
					$this->name[] 	   = $this->files['name'][$i];
					$this->tmp_name[]  = $this->files['tmp_name'][$i];
					$this->type[] 	   = $this->files['type'][$i];
					$this->error[] 	   = $this->files['error'][$i];
					$this->size[] 	   = $this->files['size'][$i];
					$this->extension[] = end(explode('.', $this->files['name'][$i]));
					$this->accepted[]  = 1;
				}
			} else {
				$this->info      = true;
				$this->name 	 = $this->files['name'];
				$this->tmp_name  = $this->files['tmp_name'];
				$this->type 	 = $this->files['type'];
				$this->error 	 = $this->files['error'];
				$this->size 	 = $this->files['size'];
				$this->extension = end(explode('.', $this->files['name']));
			}

			return $this;
		}

		# extension control
		public function ftype() {
			# get parameters
			$types = func_get_args();

			#
			if($this->multiple == true) {
				for($i = 0; $i < $this->total; $i++) {
					if(!in_array($this->extension[$i], $types)) {
						$this->accepted[$i] = 0;
						$this->errors[]  	= "100";
					}
				}
			} else {
				if(!in_array($this->extension, $types)) {
					$this->up_error = 1;
					$this->errors[] = "100";
				}
			}

			
			return $this;
		}

		# size control
		public function size($max_file_size = 2097152 ) {
			if($this->multiple == true) {
				for($i = 0; $i < $this->total; $i++) {
					if($this->size[$i] > $max_file_size) {
						$this->accepted[$i] = 0;
						$this->errors[]     = "101";
					}
				}
			} else {
				if($this->size > $max_file_size){
					$this->up_error = 1;
					$this->errors[] = "101";
				}
			}

			return $this;
		}

		# name file
		public function name($name = null) {
			if($name == null)
				return $this;

			if($this->multiple == true) {
				for($i = 0; $i < $this->total; $i++) {
					$replace = str_replace(array('{id}', '{time}'), array($i+1, time()), $name);
					$replace = mb_strtolower($replace);
					$replace = preg_replace("#([^a-zA-Z0-9-_\.]+)#i", "", $replace);
					$replace = preg_replace("#([-_]{2,})#i", "", $replace);
					$replace = preg_replace("#([\.]{2,})#i", ".", $replace);
					
					$this->name[$i] = $replace. '.' .$this->extension[$i];
				}
			} else {
				$replace = str_replace('{time}', time(), $name);
				$replace = mb_strtolower($name);
				$replace = preg_replace("#([^a-zA-Z0-9-_\.]+)#i", "", $replace);
				$replace = preg_replace("#([-_]{2,})#i", "", $replace);
				$replace = preg_replace("#([\.]{2,})#i", ".", $replace);
				
				$this->name = $replace. '.' .$this->extension;	
			}

			

			return $this;
		}

		# upload
		public function upload($sub_folder = '') {
			# return false if info() not used
			if($this->info == false) {
				$this->up_error = 1;
				$this->errors[] = "102";
			}
				
			# upload
			if($this->multiple == true) {
				for($i = 0; $i < $this->total; $i++) {
					# check if file uploaded complately
					if($this->error[$i] == 0 && $this->accepted[$i] == "1") {
						# create files final dir
						$final_dir = ($sub_folder) ? $this->dir. $sub_folder . '/'. $this->name[$i] : $this->dir. $this->name[$i];
						$move_file = move_uploaded_file($this->tmp_name[$i], $final_dir);

						if($move_file) {
							$this->uploaded++;
							$this->return['uploaded'] = $this->uploaded;
							$this->return['names'][]  = $this->name[$i];
						}
					}
				}

				# set return
				$this->return['status'] = 'ok';
				$this->return['errors'] = $this->errors;

				return $this->return;
			} else {
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
	}
?>