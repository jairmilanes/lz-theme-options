<?php

namespace Lib\Field;

use Lib\Utils;

abstract class Options extends BaseOptions
{

    public function validate($val)
    {
        if ($this->required) {
            if (Utils::stripper($val) === false) {
                $this->error[] = 'is required';
            }
        }
        if (in_array($val, $this->false_values)) {
            $this->error[] = "$val is not a valid choice";
        }

        /*
        if(!in_array( $val, $this->options )){
            $this->error[] = "$val is not a valid choice";
        }
        */

        return !empty($this->error) ? false : true;
    }

}
