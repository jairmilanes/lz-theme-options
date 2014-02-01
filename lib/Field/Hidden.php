<?php
namespace Lib\Field;

class Hidden extends Text
{
	public $field_type = 'hidden';
	
    public function __construct($label = false, $attributes = array())
    {
        parent::__construct($label, $attributes);
    }

    public function returnField($form_name, $name, $value = '')
    {
        $this->field_type = 'hidden';
        $this->label = false;

        return parent::returnField($form_name, $name, $value);
    }

}
