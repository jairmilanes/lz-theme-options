<?php
/**
 * Theme options - Options helper
 *
 * @author Jmilanes
 */
class OptionsHelper {

	//protected $data				  = array();
	protected $groups             = array();
	protected $fields             = array();
	protected $db_data			  = array();
	protected $uploaded_files 	  = array();
	protected $default_values  	  = array();

	public function __construct( $options, $data = null ){
		$this->options = $options;
		if( !empty($data) ){
			$this->db_data = $data;
		}
		return $this->setOptions();
	}

	/**
	 * Set options
	 */
	protected function setOptions(){
		foreach( $this->options as $group_slug => $group ){
			$this->groups[$group_slug] = $group['title'];
			$this->setOptionsField( $group['fields'], $group_slug );
		}
		return;
	}

	/**
	 * Set field options
	 *
	 * @param array $fields
	 * @param string $group_slug
	 * @param string $group_parent
	 */
	protected function setOptionsField( $fields, $group_slug, $group_parent = null ){
		$files = array();
		foreach( $fields as $title => $options ){
			$method = '';

			if( !isset($options['options']) || !is_array($options['options'])){
				$options['options'] = array();
			}
			$method = $this->findFieldType( $title, $options, $group_slug, $group_parent );

			if( method_exists( $this, $method ) ){

				if( !empty($group_parent) ){
					Lib\LZForm::getInstance( $group_slug )->setGroup($group_parent);
				}

				$this->$method( $options['type'], $title, $options['options'], $group_slug, $group_parent );

				$this->addToFields( $title, $group_slug,  $group_parent );

				$results = array();
				if( $options['type'] !== 'fieldGroup' ){
					$this->checkForFiles( $title, $group_slug, $group_parent );
				}

				if( isset( $options['default'] ) ){
					$this->addToDefaults($title, $options['default'], $group_slug, $group_parent );
				}

				if( isset( $options['description'] ) ){
					Lib\LZForm::getInstance( $group_slug )->addDescription( $title, $options['description'] );
				}
			}
		}
		return;
	}

	/**
	 * Set the field type
	 *
	 * @param string $title
	 * @param string $options
	 * @param string $group_slug
	 * @param string $group_parent
	 * @return string
	 */
	protected function findFieldType( $title, $options, $group_slug, $group_parent = null ){

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
			case 'textureSelector':
			case 'countrySelector':
			case 'regionSelector':
			case 'citySelector':
			case 'countrySelector':
			case 'googleFont':
				$method = 'setOptionTypeOptions';
				break;
			case 'slideRange':
				$method = 'setOptionTypeSlideRange';
				break;
			case 'ajaxFile':
				$t =  UploadHelper::getFileByName( $title, $group_slug );
				if( !empty($t) ){
					$this->addToUploaded( $title, $t, $group_slug, $group_parent );
				}
				$method = 'setOptionTypeAjaxFile';
				break;
			case 'colorpicker':
				$method = 'setOptionTypeColorpicker';
				break;
			case 'password':
				$method = 'setOptionTypePassword';
				break;
			case 'fieldGroup':
				$method = 'setOptionTypeFieldGroup';
				$options['options']['action'] = 'open';
				if( method_exists( $this, $method ) ){
					$this->$method( $options['type'], $title, $options['options'], $title);
					$this->addToFields( $title, $title,  $group_slug );
				}

				$this->setOptionsField( $options['fields'], $title, $group_slug );

				$options['options']['action'] = 'close';
				if( method_exists( $this, $method ) ){
					$this->$method( $options['type'], $title.'_close', $options['options'], $title );
					$this->addToFields( $title.'_close', $title,  $group_slug );
				}
				$method = '';
				break;
		}

