<?php


class UploadHelper {

	protected static $uploader;

	protected static $thumb_width  = 250;
	protected static $thumb_height = 150;
	
	protected static function getUploader($allowedExtensions = null, $sizeLimit = null){
		if( !class_exists('AjaxUploader') ){
			require_once(LIB_PATH."AjaxUploader.php");
		}
		return new AjaxUploader($allowedExtensions, $sizeLimit);
	}

	/**
	 * Saves new uploaded files
	 */
	public static function saveFile(){

		$field_name = Params::getParam('field_name');
		$group      = Params::getParam('group');
		$uid        = Params::getParam('qquuid');
        $file       = Params::getFiles('qqfile');
		$files      = Session::newInstance()->_get('ajax_files');

        $form = \Lib\LZForm::getInstance($group);
        $field = $form->getField($field_name);

        if( !$field || empty($file) ){
            return 	$result = array('success' => false, 'message' => sprintf(__('Field %s not found!','lz_theme_options'),$field_name) );
        }

		if( !isset($files[$uid]) ){

            self::$uploader = self::getUploader(null, (1024*$field->getConfig('max_size')));

            $original   = pathinfo( $file['name'] );
            if( $field->validate($file) ){

                $filename   = self::getUniqueFilename( $original['extension'] );

                $path 		= $field->getConfig('upload_path');
                $thumb_path = $field->getConfig('upload_thumb_path');
                $thumb_url  = 'lz_theme_options/thumbnails/'.$filename;

                if( defined('DEMO') ){
                    $path 		= LZO_DEMO_USER_PATH;
                    $thumb_path = LZO_DEMO_USER_THUMB_PATH;
                    $thumb_url  = sprintf( 'lz_theme_demo_users/%s/thumbnails/%s', DEMO_USER_IP, $filename);
                }

                if( !file_exists( $path ) ){
                    @mkdir( $path, 0777 );
                }

                $result = self::$uploader->handleUpload( $path.$filename, true );

                if( isset( $result['success'] ) && $result['success'] == true && self::saveAndResize( $field_name, $group, $uid, $filename ) ){
                    $result['success'] = true;
                    $result['thumbnailUrl'] = osc_uploads_url( $thumb_url );
                    $result['uploadName'] = $filename;
                    return $result;
                }
            }
            $message = sprintf(__('Invalid file, upload files with one of the following extensions (%s)','lz_theme_options'),osc_allowed_extension());
            if( count($field->error) ){
                $message = implode('<br/>', $field->error);
            }

            return 	$result = array('success' => false, 'message' => $message );

		}

		return 	$result = array('success' => false, 'message' => __('File already exists, try another file or change the filename.','lz_theme_options') );
	}

