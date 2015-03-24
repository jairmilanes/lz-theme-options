<?php
/**
 * Theme Options Class
 *
 * @author Jmilanes
 * @version 1.0
 */
class Options {

    /**
     * Groups array
     *
     * @var array
     */
	protected $groups             = array();

    /**
     * Fields array
     *
     * @var array
     */
	protected $fields             = array();

    /**
     * Database data array
     *
     * @var array|null
     */
	protected $db_data			  = array();

    /**
     * Uploaded files array
     *
     * @var array
     */
	protected $uploaded_files 	  = array();

    /**
     * Default values array
     *
     * @var array
     */
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
     *
     * @return bool
     */
	protected function setOptions(){
		foreach( $this->options as $group_slug => $group ){
			$this->groups[$group_slug] = $group['title'];
			$this->setOptionsField( $group['fields'], $group_slug );
		}

		if( empty($this->db_data) ){
			$this->db_data = $this->default_values;
		}
		
		return true;
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

				Lib\LZForm::getInstance( $group_slug )->setGroup( $group_parent );

				$this->$method( $options['type'], $title, $options['options'], $group_slug, $group_parent );

				$this->addToFields( $title, $group_slug,  $group_parent );

				if( $options['type'] !== 'fieldGroup' ){
					$this->checkForFiles( $title, $group_slug, $group_parent );
				}

				if( isset( $options['default'] ) && !empty($options['default']) ){
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
			case 'regionSelector':
			case 'citySelector':
            case 'citySelector':
            case 'countrySelector':
			case 'googleFont':
				$method = 'setOptionTypeOptions';
				break;
            case 'textureSelector':
                $method = 'setOptionTypeTextureSelector';
                break;
			case 'toggleSwitch':
				$method = 'setOptionTypeToggleSwitch';
				break;
			case 'slideRange':
				$method = 'setOptionTypeSlideRange';
				break;
			case 'ajaxFile':
				$t =  Uploader::getFileByName( $title, $group_slug );
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
	
	public function getDefaults(){
		return $this->default_values;
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
		return $this->setOptionTypeText( 'text', $title, $data, $group_slug, $group_parent );

	}

    /**
     * Creates a password input field
     *
     * @param $type
     * @param $title
     * @param array $data
     * @param $group_slug
     * @param null $group_parent
     * @return bool
     */
    protected function setOptionTypePassword( $type = 'password', $title, array $data, $group_slug, $group_parent = null ){
        return Lib\LZForm::getInstance( $group_slug )->addField( $title, 'password', array(
            'id'			=> 'field_'.strtolower( $title ),
            'class' 		=> 'text_field password '.@$data['class'],
            'required' 		=> @$data['required'],
            'label'			=> @$data['label'],
            'max_length' 	=> @$data['max_length'],
            'min_length' 	=> @$data['min_length'],
            'alphanumeric' 	=> @$data['alphanumeric'],
            'confirm' 	    => @$data['confirm'],
            'placeholder'	=> @$data['placeholder']
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
	protected function setOptionTypeText( $type, $title, array $data, $group_slug, $group_parent = null ){
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
				'id'			=> 'field_'.strtolower( $title ),
				'class' 		=> 'text_field '.@$data['class'],
				'required' 		=> @$data['required'],
				'label'			=> @$data['label'],
				'max_length' 	=> @$data['max_length'],
				'min_length' 	=> @$data['min_length'],
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
				'max' 			=> @$data['max'],
				'min' 			=> @$data['min'],
				'type' 			=> @$data['type'],
				'step'			=> @$data['step']
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
        $min_size = osc_thumbnail_dimensions();
        $min_size = explode('x',$min_size);
        $max_size = osc_normal_dimensions();
        $max_size = explode('x',$max_size);

		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, array(
            'id'			        => 'field_'.strtolower( $title ),
            'class' 		        => 'text_field '.lzto_var($data, 'class', ''),
            'required' 		        => lzto_var($data, 'required', false),
            'label'			        => lzto_var($data, 'label', ''),
            'type'			        => lzto_var($data, 'type', 'all'),
            'min-width'             => lzto_var($data, 'min-width', $min_size[0]),
            'min-height'            => lzto_var($data, 'min-height', $min_size[1]),
            'max-width'             => lzto_var($data, 'max-width', $max_size[0]),
            'max-height'            => lzto_var($data, 'max-height', $max_size[1]),
            'multiple'              => lzto_var($data, 'multiple', false),
            'max-files'             => lzto_var($data, 'max-files', 1),
            'max-size'              => lzto_var($data, 'max-size', osc_max_size_kb()),
            'upload-path'           => lzto_var($data, 'upload-path', LZO_UPLOAD_PATH ),
            'upload-thumb-path'     => lzto_var($data, 'upload-thumb-path', LZO_THUMB_PATH )
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
        $options = array_merge( array(
            'id'			=> 'field_'.strtolower( $title ),
            'required' 		=> false,
            'label'			=> 'Default label',
            'value'			=> null,
            'choices'       => array(),
            'false_values'  => array()
        ), $data);
        $options['class'] = @$options['class'].' options_field';
		return Lib\LZForm::getInstance( $group_slug )->addField( $title, $type, $options);
	}

    /**
     * Creates a texture select field
     *
     * @param $type
     * @param $title
     * @param array $data
     * @param $group_slug
     * @param null $group_parent
     * @return bool
     */
    protected function setOptionTypeTextureSelector($type, $title, array $data, $group_slug, $group_parent = null){
        if( !is_array($data['choices']) || empty($data['choices']) ){
            $path = WebThemes::newInstance()->getCurrentThemePath().$data['choices'];
            if( file_exists($path) ){
                $rel_dir = $data['choices'];
                $dir = osc_themes_path().osc_current_web_theme().'/'.$rel_dir;
                $allowed_extentions = array('png', 'jpeg', 'gif');
                $dir = new DirectoryIterator($dir);
                $data['choices'] = array();
                foreach( $dir as $file ){
                    if( $file->isFile() ){
                        if( in_array( strtolower($file->getExtension()), $allowed_extentions ) ){
                            $name = str_replace('.'.$file->getExtension(), '', $file->getFilename());
                            $data['choices'][$name] = $rel_dir.'/'.$file->getFilename();
                        }
                    }
                }
            }
        }
        return $this->setOptionTypeOptions($type, $title, $data, $group_slug, $group_parent);
    }

	/**
	 * Creates a toggleSwtch type of field
	 *
	 * @param string $type
	 * @param string $title
	 * @param array $data
	 */
	protected function setOptionTypeToggleSwitch( $type = 'checkbox', $title, array $data, $group_slug, $group_parent = null ){
		$data['class'] = 'toggleSwitch';
        $data['choices'] = array(1=>'');
		$this->setOptionTypeOptions('checkbox', $title, $data, $group_slug, $group_parent);
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