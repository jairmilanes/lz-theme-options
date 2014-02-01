<?php
require dirname(__FILE__)."/lib/Field.php";
require dirname(__FILE__)."/lib/Field/BaseOptions.php";
require dirname(__FILE__)."/lib/Field/Options.php";
require dirname(__FILE__)."/lib/Field/MultipleOptions.php";
require dirname(__FILE__)."/lib/Field/Text.php";
require dirname(__FILE__)."/lib/LZForm.php";
require dirname(__FILE__)."/lib/Useful.php";
require dirname(__FILE__)."/helpers/options.helper.php";
require dirname(__FILE__)."/helpers/upload.helper.php";



class Builder {

	/**
	 * It references to self object: ModelProducts.
	 * It is used as a singleton
	 *
	 * @access private
	 * @since 3.0
	 * @var ModelProducts
	 */
	private static $instance;

	protected $form;
	protected $options;
	protected $values;
	protected $forms = array();
	protected $groups = array();
	protected $default_values;
	protected $ajax_upload_fields = array();

	public function __construct(){
		$this->form =  Lib\LZForm::getInstance('lzto', osc_admin_base_url(true), true, 'POST' );
		$this->fields = array();
		$this->default_values = array();
	}

	/**
	 * It creates a new ModelProducts object class ir if it has been created
	 * before, it return the previous object
	 *
	 * @access public
	 * @since 3.0
	 * @return ModelProducts
	 */
	public static function newInstance(){
		if( !self::$instance instanceof self ) {
			self::$instance = new self;
		}
		return self::$instance ;
	}


	/***********************************************************************
	 * FIELDS SETUP FUNCTIONS
	**********************************************************************/
	public function setOptions( array $options ){
		
		$data    = osc_get_preference( osc_current_web_theme(), 'lz_theme_options' );
		if( !empty($data)){
			$data = unserialize($data);
		}
		//printR($data,true);
		//Session::newInstance()->_set('ajax_files', null);
		$this->options = new OptionsHelper( $options, $data );

		if( !empty( $data ) ){
			$forms = $this->form->getAllInstances();
			
			//printR($forms,true);
			
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
		return '';//$this->options->getOption( $field, $group );
	}

	public function getGroupName( $group ){
		return $this->options->getGroupName( $group );
	}

	public function getOptionsByGroupName( $parent ){
		return $this->options->getOptionsByGroupName($parent);
	}

	public function hasOption( $form, $field ){
		$val = Lib\LZForm::getInstance($form)->getFieldValue($field);
		return ( !empty( $val )? $val : false );
	}
	
	/***********************************************************************
	 * ACTION METHODS
	**********************************************************************/

	/**
	 * Saves theme options values
	 */
	public function save(){

		$params = Params::getParam('lzto');
		$forms = $this->form->getAllInstances();

		//printR( $params );
		
		if ( count( $forms ) > 0 ){
			$data   = array();
			$errors = array();
			foreach( $forms as $parent => $form ){
				$name = $form->getName();
				$group = $form->getGroup();
				
				if( isset( $params[$parent] ) ){
					$pars = array_filter( $params[$parent] );
					if( !empty($pars) ){
						$isValid = $form->validate( array( $name => $pars ), true );
						if(!$isValid){
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
			}

			if( count($errors) == 0 ){
				$form_data = serialize( $data );
				$status = osc_set_preference( osc_current_web_theme(), $form_data, 'lz_theme_options', 'STRING' );
				$message = ( !$status )?
				array('status' => false, 'errors' => _m('Could not sav to database.', 'lz_theme_options') ) :
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
		if( count( $this->default_values ) > 0 ){

			$data = array();
			$data[ $this->form->getName() ] = $this->default_values;

			$isValid = $this->form->validate( $data, true );

			if( $isValid ){

				$data = serialize( $isValid );
				$status = osc_set_preference( osc_current_web_theme(), $data, 'lz_theme_options', 'STRING' );

				$message = ( !$status )?
				array('status' => false, 'errors' => _m('A error ocurred, could not save options.','lz_theme_options') ) :
				array('status' => true, 'message' => _m('Theme options reset completed succecifully!','lz_theme_options') );

				die( json_encode( $message ) );

			}

			$message = array('status' => false, 'message' => _m('Ops! Default velues are not valid!','lz_theme_options') );
			die( json_encode( $message ) );

		} else {

			$this->default_values = array();
			osc_delete_preference( osc_current_web_theme(), 'lz_theme_options' );
			$message = array('status' => true, 'message' => _m('Theme options reset completed succecifully!','lz_theme_options') );
			die( json_encode( $message ) );

		}
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
		$result = UploadHelper::deleteFile();
		die( json_encode( $result ) );
	}

	/**
	 * get all uploads for the current template
	 */
	public function getAllUploadFiles(){
		return UploadHelper::getFiles( $this->ajax_upload_fields );
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
	public function install(){

	}

	public function uninstall(){

	}

}