		return $method;
	}

	/**
	 * Check if field has a file associated
	 *
	 * @param string $field
	 * @param string $group_slug
	 * @return multitype:
	 */
	protected function checkForFiles( $field, $group_slug, $group_parent ){
		$isValid = $this->fileExists( $field, $group_slug, $group_parent );
		if( !empty($isValid) ){
			if( !empty($group_parent) ){
				$this->db_data[$group_parent][$group_slug][$field] = $isValid;
			} else {
				$this->db_data[$group_slug][$field] = $isValid;
			}
		}
		return $this->db_data;
	}

	/**
	 * Add field data
	 *
	 * @param string $title
	 * @param string $group_slug
	 * @param string $group_parent
	 * @return multitype:

	protected function addToData( $title, $group_slug, $group_parent = null ){
		$this->data = $this->addData( $this->data, @$this->db_data[$title], $title, $group_slug, $group_parent );
		return $this->data;
	}
	*/

	/**
	 * Add default field value
	 *
	 * @param string $field
	 * @param string $value
	 * @param string $group_slug
	 * @param string $parent_slug
	 */
	protected function addToDefaults( $field, $value, $group_slug, $parent_slug = null ){
		$this->default_values = $this->addData( $this->default_values, $value, $field, $group_slug, $parent_slug );
	}

	/**
	 * Add default field value
	 *
	 * @param string $field
	 * @param string $value
	 * @param string $group_slug
	 * @param string $parent_slug
	 */
	protected function addToUploaded( $field, $value, $group_slug, $parent_slug = null ){
		$this->uploaded_files = $this->addData( $this->uploaded_files, $value, $field, $group_slug, $parent_slug );
	}

	/**
	 * Generic data add method
	 *
	 * @param string $data
	 * @param string $value
	 * @param string $title
	 * @param string $group_slug
	 * @param string $group_parent
	 * @return unknown
	 */
	protected function addData( $data, $value, $title, $group_slug, $group_parent = null ){
		if( !is_null( $group_parent ) ){
			if( !isset($data[$group_parent]) ){
				$data[$group_parent] = array();
			}
			if( !isset($data[$group_parent][$group_slug]) ){
				$data[$group_parent][$group_slug] = array();
			}
			$data[$group_parent][$group_slug][$title] = $value;
		} else {
			if( !isset( $data[$group_slug] ) ){
				$data[$group_slug] = array();
			}
			$data[$group_slug][$title] = $value;
		}

		return $data;
	}

	/**
	 * Add a field to the field list
	 *
	 * @param string $field
	 * @param string $group_slug
	 * @param string $group_parent
	 * @return array:
	 */
	protected function addToFields( $field, $group_slug,  $group_parent = null ){
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
		return $this->fields;
	}

	/**
	 * Gets the group name by it's slug name
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
		return ( ( array_key_exists($group_slug, $this->db_data))? array_filter( $this->db_data[$group_slug] ) : array()  );
	}

	/**
	 * Get options value
	 *
	 * @param string $field_name
	 * @return array|string
	 */
	public function getOption($field_name, $parent ){
		if( isset( $this->db_data[$parent][$field_name] ) ){
			return $this->db_data[$parent][$field_name];
		}
		return '';
	}

	/**
	 * Get fields names
	 *
	 * @param string $group
	 * @return array|boolean
	 */
	public function getFields($group = null){
		if( is_null( $group ) ){
			return $this->fields;
		}
		if( array_key_exists($group, $this->fields) ){
			return $this->fields[$group];
		}
		return false;
	}

	/**
	 * Check if a given field exists
	 *
	 * @param string $field Name of the field
	 * @param string $parent Name of the field parent
	 * @param string $group Name of the field group
	 * @return boolean
	 */
	public function fieldExists( $field, $parent, $group = null ){
		return Lib\LZForm::getInstance( $parent )->checkField($field);
	}

	/**
	 * Check if a given field exists
	 *
	 * @param string $field Name of the field
	 * @param string $parent Name of the field parent
	 * @param string $group Name of the field group
	 * @return boolean
	 */
	public function fileExists( $field, $parent, $group = null ){
		if( !empty($group) ){
			if( !isset($this->uploaded_files[$group]) ){
				return false;
			}
			if( !isset($this->uploaded_files[$group][$parent]) ){
				return false;
			}
			return ( (isset($this->uploaded_files[$group][$parent][$field]))? $this->uploaded_files[$group][$parent][$field] : false );
		} else {
			if( !isset($this->uploaded_files[$parent]) ){
				return false;
			}
			return ( (isset($this->uploaded_files[$parent][$field]))? $this->uploaded_files[$parent][$field] : false );
		}

		/*
		if( !is_null($group) ){
			if( !isset( $this->uploaded_files[$group][$parent] )){
				return false;
			}
			return ( in_array( $field, $this->uploaded_files[$group][$parent] ) )? $this->uploaded_files[$group][$parent][$field] : false;
		}
		if( !isset( $this->uploaded_files[$parent] )){
			return false;
		}
		return ( array_key_exists( $field, $this->uploaded_files[$parent] ) )? $this->uploaded_files[$parent][$field] : false;
		*/
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
	 * Adds a text field to the form
	 *
	 * @param string $type
	 * @param string $title
	 * @param array $data
	 * @param string $group_slug
	 * @param string $group_parent
	 */
	protected function setOptionTypeText( $type, $title, array $data, $group_slug, $group_parent = null ){

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
	 * Adds a slider range field to the form
	 *
	 * @param string $type
	 * @param string $title
	 * @param array $data
	 * @param string $group_slug
	 * @param string $group_parent
	 */
	protected function setOptionTypeSlideRange( $type, $title, array $data, $group_slug, $group_parent = null ){
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
				'id'			=> 'field_'.strtolower( $title ),
				'class' 		=> 'text_field '.@$data['class'],
				'required' 		=> @$data['required'],
				'label'			=> @$data['label'],
				'max_length' 	=> @$data['max_length'],
				'min_length' 	=> @$data['min_length'],
				'max' 			=> @$data['max'],
				'min' 			=> @$data['min'],
				'type' 			=> @$data['type'],
				'value'			=> @$data['value']
		));
	}

	/**
	 * Adds a text field to the form
	 *
	 * @param string $type
	 * @param string $title
	 * @param array $data
	 * @param string $group_slug
	 * @param string $group_parent
	 */
	protected function setOptionTypeAjaxFile( $type, $title, array $data, $group_slug, $group_parent = null ){
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
				'id'			=> 'field_'.strtolower( $title ),
				'class' 		=> 'text_field '.@$data['class'],
				'required' 		=> @$data['required'],
				'label'			=> @$data['label'],
				'value'			=> @$data['value']
		));
	}

	/**
	 * Creates a select, checkbox, radio type of field
	 *
	 * @param string $type
	 * @param string $title
	 * @param array $data
	 */
	protected function setOptionTypeOptions( $type, $title, array $data, $group_slug, $group_parent = null ){
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

	/**
	 * Creates a group type of field
	 *
	 * @param string $type
	 * @param string $title
	 * @param array $data
	 * @param string $group_slug
	 * @param string $group_parent
	 */
	protected function setOptionTypeFieldGroup( $type, $title, array $data, $group_slug, $group_parent = null ){
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
				'id'			=> 'field_'.strtolower( $title ),
				'class' 		=> 'text_field',
				'title'			=> @$data['title'],
				'action'  		=> @$data['action'],
		));
	}

}