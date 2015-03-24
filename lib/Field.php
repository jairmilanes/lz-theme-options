<?php
namespace Lib;
use Lib\Utils;

/**
 * Class Field
 *
 * @package Lib
 * @author Jair Milanes Junior
 * @version 1.0
 */
abstract class Field
{

    public $custom_error = array();
    protected $form;
    public $html = array(
        'open_field' => false,
        'close_field' => false,
        'open_html' => false,
        'close_html' => false
    );

    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * Return the current field, i.e label and input
     */
    abstract public function returnField($form_name, $name, $value = '', $group = '' );

    /**
     * Validate the current field
     */
    abstract public function validate($val);

    /**
     * Apply custom error message from user to field
     */
    public function errorMessage($message)
    {
        $this->custom_error[] = $message;
    }

    /**
     * Get an attribute
     *
     * @param $name
     * @return int
     */
    public function getAttribute($name){
        if( isset($this->attributes[$name])){
            return $this->attributes[$name];
        }
        return -1;
    }

    /**
     * Check if is required
     *
     * @return bool
     */
    public function isRequired(){
        return (isset( $this->required )? (bool)$this->required : false );
    }

    /**
     * Builds data attributes string
     *
     * @return string
     */
    public function getDataAttributes(){
        $attr = '';
        if( isset($this->attributes['data']) && is_array($this->attributes['data']) ){
            foreach($this->attributes['data'] as $name => $value ){
                $attr .= ' data-'.$name.'="'.$value.'"';
            }
        }
        return $attr;
    }
}
