<?php
require dirname(__FILE__)."/lib/Field.php";
require dirname(__FILE__)."/lib/Field/BaseOptions.php";
require dirname(__FILE__)."/lib/Field/Options.php";
require dirname(__FILE__)."/lib/Field/MultipleOptions.php";
require dirname(__FILE__)."/lib/Field/Text.php";
require dirname(__FILE__)."/lib/LZForm.php";
require dirname(__FILE__)."/lib/Useful.php";

//
use \Lib;

//echo '<pre>'.print_r( get_declared_classes(), true ).'</pre>';exit;
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
	protected $fields;
	protected $data;
	protected $forms = array();
	protected $groups = array();
	protected $default_values;
	protected $ajax_upload_fields = array();

	public function __construct(){
		$this->form =  Lib\LZForm::getInstance('LZOptionsFramework', osc_admin_base_url(true), true, 'POST' );
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
	/**
	 * Set theme options
	 */
	public function setOptions( array $options ){

		$this->options = $options;
		$data = array();
		$data = osc_get_preference( osc_current_web_theme(), 'lz_theme_options' );

		if( !empty($data) ){
			$data = unserialize( $data );
			$this->form->addData( $data );
		}

		foreach( $this->options as $group_slug => $group ){
			$this->groups[$group_slug] = $group['title'];
			$this->setOptionsField( $group['fields'], $group_slug );
		}
		//printR( $this->form->getAllInstances(), true );
		//printR( $this->fields, true );
		//printR( $this->groups, true );
		if( count( $this->fields ) > 0 ){
			return true;
		}
		return false;
	}

	protected function setOptionsField( $fields, $group_slug, $group_parent = null ){
		$files = array();
		foreach( $fields as $title => $options ){
			$method = '';
			switch( $options['type'] ){
				case 'text':
				case 'textarea':
				case 'email':
				case 'number':
				case 'file':
				case 'url':
					$method = 'setOptionTypeText';
					break;
				case 'radio':
				case 'checkbox':
				case 'select':
				case 'multipleSelect':
				case 'colorSelector':
					$method = 'setOptionTypeOptions';
					break;
				case 'ajaxFile':
					$this->ajax_upload_fields[] = $title;
					$t = $this->getUploadFileByName($title);
					$files[$title] = $t;
					$method = 'setOptionTypeAjaxFile';
					break;
				case 'colorpicker':
					$method = 'setOptionTypeColorpicker';
					break;
				case 'password':
					$method = 'setOptionTypePassword';
					break;
				case 'fieldGroup':
					$subgroup = '';
					$method = 'setOptionTypeFieldGroup';

					$options['options']['action'] = 'open';
					if( method_exists( $this, $method ) ){
						$this->$method( $options['type'], $title, $options['options'], $title);
					}

					$this->setOptionsField( $options['fields'], $title, $group_slug );

					$options['options']['action'] = 'close';
					if( method_exists( $this, $method ) ){
						$this->$method( $options['type'], $title.'_close', $options['options'], $title );
						$this->addField( $title.'_close', $title,  $group_slug );
					}
					$method = '';
					break;
			}

			if( !isset($options['options']) || !is_array($options['options'])){
				$options['options'] = array();
			}

			if( method_exists( $this, $method ) ){

				$this->$method( $options['type'], $title, $options['options'], $group_slug, $group_parent );

				$this->addField( $title, $group_slug,  $group_parent );

				$results = array();
				if( $options['type'] !== 'fieldGroup' ){

					if( isset($files[$title]) ){
						$data[$title] = $files[$title];
						unset( $files[$title] );
					}

					if( !is_null( $group_parent ) ){
						if( !isset($this->data[$group_parent]) ){
							$this->data[$group_parent] = array();
						}
						if( !isset($this->data[$group_parent][$group_slug]) ){
							$this->data[$group_parent][$group_slug] = array();
						}
						$this->data[$group_parent][$group_slug][$title] = $data[$title];

					} else {
						if( !isset( $this->data[$group_slug] ) ){
							$this->data[$group_slug] = array();
						}
						$this->data[$group_slug][$title] = $data[$title];
					}

					if( isset( $options['default'] ) ){
						$this->addDefaultValue($title, $options['default'],$group_slug, $group_parent );
					}

					if( isset( $options['description'] ) ){
						$this->form->addDescription( $title, $options['description'] );
					}
				}
			}
		}
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
	public function getFields(){
		return $this->fields;
	}

	/**
	 * Gets a single field value
	 *
	 * @param string $field Name of the field
	 */
	public function getOption( $field ){
		return osc_esc_html( $this->form->getFieldValue( $field ) );
	}

	/**
	 * Gets the group name by it´s slug name
	 *
	 * @param string $group_slug Slug of the group
	 */
	public function getGroupName($group_slug){
		return ( isset($this->groups[$group_slug])? $this->groups[$group_slug] : '' );
	}

	/**
	 * Get all options inside a given group
	 *
	 * @param string $group_slug Slug of the group
	 */
	public function getOptionsByGroupName($group_slug){
		return ( ( array_key_exists($group_slug, $this->data))? array_filter( $this->data[$group_slug] ) : array()  );
	}

	/***********************************************************************
	 * FIELDS SETUP FUNCTIONS
	**********************************************************************/
	/**
	 * Add a field to the field list
	 */
	protected function addField( $field, $group_slug,  $group_parent = null ){

		if( !is_null($group_parent) ){

			if( !isset( $this->fields[$group_parent] ) ){
				$this->fields[$group_parent] = array();
			}
			if( !isset( $this->fields[$group_parent][$group_slug] ) ){
				$this->fields[$group_parent][$group_slug] = array();
			}
			$this->fields[$group_parent][$group_slug][] = $field;

		} else {

			if( !isset( $this->fields[$group_slug] ) ){
				$this->fields[$group_slug] = array();
			}

			$this->fields[$group_slug][] = $field;
		}
		return true;
	}

	protected function addDefaultValue( $field, $value, $group_slug, $parent_slug = null ){
		if( !is_null($parent_slug) ){
			if( !isset($this->default_values[$parent_slug] ) ){
				$this->default_values[$parent_slug] = array();
			}
			if( !isset($this->default_values[$parent_slug][$group_slug])){
				$this->default_values[$parent_slug][$group_slug] = array();
			}
			$this->default_values[$parent_slug][$group_slug][$field] = $value;
		} else {
			if( !isset($this->default_values[$group_slug])){
				$this->default_values[$group_slug] = array();
			}
			$this->default_values[$group_slug][$field] = $value;
		}
	}


	/**
	 * Adds a text field to the form
	 */
	protected function setOptionTypeText( $type, $title, array $data, $group_slug, $group_parent = null ){
		//$form = $this->getSubForm( $group_slug );
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
			'id'			=> 'field_'.strtolower( $title ),
		    'class' 		=> 'text_field '.@$data['class'],
			'required' 		=> @$data['required'],
			'label'			=> @$data['label'],
		    'max_length' 	=> @$data['max_length'],
			'min_length' 	=> @$data['min_length'],
			'value'			=> @$data['value'],
			'placeholder'	=> @$data['placeholder']
		));
	}

	/**
	 * Adds a text field to the form
	 */
	protected function setOptionTypeAjaxFile( $type, $title, array $data, $group_slug, $group_parent = null ){
		//$form = $this->getSubForm( $group_slug );
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
			'id'			=> 'field_'.strtolower( $title ),
			'class' 		=> 'text_field '.@$data['class'],
			'required' 		=> @$data['required'],
			'label'			=> @$data['label'],
			'value'			=> @$data['value']
		));
	}

	/**
	 * Creates a colorpicker field instance
	 *
	 * @param string $type
	 * @param string $title
	 * @param array $data
	 */
	protected function setOptionTypeColorpicker( $type, $title, array $data, $group_slug, $group_parent = null ){
		$data['id'] = 'colorpicker_id'; // id of the field * only used internally
		$data['class'] = 'colorpicker colorpicker_class'; // class of the field * only used internally
		$this->setOptionTypeText( 'text', $title, $data, $group_slug, $group_parent );

	}

	/**
	 * Creates a select, checkbox, radio type of field
	 *
	 * @param string $type
	 * @param string $title
	 * @param array $data
	 */
	protected function setOptionTypeOptions( $type, $title, array $data, $group_slug, $group_parent = null ){
		//$form = $this->getSubForm( $group_slug );
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
			'id'			=> 'field_'.strtolower( $title ),
			'class' 		=> 'text_field',
			'required' 		=> @$data['required'],
			'label'			=> @$data['label'],
			'false_values'  => array(),
			'value'			=> @$data['value'],
			'choices'       => @$data['choices'],
			'false_values'  => @$data['false_values'],
			'option_size'   => @$data['option_size']
		));
	}

	protected function setOptionTypeFieldGroup( $type, $title, array $data, $group_slug, $group_parent = null ){
		//$form = $this->getSubForm( $group_slug );
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
				'id'			=> 'field_'.strtolower( $title ),
				'class' 		=> 'text_field',
				'title'			=> @$data['title'],
				'action'  		=> @$data['action'],
		));
	}

	/***********************************************************************
	 * OPTIONS CRUD FUNCTIONS
	**********************************************************************/

	/**
	 * Saves theme options values
	 */
	public function save(){

		$isValid = $this->form->validate( Params::getParamsAsArray(), true );
		if( !$isValid ){
			die( json_encode( array('status' => false, 'message' => _m('There were some errors in the form.', 'lz_theme_options'), 'errors' => $this->form->getErrors() ) ) );
		}

		$data = serialize( $isValid );
		$status = osc_set_preference( osc_current_web_theme(), $data, 'lz_theme_options', 'STRING' );

		$message = ( !$status )?
				array('status' => false, 'errors' => _m('Could not sav to database.', 'lz_theme_options') ) :
				array('status' => true, 'message' => _m('Theme options updated!', 'lz_theme_options') );

		die( json_encode( $message ) );

	}

	/**
	 * Validates form post values
	 */
	public function validate(){
		return $this->form->validate( Params::getParamsAsArray() );
	}


	/***********************************************************************
	 * AJAX UPLOADING CRUD FUNCTIONS
	**********************************************************************/

	/**
	 * Saves new uploaded files
	 */
	public function saveUpload(){

		$result = UploadHelper::saveFile();
		die( htmlspecialchars( json_encode($result), ENT_NOQUOTES) );


		$files = Session::newInstance()->_get('ajax_files');
		$uid = Params::getParam('qquuid');
		if( !isset($files[$uid]) ){

			$field_name = Params::getParam('field_name');

			// Include the uploader class
			require_once(LIB_PATH."AjaxUploader.php");
			$uploader = new AjaxUploader();
			$original = pathinfo($uploader->getOriginalName());
			$filename = uniqid("qqfile_").".".$original['extension'];
			$path = osc_content_path().'uploads/lz_theme_options';

			if( !file_exists( $path ) ){
				mkdir($path);
			}

			$result = $uploader->handleUpload( $path.'/'.$filename );

			if( isset( $result['success'] ) && $result['success'] == true ){

				$existing_file = false;
				$db_file = osc_get_preference( osc_current_web_theme().'_'.$field_name, 'lz_theme_options_uploads');
				if( !empty($db_file) ){
					$f = explode('||', $db_file );
					if( file_exists($path.'/'.$f[1]) ){
						@unlink( $path.'/'.$f[1] );
						@unlink( $path.'/thumbnails/'.$f[1] );
					}
					osc_delete_preference(osc_current_web_theme().'_'.$field_name, 'lz_theme_options_uploads');
				}

				$resize = ImageResizer::fromFile( $path.'/'.$filename );
				$resize->resizeTo( 250, 150, true );
				if( !file_exists( $path.'/thumbnails' ) ){
					mkdir($path.'/thumbnails');
				}
				$resize->saveToFile($path.'/thumbnails/'.$filename);
				$result['thumbnailUrl'] = osc_uploads_url( 'lz_theme_options/thumbnails/'.$filename );

				osc_set_preference( osc_current_web_theme().'_'.$field_name, $uid.'||'.$filename, 'lz_theme_options_uploads', 'STRING' );

			}

			$result['uploadName'] = $filename;

		} else {
			$result = array('success' => false, 'message' => _m('File already exists, try another file or change the filename.','lz_theme_options') );
		}

		die( htmlspecialchars(json_encode($result), ENT_NOQUOTES) );
	}

	/**
	 * Delete previus uploaded files
	 */
	public function deleteUpload(){

		$field_name = Params::getParam('field_name');
		$uid = Params::getParam('qquuid');
		$files = Session::newInstance()->_get('ajax_files');

		$success = false;
		$filename = '';

		if(isset($files[$uid]) && $files[$uid]!='') {
			$filename = $files[$uid];
			$success = @unlink( osc_content_path().'uploads/lz_theme_options/'.$filename );
			$success = @unlink( osc_content_path().'uploads/lz_theme_options/thumbnails/'.$filename );
			if( false !== $success ){
				osc_delete_preference( osc_current_web_theme().'_'.$field_name, 'lz_theme_options_uploads');
			}
		}

		Session::newInstance()->_set('ajax_files', $files);

		die( json_encode(array('success' => $success, 'deletedFile' => $filename) ) );
	}

	/**
	 * get all uploads for the current template
	 */
	public function getAllUploadFiles(){
		Preference::newInstance()->dao->select();
		Preference::newInstance()->dao->from( Preference::newInstance()->getTableName() );
		Preference::newInstance()->dao->where( 's_section', 'lz_theme_options_uploads' );
		$i = 0;
		$results = array();
		if( count($this->ajax_upload_fields) > 0 ){
			foreach( $this->ajax_upload_fields as $field ){
				if( $i == 0 ){
					Preference::newInstance()->dao->where( 's_name', osc_current_web_theme().'_'.$field );
				} else {
					Preference::newInstance()->dao->orWhere( 's_name', osc_current_web_theme().'_'.$field );
				}
				$i++;
			}
			$files = Preference::newInstance()->dao->get();
			// printR($files, true);
			if( $files->numRows() > 0 ){
				foreach( $files->resultArray() as $file ) {
					var_dump($file);
					$field = str_replace( osc_current_web_theme().'_', '', $file['s_name'] );
					$results[$field] = $file;
				}
			}
		}
		//exit;
		return $results;
	}

	/**
	 * Ajax funtion to load existing uploaded files
	 */
	protected function getUploadFileByName($field_name){
		$results 	= array();
		$file 		= osc_get_preference( osc_current_web_theme().'_'.$field_name, 'lz_theme_options_uploads' );
		if( !empty( $file ) ){
			$path = osc_content_path().'uploads/lz_theme_options/';
			$f = explode( '||', $file );
			$uid = $f[0];
			$filename = $f[1];
			$results['name'] = $filename;
			$results['uuid'] = $uid;
			$results['size'] = $this->human_filesize( filesize( $path.$filename ) );
			$results['url']  = osc_uploads_url( 'lz_theme_options/'.$filename );
			$results['thumbnailUrl'] = osc_uploads_url( 'lz_theme_options/thumbnails/'.$filename );
		}
		return $results;
	}

	/**
	 * Ajax funtion to load existing uploaded files in json format
	 */
	public function getUploadFilesAsJson(){
		if( Params::existParam('field_name') ){
			$field_name = Params::getParam('field_name');
			$results = $this->getUploadFileByName( $field_name );
			$files = Session::newInstance()->_get('ajax_files');
			if( empty($files)){ $files = array(); }
			$files[$results['uuid']] = $results['name'];
			Session::newInstance()->_set('ajax_files', $files);
			die( json_encode( array( $field_name => $results ) ) );
		}
		die( json_encode( array( 'status' => false ) ) );
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

	/***********************************************************************
	 * RENDERING FUNCTIONS
	 **********************************************************************/

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

	/**
	 * Render the form fields given it�s name
	 *
	 * @param string $field Name of the field
	 * @param string $parent Name of the field parent
	 * @param string $group Name of the field group
	 * @return boolean|string false | field row html
	 */
	public function renderField( $name, $parent, $group = null ){
		if( $this->fieldExists( $name, $parent, $group ) ){
			return Lib\LZForm::getInstance( $parent )->renderRow($name);
		}
		return false;
	}

	public function install(){

	}

	public function uninstall(){

	}

	/***********************************************************************
	 * HELPER FUNCTIONS
	**********************************************************************/
	/**
	 * Check if a given field exists
	 *
	 * @param string $field Name of the field
	 * @param string $parent Name of the field parent
	 * @param string $group Name of the field group
	 * @return boolean
	 */
	protected function fieldExists( $field, $parent, $group = null ){
		if( !is_null($group) ){
			if( !isset( $this->fields[$group][$parent] )){
				return false;
			}
			return (  in_array( $field, $this->fields[$group][$parent] ) );
		}
		return ( array_key_exists( $field, $this->fields[$parent] ) );
	}


}