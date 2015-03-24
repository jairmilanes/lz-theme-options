<?php
namespace Lib\Field;


class CountrySelector extends Select
{
    protected $limit = array();
    protected $exclude = array();

    public function __construct($label, array $attributes = array())
    {
        $this->field_type = 'countryselector';

        if( isset($attributes['limit']) && is_array($attributes['limit']) ){
            $this->limit = $attributes['limit'];
            unset($attributes['limit']);
        }

        if( isset($attributes['exclude']) && is_array($attributes['exclude']) ){
            $this->exclude = $attributes['exclude'];
            unset($attributes['exclude']);
        }

        parent::__construct($label, $attributes);

        $this->options = array(''=>__('Select a country...'));
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        $countries = osc_get_countries();
        foreach( $countries as $country ){
            if( !empty($this->limit) && empty($this->exclude)){
                if( in_array($country['s_slug'], $this->limit )
                    || in_array($country['pk_c_code'], $this->limit)  ){
                    $this->options[$country['pk_c_code']] = $country['s_name'];
                } else {
                    continue;
                }
            } else {
                if( !empty($this->exclude) ){
                    if( !in_array($country['s_slug'], $this->exclude )
                        || !in_array($country['pk_c_code'], $this->exclude )  ){
                        $this->options[$country['pk_c_code']] = $country['s_name'];
                    } else {
                        continue;
                    }
                } else {
                    $this->options[$country['pk_c_code']] = $country['s_name'];
                }
            }
        }

    	return parent::returnField($form_name, $name, $value, $group);
    }

}
