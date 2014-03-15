<?php


class UploadHelper {

	protected static $uploader;

	protected static $thumb_width  = 250;
	protected static $thumb_height = 150;

	protected static function getUploader(){
		if( !class_exists('AjaxUploader') ){
			require_once(LIB_PATH."AjaxUploader.php");
		}
		return new AjaxUploader();
	}

	/**
	 * Saves new uploaded files
	 */
	public static function saveFile(){

		$field_name = Params::getParam('field_name');
		$group      = Params::getParam('group');
		$uid        = Params::getParam('qquuid');
		$files      = Session::newInstance()->_get('ajax_files');

		if( !empty($field_name) && !isset($files[$uid]) ){

			self::$uploader = self::getUploader();
			
			$original   = pathinfo( self::$uploader->getOriginalName() );
			$filename   = self::getUniqueFilename( $original['extension'] );

			if( !file_exists( LZO_UPLOAD_PATH ) ){
				@mkdir( LZO_UPLOAD_PATH, 0777 );
			}

			$result = self::$uploader->handleUpload( LZO_UPLOAD_PATH.$filename );

			if( isset( $result['success'] ) && $result['success'] == true && self::saveAndResize( $field_name, $group, $uid, $filename ) ){
				$result['success'] = true;
				$result['thumbnailUrl'] = osc_uploads_url( 'lz_theme_options/thumbnails/'.$filename );
				$result['uploadName'] = $filename;
				return $result;
			}
		}
		return 	$result = array('success' => false, 'message' => _m('File already exists, try another file or change the filename.','lz_theme_options') );
	}

	/**
	 * Saves the new file while it creates a thumbnail
	 */
	protected function saveAndResize( $field_name, $group, $uid, $filename ){
		self::delete( $field_name, $group );
	
		$saved = osc_set_preference( osc_current_web_theme().'_'.$group.'_'.$field_name, $uid.'||'.$filename, 'lz_theme_options_uploads', 'STRING' );

		if( $saved !== false ){
			$resize = ImageResizer::fromFile( LZO_UPLOAD_PATH.'/'.$filename );
			$resize->resizeTo( self::$thumb_width, self::$thumb_height, true );
			if( !file_exists( LZO_THUMB_PATH ) ){
				mkdir( LZO_THUMB_PATH );
			}
			try {
				$resize->saveToFile( LZO_THUMB_PATH.$filename );
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
	 * Delete previus uploaded files
	 */
	public static function deleteFile(){
		$filename = Params::getParam('field_name');
		$group    = Params::getParam('group');
		$uuid     = Params::getParam('qquuid');
		$success  = self::delete( $filename, $group, $uuid );
		return array( 'success' => $success, 'deletedFile' => $filename );
	}

	/**
	 * Completly deletes the file from the database and filesystem
	 */
	protected static function delete($field_name, $group, $uuid = null ){
		try {
			$files = Session::newInstance()->_get('ajax_files');
			if( empty($uuid)){
				$db_file = osc_get_preference( osc_current_web_theme().'_'.$group.'_'.$field_name, 'lz_theme_options_uploads');
				if( !empty($db_file) ){
					$f = explode('||', $db_file );
					$uid = $f[0];
					$filename = $f[1];
				}
			} else {
				if( isset($files[$uuid]) ){
					$filename = $files[$uuid];
				}
			}

			if( file_exists( LZO_UPLOAD_PATH.$filename ) ){
				@unlink( LZO_UPLOAD_PATH.$filename );
				@unlink( LZO_THUMB_PATH.$filename );
			}
		    //var_dump(osc_current_web_theme().'_'.$group.'_'.$field_name);
			osc_delete_preference( osc_current_web_theme().'_'.$group.'_'.$field_name, 'lz_theme_options_uploads');

			if( isset( $files[$uid])){
				unset($files[$uid]);
				Session::newInstance()->_set('ajax_files', $files);
			}

		} catch( Exception $e ){
			return false;
		}
		return true;
	}

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
		
		//var_dump(Preference::newInstance()->dao->lastQuery());exit;
		
		if( is_object($files) && $files->numRows() > 0 ){
			foreach( $files->resultArray() as $file ) {
				$field = str_replace( osc_current_web_theme().'_', '', $file['s_name'] );
				$results[$field] = $file;
			}
		}
		
		return $results;
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

		fb($theme.'_'.$group.'_'.$field_name, 'Selected upload');
		$file = osc_get_preference( $theme.'_'.$group.'_'.$field_name, 'lz_theme_options_uploads' );

		fb($file, 'Selected upload exists' );

		if( !empty( $file ) ){
			$f 				 = explode( '||', $file );
			$uid 		     = $f[0];
			$filename 		 = $f[1];

			if( file_exists(LZO_UPLOAD_PATH.$filename ) ){
				$results['name'] = $filename;
				$results['uuid'] = $uid;
				$results['size'] = self::human_filesize( filesize( LZO_UPLOAD_PATH.$filename ) );
				$results['url']  = osc_uploads_url( 'lz_theme_options/'.$filename );
				$results['thumbnailUrl'] = osc_uploads_url( 'lz_theme_options/thumbnails/'.$filename );
			} else {
				self::delete($field_name, $group);
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

	/**
	 * Returns a formatted filesize
	 */
	protected static function human_filesize($bytes, $decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}

}