<?php

namespace Lib\Field;

use Lib\Useful;

class Url extends Text
{
	public $field_type = 'url';

    public function validate($val)
    {
        if (!empty($this->error)) {
            return false;
        }
        if (parent::validate($val)) {
            if (Useful::stripper($val) !== false) {
                if (!filter_var($val, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
                    $this->error[] = 'must be a valid URL';
                }
            }
        }

        return !empty($this->error) ? false : true;
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        return parent::returnField($form_name, $name, $value, $group );
    }

}
