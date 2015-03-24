<?php
namespace Lib\Field;

class CitySelector extends Select
{
	protected $region;
	public $field_type = 'cityselector';
	
    public function __construct($label, array $attributes = array())
    {
        parent::__construct($label, $attributes);
        $this->options = array(''=>__('Select a city...'));
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        $this->region = '';
        if( isset($this->attributes['region']) ){
            $rgManager = \Region::newInstance();
            $rgManager->dao->select('*');
            $rgManager->dao->from($rgManager->getTableName());
            $rgManager->dao->where('s_name', $this->attributes['region']);
            $rgManager->dao->orWhere('pk_i_id', $this->attributes['region']);
            $rgManager->dao->limit(1);
            $rg = $rgManager->dao->get();
            if( false !== $rg ){
                $rs = $rg->row();
                $this->region = $rs['pk_i_id'];
            }
            unset($rgManager);
            unset($this->attributes['region']);
        }

        if( isset($this->attributes['region_field'])){
            $this->attributes['data']['watch'] = $this->attributes['region_field'];
            $this->attributes['class'] = 'city';
            unset($this->attributes['region_field']);
        }

    	$cities = \City::newInstance()->findByRegion( $this->region );
    	if( count($cities) ){
            foreach( $cities as $city ){
                $this->options[$city['pk_i_id']] = $city['s_name'];
            }
        }

        return parent::returnField($form_name, $name, $value, $group);
    }
}
