<?php
require dirname(__FILE__)."/lib/LZForm.php";
require dirname(__FILE__)."/helpers/options.helper.php";
require dirname(__FILE__)."/helpers/upload.helper.php";
require dirname(__FILE__)."/model/OSCLztoModel.php";
require dirname(__FILE__)."/lib/Useful.php";
require dirname(__FILE__)."/lib/Field.php";
require dirname(__FILE__)."/lib/Field/BaseOptions.php";
require dirname(__FILE__)."/lib/Field/Options.php";
require dirname(__FILE__)."/lib/Field/MultipleOptions.php";
require dirname(__FILE__)."/lib/Field/Text.php";

/**
 * Class Builder 
 * 
 * @author Jair Milanes Junior
 * @version 1.0
 *
 */

class Builder {

	/**
	 * It references to self object: Builder.
	 * It is used as a singleton
	 *
	 * @access private
	 * @since 1.0
	 * @var ModelProducts
	 */
	private static $instance;
	
	/**
	 * It is a instance of our form object
	 * 
	 * @access protected
	 * @since 1.0
	 */
	protected $form;
	
	/**
	 * Instance of the options object
	 * 
	 * @access protected
	 * @since 1.0
	 */
	protected $options;
	
	/**
	 * Log file path
	 * 
	 * @access protected
	 * @since 1.0
	 */
	protected $log_file;

	/**
	 * Class construct
	 */
	public function __construct(){
		if( OC_ADMIN ){
			$url = osc_admin_base_url(true);
		} else {
			$url = osc_base_url(true);
		}
		$this->form =  Lib\LZForm::getInstance('lzto', $url, true, 'POST' );
		$path = osc_plugins_path(__FILE__).'lz_theme_options/logs';
		if( !file_exists($path) ){
			@mkdir($path, 0777);
		}
		$this->log_file = $path.'/error.log';
	}

