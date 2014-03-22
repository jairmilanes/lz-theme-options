<?php
namespace Lib\Field;

class Textarea extends Text
{
	public $field_type = 'textarea';
	
    public function __construct($label, $attributes)
    {
        parent::__construct($label, $attributes);
        if (!isset($attributes['rows'])) {
            $attributes['rows'] = 6;
        }
        if (!isset($attributes['cols'])) {
            $attributes['cols'] = 60;
        }
    }

    public function returnField($form_name, $name, $value = '', $group = '') 
    {
        $this->attributeString();

        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label for="%s_%s_%s" class="%s">%s</label>', $form_name, $group, $name, $this->class, $this->label),
            'field' => sprintf('<textarea name="%5$s[%6$s][%1$s]" id="%5$s_%5$s_%1$s" class="%2$s" %4$s>%3$s</textarea>', $name, $this->class, $value, $this->attribute_string, $form_name, $group),
            'html' => $this->html
        );
    }

}