	/**
	 * Saves the new file while it creates a thumbnail
	 */
	protected function saveAndResize( $field_name, $group, $uid, $filename ){
		
		$path 		= LZO_UPLOAD_PATH;
		$thumb_path = LZO_THUMB_PATH; 
		
		$pref_name = osc_current_web_theme().'_'.$group.'_'.$field_name;
		
		if( defined('DEMO')){
			$current_file = OSCLztoModel::newInstance()->getUserFileByName(DEMO_USER_IP, $pref_name);
			$path 		= LZO_DEMO_USER_PATH;
			$thumb_path = LZO_DEMO_USER_THUMB_PATH;
			$saved = OSCLztoModel::newInstance()->updateUserSettings( 
						DEMO_USER_IP,
						array( 's_ip' => DEMO_USER_IP, 
							   's_name' => $pref_name, 
							   's_settings' => $uid.'||'.$filename
						)
			);
		} else {
			$current_file = osc_get_preference($pref_name,'lz_theme_options_uploads');
			$saved = osc_set_preference( $pref_name, $uid.'||'.$filename, 'lz_theme_options_uploads', 'STRING' );
		}
		
		
		
		if( $saved !== false ){
			
			if(!empty($current_file)){
				$current_file = explode('||', $current_file );
				
				if( file_exists($path.$current_file[1])){
					@unlink($path.$current_file[1]);
				}
				if( file_exists($thumb_path.$current_file[1])){
					@unlink($thumb_path.$current_file[1]);
					
				}
				$session_files = Session::newInstance()->_get('ajax_files');
				if( isset( $session_files[$current_file[0]])){
					unset($session_files[$current_file[0]]);
					Session::newInstance()->_set('ajax_files', $session_files);
				}
			}
			
			
			if( !file_exists( $thumb_path ) ){
				@mkdir( $thumb_path, 0777, true );
			}
			
			$resize = ImageResizer::fromFile( $path.$filename );
			$resize->resizeTo( self::$thumb_width, self::$thumb_height, true );

			try {
				$resize->saveToFile( $thumb_path.$filename );
			} catch( Exception $e){
				return false;
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Gets a unique name for a new upload file
	 */
	protected static function getUniqueFileName($ext){
		return uniqid("qqfile_").".".$ext;
	}
	
	/**
	 * Completly deletes the file from the database and filesystem
	 */
	public static function delete($field_name, $group, $uuid = null ){
		try {
			$path = LZO_UPLOAD_PATH;
			$thumb_path = LZO_THUMB_PATH;
			
			$files = Session::newInstance()->_get('ajax_files');
			if( empty($uuid)){

				if( defined('DEMO') ){
					$db_file = OSCLztoModel::newInstance()->getUserFileByName( DEMO_USER_IP, osc_current_web_theme().'_'.$group.'_'.$field_name);
					$path 		= LZO_DEMO_USER_PATH;
					$thumb_path = LZO_DEMO_USER_THUMB_PATH;
				} else {
					$db_file = osc_get_preference( osc_current_web_theme().'_'.$group.'_'.$field_name, 'lz_theme_options_uploads');
				}
				
				if( !empty($db_file) ){
					$f = explode('||', $db_file );
					$uid = $f[0];
					$filename = $f[1];
				}
			} else {
				if( defined('DEMO') ){
					$path 		= LZO_DEMO_USER_PATH;
					$thumb_path = LZO_DEMO_USER_THUMB_PATH;
				}
				if( isset($files[$uuid]) ){
					$filename = $files[$uuid];
				}
			}

			if( file_exists( $path.$filename ) ){
				@unlink( $path.$filename );
				@unlink( $thumb_path.$filename );
			}
			
			if( defined('DEMO') ){
				OSCLztoModel::newInstance()->deleteUserFileByName(DEMO_USER_IP, osc_current_web_theme().'_'.$group.'_'.$field_name );
			} else {
				osc_delete_preference( osc_current_web_theme().'_'.$group.'_'.$field_name, 'lz_theme_options_uploads');
			}
			
			if( isset( $files[$uid])){
				unset($files[$uid]);
				Session::newInstance()->_set('ajax_files', $files);
			}

		} catch( Exception $e ){
			return false;
		}
		return true;
	}
	
	/*
	public function cleanUpFile($user_file){
		
		$file = explode('||', $user_file);
		
		if( file_exists(LZO_UPLOAD_PATH.$file[1]) ){
			@unlink(LZO_DEMO_USER_PATH.$file[1]);
		}
		else if( file_exists(LZO_DEMO_USER_THUMB_PATH.$file[1]) ){
			@unlink(LZO_DEMO_USER_THUMB_PATH.$file[1]);
		}
		Preference::newInstance()->dao->delete(Preference::newInstance()->getTableName(),'s_section = \'lz_theme_options\'');
		Preference::newInstance()->dao->delete(Preference::newInstance()->getTableName(),'s_section = \'lz_theme_options_uploads\'');
		
		
	}
	*/


	/**
	 * get all uploads for the current template
	 */
	public static function getFiles( $upload_fields = array() ){
		
		Preference::newInstance()->dao->select();
		Preference::newInstance()->dao->from( Preference::newInstance()->getTableName() );
		Preference::newInstance()->dao->where( 's_section', 'lz_theme_options_uploads' );

		$i = 0;
		$results = array();
		$files = array();
		
		if( count( $upload_fields ) > 0 ){
			foreach( $upload_fields as $field ){
				if( $i == 0 ){
					Preference::newInstance()->dao->where( 's_name', osc_current_web_theme().'_'.$field );
				} else {
					Preference::newInstance()->dao->orWhere( 's_name', osc_current_web_theme().'_'.$field );
				}
				$i++;
			}
			
		} else {
			Preference::newInstance()->dao->like( 's_name', osc_current_web_theme().'_', 'after' );
		}	
		$files = Preference::newInstance()->dao->get();
	
		if( is_object($files) && $files->numRows() > 0 ){
			foreach( $files->resultArray() as $file ) {
				$field = str_replace( osc_current_web_theme().'_', '', $file['s_name'] );
				$results[$field] = $file;
			}
		}
		return $results;
	}
	
	public function getUserFiles(){
		$user_files = OSCLztoModel::newInstance()->getUserUploads( DEMO_USER_IP );
		if( false !== $user_files ){
			$files = array();
			foreach( $user_files as $file ){
				$files[$file['s_name']] = $file['s_settings'];
			}
			return $files;
		}
		return array();
	}

	/**
	 * Ajax funtion to load existing uploaded files
	 */
	public static function getFileByName($field_name, $group){
		$results 	= array();
		
		$theme = osc_current_web_theme();

		if( function_exists('lz_demo_selected_theme') ){
			$theme = lz_demo_selected_theme();
		}
		
		if( defined( 'DEMO' ) ){
			$path = LZO_DEMO_USER_PATH;
			$thumb_path = LZO_DEMO_USER_THUMB_PATH;
			$url = 'lz_theme_demo_users/'.DEMO_USER_IP.'/';
			$thumb_url = 'lz_theme_demo_users/'.DEMO_USER_IP.'/thumbnails/';
			$file = OSCLztoModel::newInstance()->getUserFileByName(DEMO_USER_IP, $theme.'_'.$group.'_'.$field_name );
		} else {
			$path = LZO_UPLOAD_PATH;
			$thumb_path = LZO_THUMB_PATH;
			$url = 'lz_theme_options/';
			$thumb_url = 'lz_theme_options/thumbnails/';
			$file = osc_get_preference( $theme.'_'.$group.'_'.$field_name, 'lz_theme_options_uploads' );
		}

		if( !empty( $file ) ){
			$f 				 = explode( '||', $file );
			$uid 		     = $f[0];
			$filename 		 = $f[1];

			if( file_exists($path.$filename ) ){
				$results['name'] = $filename;
				$results['uuid'] = $uid;
				$results['size'] = lzto_format_file_size( filesize( $path.$filename ) );
				$results['url']  = osc_uploads_url( $url.$filename );
				$results['thumbnailUrl'] = osc_uploads_url( $thumb_url.$filename );
			} else {
				if( defined('DEMO') ){
					OSCLztoModel::newInstance()->deleteUserFileByName(DEMO_USER_IP, $theme.'_'.$group.'_'.$field_name );
				} else {
					osc_delete_preference( $theme.'_'.$group.'_'.$field_name, 'lz_theme_options_uploads' );
				}
			}
		}
		return $results;
	}

	/**
	 * Ajax funtion to load existing uploaded files in json format
	 */
	public static function getFilesAsJson(){
		if( Params::existParam('field_name') ){
			$field_name = Params::getParam('field_name');
			$group      = Params::getParam('group');
			$results    = self::getFileByName( $field_name, $group );
		
			if( !empty($results) ){
				$files      = Session::newInstance()->_get('ajax_files');
				if( empty($files)){ $files = array(); }
				$files[$results['uuid']] = $results['name'];
				Session::newInstance()->_set('ajax_files', $files);
				return array( 'status' => true, $field_name => $results );
			}
		}
		return array( 'status' => false );
	}


}