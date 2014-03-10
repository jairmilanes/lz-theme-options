<?php

namespace Lib\Field;

use Lib\Useful;

class SlideRange extends Text
{
	public $field_type = 'sliderange';

    public function validate($val)
    {
    	$values = array();
    	if( isset($val['min']) && !isset($val['max']) ){
    		$values['min'] = (int)$val['min']; 		
    	} else if( !isset($val['min']) && isset($val['max']) ){
    		$values['max'] = (int)$val['max'];
    	} else if( isset($val['min']) && isset($val['max']) ){
    		$values['min'] = (int)$val['min'];
    		$values['max'] = (int)$val['max'];
    	}

    	foreach( $values as $type => $range ){
    		if ( !parent::validate($range) ) {
    			$this->error[$type] = 'must be a valid range of numbers';
    		}
    		if ( Useful::stripper($range) !== false ) {
    			if (!filter_var( (int)$range, FILTER_VALIDATE_INT ) ) {
    				$this->error[$type] = 'must be a valid range of numbers';
    			}
    		}	
    	}

        return !empty($this->error) ? false : true;
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
    	if( !isset($this->attributes['type']) || empty($this->attributes['type']) ){
    		$this->attributes['type'] = true;
    	}
    	if( !isset($this->attributes['step']) || empty($this->attributes['step']) ){
    		$this->attributes['type'] = 5;
    	}
    	//var_dump($value);exit;
    	$field  = sprintf('<div class="sliderange_field" data-step="%s">', $this->attributes['step'] );
    	switch( $this->attributes['type'] ){
    		case "min":
    			$field .= sprintf( '<input readonly type="text" class="range_min" name="%2$s[%3$s][%1$s][max]" id="%2$s_%3$s_%1$s_max" value="%4$s">', $name, $form_name, $group, @$value['max']);
    			break;
    		case "max":
    			$field .= sprintf( '<input readonly type="text" class="range_max" name="%2$s[%3$s][%1$s][min]" id="%2$s_%3$s_%1$s_min" value="%4$s">', $name, $form_name, $group, @$value['min']);
    			break;
    		case "both":
    			$field .= sprintf( '<input readonly type="text" name="%2$s[%3$s][%1$s][min]" id="%2$s_%3$s_%1$s_min" value="%4$s">', $name, $form_name, $group, @$value['min']);
    			$field .= sprintf( '<input readonly type="text" name="%2$s[%3$s][%1$s][max]" id="%2$s_%3$s_%1$s_max" value="%4$s">', $name, $form_name, $group, @$value['max']);
    			break;
    	}
    	$field .= '<div class="clear"></div>';
    	$field .= sprintf( '<div id="%2$s_%3$s_%1$s" class="slider" data-type="%4$s" data-min="%6$s" data-max="%5$s"></div>', $name, $form_name, $group, $this->attributes['type'], $this->attributes['max'],$this->attributes['min'] );
    	$field .= '</div>';    	
        $class = !empty($this->error) ? 'error choice_label' : 'choice_label';

        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label for="%s_%s_%s" class="%s">%s</label>', $form_name, $group, $name, $class, $this->label),
            'field' => $field,
            'html' => $this->html
        );
    }

}
