<?php
//namespace Nibble\NibbleForms\Field;
//use Nibble\NibbleForms\Useful;
namespace Lib\Field;

use Lib\Useful;

class Checkbox extends MultipleOptions
{
	public $field_type = 'checkbox';
	
    public function returnField($form_name, $name, $value = '', $group = '')
    {
    	
        $field = '';
        foreach ($this->options as $key => $val) {
        	    	
            $attributes = $this->getAttributeString($val);
            $field .= sprintf('<input type="checkbox" name="%5$s[%6$s][%1$s][]" id="%5$s_%6$s_%3$s" value="%2$s" %4$s class="%7$s"/>', 
            		$name, 
            		$key, 
            		Useful::slugify($name) . '_' . Useful::slugify($key), 
            		(is_array($value) && in_array((string) $key, $value) ? 'checked="checked"' : '') . $attributes['string'], 
            		$form_name, 
            		$group,
					$this->attributes['class'] );
        }
        
        $class = !empty($this->error) ? 'error choice_label' : 'choice_label';

        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label class="%s">%s</label>', $class, $this->label),
            'field' => $field,
            'html' => $this->html
        );
    }
    
    public function validate($val)
    {
    	if (is_array($val)) {
    		if ($this->minimum_selected && count($val) < $this->minimum_selected) {
    			$this->error[] = sprintf('at least %s options must be selected', $this->minimum_selected);
    		}
    		foreach ($val as $answer) {
    			if (in_array($answer, $this->false_values)) {
    				$this->error[] = "$answer is not a valid choice";
    			}
    		}
    	} elseif ($this->required) {
    		$this->error[] = 'is required';
    	}
    
    	return !empty($this->error) ? false : true;
    }

}
