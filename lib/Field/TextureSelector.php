<?php
namespace Lib\Field;

class TextureSelector extends Options
{
	protected $size;
	public $field_type = 'textureselector';
	
    public function __construct($label, array $attributes = array())
    {
        parent::__construct($label, $attributes);
        $this->size = ( !isset( $attributes['option_size'] ))? 'small' : $attributes['option_size']; 
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
    	$field  = sprintf( '<div id="%2$s_%4$s_%1$s" class="texture_field %3$s">', $name, $form_name, $this->size, $group );
        $field .= '<ul class="inline">';
        foreach ($this->options as $key => $val) {
            $attributes = $this->getAttributeString($val);
            $field .= sprintf('<li class="texture %s" %s><span style="background: url( %s ) repeat top left;" data-value="%s"></span></li>', ((string) $key === (string) $value ? 'active' : ''), $attributes['string'], osc_current_web_theme_url($attributes['val']), $key);
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
