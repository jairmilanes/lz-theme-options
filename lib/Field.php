<?php
namespace Lib;

use Lib\Useful;

abstract class Field
{

    public $custom_error = array();
    protected $form;
    public $html = array(
        'open_field' => false,
        'close_field' => false,
        'open_html' => false,
        'close_html' => false
    );

    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * Return the current field, i.e label and input
     */
    abstract public function returnField($form_name, $name, $value = '', $group = '' );

    /**
     * Validate the current field
     */
    abstract public function validate($val);

    /**
     * Apply custom error message from user to field
     */
    public function errorMessage($message)
    {
        $this->custom_error[] = $message;
    }

}
