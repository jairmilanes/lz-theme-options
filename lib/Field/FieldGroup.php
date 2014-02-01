<?php

namespace Lib\Field;

use Lib\Field;

class FieldGroup extends Field
{
	protected $action;
	protected $group_title;
    public $field_type = 'fieldGroup';

    public function __construct($label, $attributes = array())
    {
		$this->action = ( !isset($attributes['action']) )? 'open' : $attributes['action'];
		$this->group_title = @$attributes['title'];
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        if( $this->action == 'close' ){
        	$field = $this->getCloseTag();
        } else {
        	$field = $this->getOpenTag($name, $group);
        	$field .= $this->getTitleTag();
        }

        return array(
            'messages' => '',
            'label' => '',
            'field' => $field,
            'html' => $this->html
        );
    }
    
    protected function getTitleTag(){
    	return '<legend>'.$this->group_title.'</legend>';
    }
	
    protected function getOpenTag($name, $group){
    	return '<fieldset class="group_'.$group.'_'.$name.' open"><div class="fieldset_inner">';
    }
    
    protected function getCloseTag(){
    	 return '</div></fieldset>';
    }
    
    public function validate($val)
    {
        return true;
    }

}
