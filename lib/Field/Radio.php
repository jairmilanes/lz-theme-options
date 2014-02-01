<?php

namespace Lib\Field;

use Lib\Useful;

class Radio extends Options
{
	public $field_type = 'radio';

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        $field = '';
        foreach ($this->options as $key => $val) {
            $attributes = $this->getAttributeString($val);
            $field .= sprintf('<input type="radio" name="%6$s[%7$s][%1$s]" id="%6$s_%7$s_%3$s" value="%2$s" %4$s/>' .
                    '<label for="%6$s_%7$s_%3$s">%5$s</label>'
                    , $name, $key, Useful::slugify($name) . '_' . Useful::slugify($key), ((string) $key === (string) $value ? 'checked="checked"' : '') . $attributes['string'], $attributes['val'], $form_name, $group);
        }
        $class = !empty($this->error) ? 'error choice_label' : 'choice_label';

        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label class="%s">%s</label>', $class, $this->label),
            'field' => $field,
            'html' => $this->html
        );
    }

}