	/**
	 * It creates a new Builder object class ir if it has been created
	 * before, it return the previous object
	 *
	 * @access public
	 * @since 3.0
	 * @return Builder
	 */
	public static function newInstance(){
		if( !self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance ;
	}

	/**
	 * Creates and saves a new preset
	 * 
	 * @return number|boolean Return 1 if no data to copy from, 2 if 
	 * admin is not logged in, 3 if all went well and falseif fails 
	 * to create the new zip file  
	 */
	public function savePreset(){
		
		if( !osc_is_admin_user_logged_in() ){
			//$this->log('Trying to save a preset with no admin logged in');
			return 2;
		}
		
		$preset_name = Params::getParam('preset_name');
		
		$data    = osc_get_preference( osc_current_web_theme(), 'lz_theme_options' );
		
		if( !empty($data) ){
			$data = unserialize($data);
		}

		if( !is_array($data) ){
			$data = array();
		} 
		
		$uploads = UploadHelper::getFiles();
		
		if( count( $uploads ) > 0 ){
			$data['uploads'] = $uploads;
		}

		if( empty($data) ){
			//$this->log('No data to create a preset, save options at least one time before creating a preset.');
			return 1;
		}

		$preset_json = json_encode($data);
		
		//chmod( LZO_UPLOAD_PATH , 0755);

		if( $this->zipPreset( $preset_json, LZO_UPLOAD_PATH, $preset_name )  ){
			//$this->log('New preset created, name: '.$preset_name.'.');
			return 3;
		}
		//$this->log('Failed to save a new preset, name: '.$preset_name.'.');
		return false;
		
	}
	
	/**
	 * Get all existing presets
	 * 
	 * @return array  Returns a array containing the files or a 
	 * array with a key empty if no files were found
	 */
	public function loadPresets(){
		if( !file_exists(LZO_PRESETS_PATH) ){
			mkdir(substr(LZO_PRESETS_PATH, 0, -1));
		}
		
		$dir = new DirectoryIterator(LZO_PRESETS_PATH);
		$files = array();
		foreach( $dir as $file ){
			if( $file->isFile() ){
				$name = $file->getFilename();
				$parts = explode( '-', $name );
                array_shift($parts);
                $parts = implode('-', $parts);

				$preset_name = str_replace( '.zip', '', $parts );
				
				$files[$preset_name] = array(
						'title'      => ucfirst( strtolower( str_replace('_', ' ', $preset_name ) ) ),
						'load_url'	 => osc_ajax_hook_url( 'lzto_load_preset', array( '&preset_name' => $preset_name ) ),
						'delete_url' => osc_ajax_hook_url( 'lzto_remove_preset', array( '&preset_name' => $preset_name ) )
				);
				//$files[$preset_name] = ucfirst( strtolower( str_replace('_', ' ', $preset_name ) ) );
			}
		}
		if( count( $files ) > 0 ){
			ksort($files);
			return $files;
		}
		return array('empty' => array(
			'title' => _m('There is no presets, click below to create one.', 'lz_theme_options')
		));
	}
	
	/**
	 * Loads a specific preset
	 * 
	 * @return boolean True if load with success false otherwise
	 */
	public function loadPreset(){
		
		if( !Params::existParam( 'preset_name' ) ){
			return false;
		}
		$file = LZO_PRESETS_PATH.'preset-'.Params::getParam( 'preset_name' ).'.zip';
		if( !file_exists( $file ) ){
			return false;
		}
		
		$zip = new ZipArchive();

		if( $zip->open( $file ) ){
			$temp_path = UPLOADS_PATH.'temp/lz_theme_options';

			if( file_exists($temp_path)){
				$this->rmdir_recurse($temp_path);
			}
			
			@mkdir( $temp_path, 0777 );

			if( $zip->extractTo( $temp_path ) ){
				$zip->close();
				
				if( file_exists( $temp_path.'/preset.json' ) ){
					$json = file_get_contents( $temp_path.'/preset.json' );
					$data = json_decode( $json, true );

					if( isset( $data['uploads'] ) ){
						foreach( $data['uploads'] as $field => $file ){
							osc_delete_preference( $file['s_name'], $file['s_section'] );
							$saved = osc_set_preference( $file['s_name'], $file['s_value'], $file['s_section'], $file['e_type'] );
						}
						unset($data->uploads);
					}
					osc_delete_preference( osc_current_web_theme(), 'lz_theme_options' );
					$data = osc_set_preference( osc_current_web_theme(), serialize($data), 'lz_theme_options' );
					
					if( false !== $data ){
						
						$path = substr(LZO_UPLOAD_PATH, 0, -1 );
						if( file_exists($path) ){
							rename($path, $path.'_old');
						}
						
						if( rename( $temp_path, $path )){
							@unlink( $path.'/preset.json' );
						}
						
						if( file_exists($path.'_old') ){
							$this->rmdir_recurse($path.'_old');
						}
						
						return true;
					}
				}
			}
		}
		return false;
	}
	
	/**
	 * Completly removes a preset
	 * 
	 * @return boolean True if success false otherwise
	 */
	public function removePreset(){
		$preset = Params::getParam('preset_name');
		$file = LZO_PRESETS_PATH.'preset-'.$preset.'.zip';
		if( file_exists($file)){
			@unlink($file);
			return $this->loadPresets();
		}
		return true;
	}
	
	/**
	 * Helper method to remove the ontents of a dir but not the dir it´s self
	 * 
	 * @param string $path
	 * @return boolean True on success false otherwise
	 */
	protected function rmdir_recurse($path) {
	    $path = rtrim($path, '/').'/';
	    if( file_exists($path) ){
		    $handle = opendir($path);
		    while(false !== ($file = readdir($handle))) {
		        if($file != '.' and $file != '..' ) {
		            $fullpath = $path.$file;
		            if(is_dir($fullpath)) $this->rmdir_recurse($fullpath); else unlink($fullpath);
		        }
		    }
		    closedir($handle);
	    	return @rmdir($path);
	    }
	    return true;
	}


	/***********************************************************************
	 * FIELDS SETUP FUNCTIONS
	 *********************************************************************/
	
	/**
	 * Loads and prepares the available theme options
	 * 
	 * @param array $options Array containing the theme options
	 * @return boolean True on success false otherwise
	 */
	public function setOptions( array $options ){
		$data = null;

        /// Load specific user theme options settings
		if( lzto_isDemo() ){
			$data = $this->getUserSettings();
		}
		
		if( empty($data) ){
			$data    = osc_get_preference( osc_current_web_theme(), 'lz_theme_options' );
		}
		
		if( !empty($data)){
			$data = unserialize($data);
		}
		
		if( is_object($data)){
			$data = json_decode(json_encode($data), true);
		}

		$this->options = new OptionsHelper( $options, $data );
		
		if( empty( $data ) ){
			$data = $this->options->getDefaults();
			$data = array_filter($data);
		}

		if( !empty( $data ) ){
			
			$forms = Lib\LZForm::getInstance()->getAllInstances();

			foreach( $forms as $parent => $form ){
				
				$name = $form->getName();
				$group = $form->getGroup();

				if( !empty($group) ){
					if( isset( $data[$group][$parent] ) ){
						$pars = array_filter( $data[$group][$parent] );
						if( !empty($pars) ){
							$form->addData( $pars );
						}
					}
				} else {
					if( isset( $data[$parent] ) ){
						$pars = array_filter( $data[$parent] );
						if( !empty($pars) ){
							$form->addData( $pars );
						}
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * Grabs a specific user saved settings in case we are in a demo site
	 * 
	 * @return string|boolean Returns de s_setting string or false if feils to grab it.
	 */
	protected function getUserSettings(){
		$rs = OSCLztoModel::newInstance()->getUserSettings(DEMO_USER_IP);
		if( !empty($rs) ){
			return $rs;
		}

		if( lzto_isDemo() ){
			$settings = osc_get_preference(osc_current_web_theme(), 'lz_theme_options');
			$files    = UploadHelper::getFiles();
			$user_settings = OSCLztoModel::newInstance()->createUserSettings( DEMO_USER_IP, $settings, $files );
			if( false !== $user_settings ){
				return $user_settings;
			}
		}
		return false;
	}

    /**
     * Resets a user settings
     *
     * @param $ip
     * @return mixed
     */
    protected function resetUserSettings($ip){
        if( OSCLztoModel::newInstance()->deleteUserSettings($ip) ){
            $settings = osc_get_preference(osc_current_web_theme(), 'lz_theme_options');
            $files    = UploadHelper::getFiles();
            $user_settings = OSCLztoModel::newInstance()->createUserSettings( DEMO_USER_IP, $settings, $files );
            if( false !== $user_settings ){
                return $user_settings;
            }
        }
        return OSCLztoModel::newInstance()->getUserSettings($ip);
    }
	/**
	 * Gets a new form instance
	 */
	protected function getSubForm( $name ){
		return Lib\LZForm::getInstance( $name );
	}

	/**
	 * Get all the available fields
	 */
	public function getFields( $group = null ){
		return $this->options->getFields($group);
	}

	/**
	 * Gets a single field value
	 *
	 * @param string $field Name of the field
	 */
	public function getOption( $group, $field ){
		return $this->options->getOption( $field, $group );
	}

	/**
	 * Returns a specific group name given its slug
	 * 
	 * @param string $group Group slug
	 * @return Ambigous <string, multitype:>
	 */
	public function getGroupName( $group_slug ){
		return $this->options->getGroupName( $group_slug );
	}

	/**
	 * Get all the options for a specific group given the group slug
	 * 
	 * @param string $group_slug Slug of the disired group
	 * @return array|false Group fields or false if it fails
	 */
	public function getOptionsByGroupName( $group_slug ){
		return $this->options->getOptionsByGroupName($group_slug);
	}

	/**
	 * Get a field value if it exists
	 * 
	 * @param string $group_slug Slug of the group
	 * @param string $field Name of the field
	 * @return mixed|boolean Returns the fields value and false otherwise
	 */
	public function hasOption( $group_slug, $field ){
		$val = Lib\LZForm::getInstance($group_slug)->getFieldValue($field);
		return ( !empty( $val )? $val : false );
	}
	
	/***********************************************************************
	 * ACTION METHODS
	**********************************************************************/

	/**
	 * Saves theme options values
	 */
	public function save(){

		$forms = $this->form->getAllInstances();

		if ( count( $forms ) > 0 ){
			$data   = array();
			$errors = array();

			foreach( $forms as $parent => $form ){
				$name = $form->getName();
				$group = $form->getGroup();

                if( !empty($parent) && $parent !== 'lzto' ){
                    $isValid = $form->validate( $parent, true );

                    if(false === $isValid){
                        $errors[$parent] = $form->getErrors();
                    } else {
                        if( !empty($group) ){
                            if( !isset($data[$group])){
                                $data[$group] = array();
                            }
                            $data[$group][$parent] = $isValid;
                        } else {
                            $data[$parent] = $isValid;
                        }
                    }
                }
			}

			if( count($errors) == 0 ){
				$form_data = serialize( $data );

				if(lzto_isDemo()){
					$status = OSCLztoModel::newInstance()->updateUserSettings( DEMO_USER_IP, array('s_ip' => DEMO_USER_IP, 's_name' => osc_current_web_theme(), 's_settings' => $form_data) );
				} else {
					$status = osc_set_preference( osc_current_web_theme(), $form_data, 'lz_theme_options', 'STRING' );
				}

				$message = ( !$status )?
				array('status' => false, 'errors' => _m('Could not save to database.', 'lz_theme_options') ) :
				array('status' => true, 'message' => _m('Theme options updated!', 'lz_theme_options') );

				die( json_encode( $message ) );
			}
			die( json_encode( array('status' => false, 'message' => _m('There were some errors in the form.', 'lz_theme_options'), 'errors' => $errors ) ) );
		}

		die( json_encode( array('status' => false, 'errors' => _m('No forms found.', 'lz_theme_options') ) ) );
	}

	/**
	 * Validates form post values
	 */
	public function validate(){
		return $this->form->validate( Params::getParamsAsArray() );
	}

	/**
	 * Resets the form to it's default values
	 */
	public function resetOptions(){		
		$ip = ( lzto_isDemo() )? DEMO_USER_IP : null;
		$rs = OSCLztoModel::newInstance()->resetDb($ip);
		
		if( $rs ){
			$path = ( lzto_isDemo() )? LZO_DEMO_USER_PATH : LZO_UPLOAD_PATH;
			if( file_exists($path)){
				osc_deleteDir($path);
			}
			return true;
		}
		return false;
	}

	/*********************************************************************
	 * AJAX UPLOADING CRUD FUNCTIONS
	**********************************************************************/
	/**
	 * Saves new uploaded files
	 */
	public function saveUpload(){
		$result = UploadHelper::saveFile();
		die( htmlspecialchars( json_encode($result), ENT_NOQUOTES) );
	}

	/**
	 * Delete previus uploaded files
	 */
	public function deleteUpload(){
		$filename = Params::getParam('field_name');
		$group    = Params::getParam('group');
		$uuid     = Params::getParam('qquuid');
		$success  = UploadHelper::delete( $filename, $group, $uuid );
		die( json_encode( array( 'success' => $success, 'uuid' => $uuid, 'deletedFile' => $filename ) ) );
	}

	/**
	 * get all uploads for the current template
	 */
	public function getAllUploadFiles(){
		return UploadHelper::getFiles();
	}

	/**
	 * Ajax funtion to load existing uploaded files
	 */
	protected function getUploadFileByName($field_name){
		$result = UploadHelper::getFileByName( $field_name );
		return $result;
	}

	/**
	 * Ajax funtion to load existing uploaded files in json format
	 */
	public function getUploadFilesAsJson(){
		$result = UploadHelper::getFilesAsJson();
		die( json_encode( $result ) );
	}




	/**********************************************************************
	 * RENDERING FUNCTIONS
	**********************************************************************/
	/**
	 * Render the form fields given it�s name
	 *
	 * @param string $field Name of the field
	 * @param string $parent Name of the field parent
	 * @param string $group Name of the field group
	 * @return boolean|string false | field row html
	 */
	public function renderField( $name, $parent, $group = null ){
		if( $this->options->fieldExists( $name, $parent, $group ) ){
			Lib\LZForm::getInstance( $parent )->setGroup($parent);
			return Lib\LZForm::getInstance( $parent )->renderRow($name);
		}
		return false;
	}

	/**
	 * Open form for rendering
	 */
	public function openForm(){
		return $this->form->openForm();
	}

	/**
	 * Close form for rendering
	 */
	public function closeForm(){
		return $this->form->closeForm();
	}


	/**********************************************************************
	 * SYSTEM FUNCTIONS
	**********************************************************************/
	/**
	 * Install LZTO
	 */
	public function install(){
		return OSCLztoModel::newInstance()->install();
	}

	/**
	 * Uninstall LZTO
	 */
	public function uninstall(){
		return OSCLztoModel::newInstance()->uninstall();
	}

	
	/**********************************************************************
	 * PRESET RELATED
	**********************************************************************/
	
	/**
	 * Getters the uploaded content and zip the new preset on the
	 * destination dir
	 * 
	 * @param strng $json Path to the json config file
	 * @param string $source Path to the source dir
	 * @param string $preset_name Name of the new preset
	 * @return boolean True|False 
	 */
	protected function zipPreset( $json, $source, $preset_name = null )
	{
		if( is_null($preset_name) ){
			return false;
		}
		$preset_name = strtolower( implode('_', explode(' ', $preset_name) ) );
		//$this->log('PRESET NAME '.$preset_name);
		
		//$this->log('PRESETS PATH EXISTS '.file_exists(LZO_PRESETS_PATH));
		if( !file_exists(LZO_PRESETS_PATH) ){
			mkdir(LZO_PRESETS_PATH);
		}
		
		$destination = LZO_PRESETS_PATH.'preset-'.$preset_name.'.zip';
		//$this->log('DESTINATION PATH '.$destination);
		
		//$this->log('JSON PATH '.$source.'preset.json');
		//$this->log('JSON EXISTS "'.file_exists( $source.'preset.json' ).'"');
		if( file_exists( $source.'preset.json' ) ){
			unlink($source.'preset.json');
		}
		
		//$this->log('EXTENTION CHECK "'.(extension_loaded('zip') === true).'"');
		$file_put_contents = file_put_contents($source.'preset.json', $json);
		//$this->log('PUT CONTENTS CHECK "'.($file_put_contents).'"');
	    if ( extension_loaded('zip') === true &&  $file_put_contents )
	    {
	        if (file_exists($source) === true)
	        {
	            $zip = new ZipArchive();
				
	            $open = $zip->open($destination, ZIPARCHIVE::CREATE);
	            $this->log('ZIP CREATED "'.($open).'"');
	            
	            if ( $open === true)
	            {
	                $source = realpath($source);
	                $this->log('REAL SOURCE PATH "'.($source).'"');
	                
	                
	                if (is_dir($source) === true)
	                {
	                    $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST );
;
	                    $i =  0;
	                    foreach ($files as $name => $file)
	                    {
	                    	$file =  $file->getRealPath();
	                        $is_root = str_replace($source, '', $file );
	                        
	                        if( !empty($is_root) && strlen($source) < strlen($file) ){
								$this->log('CURRENT FILE = '.$file);
								$this->log('FILE EXISTS FILE = '.file_exists($file));
		                        if (is_dir($file) === true)
		                        {
		                        	$dir = $is_root;
		                        	if( empty($dir)){
		                        	} else {
		                        		$d = str_replace(DIRECTORY_SEPARATOR,'',$dir);
		                        		$this->log('EMPTY DIR = '.$d);
		                        		$rs = $zip->addEmptyDir( $d );
		                        	}
		                        }
								
		                        else if (is_file($file) === true)
		                        {
		                        	
		                        	$new_file = str_replace($source.DIRECTORY_SEPARATOR, '', $file);
		                        	$content = file_get_contents($file);
		                        	$this->log('FILE NAME = '.$new_file);
		                        	$this->log('FILE SIZE = '.strlen($content));
		                            $rs = $zip->addFromString($new_file, $content );
		                            $this->log('FILE ADDED = '.$rs);
		                        }
		                        
	                        }
	                        $i++;
	                    }
	                }
	
	                else if (is_file($source) === true)
	                {
	                    $zip->addFromString(basename($source), file_get_contents($source));
	                }
	                
	                $rs = $zip->close();
	                $this->log('ZIP CLOSED = '.$rs);
	                return $rs;
	            }
	        }
	    }
	
	    return false;
	}
	
	/**
	 * Log to error.log
	 * @param unknown $msg
	 */
	protected function log( $msg )
	{
		$fd = fopen( $this->log_file, "a+" );
		$str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
		fwrite($fd, $str . "\r\n\r\n");
		fclose($fd);
	}
}