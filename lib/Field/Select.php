<?php

namespace Lib\Field;

class Select extends Options
{
	public $field_type = 'select';
	
    public function __construct($label, array $attributes = array())
    {
        parent::__construct($label, $attributes);
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        $field = sprintf('<select name="%2$s[%3$s][%1$s]" id="%2$s_%3$s_%1$s" class="%4$s" %5$s>', $name, $form_name, $group, $this->attributes['class'], $this->getDataAttributes());
        foreach ($this->options as $key => $val) {

            if( isset($val['options']) ){
                $field .= '<optgroup label="'.$key.'">';

                foreach ($val['options'] as $group_key => $group_val) {
                    $attributes = $this->getAttributeString($group_val);
                    $field .= sprintf('<option value="%s" %s>%s</option>', $group_key, ((string) $group_key === (string) $value ? 'selected="selected"' : '') . $attributes['string'], $attributes['val']);
                }

                $field .= '</optgroup>';
            } else {
                $attributes = $this->getAttributeString($val);
                $field .= sprintf('<option value="%s" %s>%s</option>', $key, ((string) $key === (string) $value ? 'selected="selected"' : '') . $attributes['string'], $attributes['val']);
            }

        }
        $field .= '</select>';
        $class = !empty($this->error) ? 'error choice_label' : 'choice_label';

        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label for="%s_%s_%s" class="%s">%s</label>', $form_name, $group, $name, $class, $this->label),
            'field' => $field,
            'html' => $this->html
        );
    }

}