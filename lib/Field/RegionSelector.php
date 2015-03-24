<?php
namespace Lib\Field;

class RegionSelector extends Select
{
    protected $region;
    public $field_type = 'regionselector';

    public function __construct($label, array $attributes = array())
    {
        parent::__construct($label, $attributes);
        $this->options = array('' =>__('Select a region...') );
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        $this->country = '';
        if( isset($this->attributes['country']) ){
            $rgManager = \Country::newInstance();
            $rgManager->dao->select('*');
            $rgManager->dao->from($rgManager->getTableName());
            $rgManager->dao->where('s_name', $this->attributes['country']);
            $rgManager->dao->orWhere('pk_c_code', $this->attributes['country']);
            $rgManager->dao->limit(1);
            $rg = $rgManager->dao->get();
            if( false !== $rg ){
                $rs = $rg->row();
                $this->country = $rs['pk_c_code'];
            }
            unset($rgManager);
            unset($this->attributes['country']);
        }

        if( isset($this->attributes['country_field'])){
            $this->attributes['data']['watch'] = $this->attributes['country_field'];
            $this->attributes['class'] = 'region';
            unset($this->attributes['country_field']);
        }

        $regions = \Region::newInstance()->findByCountry( $this->country );
        if( count($regions) ){
            foreach( $regions as $region ){
                $this->options[$region['pk_i_id']] = $region['s_name'];
            }
        }

        return parent::returnField($form_name, $name, $value, $group);
    }
}
