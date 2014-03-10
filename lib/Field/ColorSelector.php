<?php
namespace Lib\Field;

class ColorSelector extends Options
{
	protected $size;
	public $field_type = 'colorselector';
	
    public function __construct($label, array $attributes = array())
    {
        parent::__construct($label, $attributes);
        $this->size = ( !isset( $attributes['option_size'] ))? 'small' : $attributes['option_size'];
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
    	$field  = sprintf( '<div id="%2$s_%4$s_%1$s" class="color_field %3$s">', $name, $form_name, $this->size, $group );
        $field .= '<ul class="inline">';
        foreach ($this->options as $key => $val) {
            $attributes = $this->getAttributeString($val);
            $accent = '';
            if( strstr($attributes['val'], ',') ){
            	$colors = explode( ',', $attributes['val'] );
            	$attributes['val'] = $colors[0];
            	$accent = sprintf('<span class="accent_color" style="background: %s;">', $colors[1] );
            }

            $field .= sprintf('<li class="color %s" %s><span style="background: %s;" data-value="%s">%s</span></li>', ((string) $key === (string) $value ? 'active' : ''), $attributes['string'], $attributes['val'], $key, $accent);
        }
        $field .= '</ul>';
        $field .= sprintf('<input type="hidden" name="%2$s[%3$s][%1$s]" id="%2$s_%3$s_%1$s" value="%4$s">', $name, $form_name, $group, $value);
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