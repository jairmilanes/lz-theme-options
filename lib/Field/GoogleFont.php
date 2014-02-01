<?php
namespace Lib\Field;

use Lib\Useful;

class GoogleFont extends Options
{
	protected $attrs;
	public $field_type = 'googlefont';
	
    public function __construct($label, array $attributes = array())
    {
    	$this->attrs = $attributes;
        parent::__construct($label, $attributes);
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
    	$field = '<div class="fonts">';
    	$field .= sprintf('<select name="%2$s[%3$s][%1$s]" id="%2$s_%3$s_%1$s">', $name, $form_name, $group, $value);
    	foreach ($this->options as $key => $val) {
    		$attributes = $this->getAttributeString($val);
    		$field .= sprintf('<option value="%s" %s>%s</option>', $attributes['val'], ((string) $val === (string) $value ? 'selected="selected"' : '') . $attributes['string'], $key);
    	}
    	$field .= '</select>';
    	$field .= '</div>';
    	$class = !empty($this->error) ? 'error choice_label' : 'choice_label';
        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label for="%s_%s_%s" class="%s">%s</label>', $form_name, $group, $name, $class, $this->label),
            'field' => $field,
            'html' => $this->html
        );
    }
    
    public function validate($val)
    {
    	if ($this->required) {
    		if (Useful::stripper($val) === false) {
    			$this->error[] = 'is required';
    		}
    	}
    	if (in_array($val, $this->false_values)) {
    		$this->error[] = "$val is not a valid choice";
    	}
    	
    	if(!in_array( $val, $this->options )){
    		$this->error[] = "$val is not a valid choice";
    	}
    
    	return !empty($this->error) ? false : true;
    }

}
