<?php
namespace Lib\Field;

use Lib\Utils;

class GoogleFont extends Select
{
    protected $variants;
    protected $subsets;
    protected $sort;
    protected $category;
    protected $limit;
    protected $cache_key;
	protected $apikey;

	public $field_type = 'googlefont';
	
    public function __construct($label, array $attributes = array())
    {
        $cache_key = '';

        $this->variants = array();
        if( isset($attributes['variants']) ){
            $this->variants = $attributes['variants'];
            $cache_key .= implode('-',$attributes['variants']);
            unset($attributes['variants']);
        }

        $this->subsets = array();
        if( isset($attributes['subsets']) ){
            $this->subsets = $attributes['subsets'];
            $cache_key .= implode('-',$attributes['subsets']);
            unset($attributes['subsets']);
        }

        $this->sort = 'popularity';
        if( isset($attributes['sort']) ){
            $this->sort = $attributes['sort'];
            $cache_key .= $this->sort;
            unset($attributes['sort']);
        }

        $this->category = '';
        if( isset($attributes['category']) ){
            $this->category = $attributes['category'];
            $cache_key .= (is_array($this->category)? implode('-',$this->category): $this->category);
            unset($attributes['category']);
        }

        if( isset($attributes['limit']) ){
            $this->limit = $attributes['limit'];
            $cache_key .= $this->limit;
            unset($attributes['limit']);
        }

        if( isset($attributes['api-key']) ){
            $this->apikey = $attributes['api-key'];
            $cache_key .= $this->apikey;
            unset($attributes['api-key']);
        }

        $this->cache_key = md5($cache_key);

        parent::__construct($label, $attributes);
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        $this->options = $this->loadFontsList();
        return parent::returnField($form_name, $name, $value, $group);
    }

    protected function loadFontsList(){
        $fonts = osc_cache_get($this->cache_key, $found );
        $found = false;
        if( !$found ){
            $fonts = array();

            $fonts_encoded = file_get_contents('https://www.googleapis.com/webfonts/v1/webfonts?sort='.$this->sort.'&key='.$this->apikey);
            $base_url = '%s%s%s%s';

            if( !empty($fonts_encoded) ){
                $fonts_decoded = json_decode($fonts_encoded);

                unset($fonts_encoded);
                foreach( $fonts_decoded->items as $font ){

                    if( !empty($this->category) ){
                        if( (is_array($this->category) && !in_array($font->category, $this->category))
                            || (is_string($this->category) && $this->category !== $font->category) ){
                            continue;
                        }
                    }

                    $cat_key = (strtoupper($font->category).' FONTS');
                    if( !isset($fonts[$cat_key]['options']) ){
                        $fonts[$cat_key]['options'] = array();
                    }

                    if( $this->limit ){
                        if( count($fonts[$cat_key]['options']) >= $this->limit ){
                            continue;
                        }
                    }

                    $font_name = preg_replace('/\s+/', '+', 'family='.$font->family);

                    $variants = '';
                    if( !empty($font->variants) ){
                        $variants_arr = array_filter($font->variants, function($variant){
                            return in_array($variant, $this->variants);
                        });
                        $variants = (!empty($variants_arr)? ':'.implode(',',$variants_arr) : '');
                    }

                    $subsets = '';
                    if( isset($font->subsets) ){
                        $subsets_arr = array_filter($font->subsets, function($subset){
                            return in_array($subset, $this->subsets);
                        });
                        $subsets = (!empty($subsets_arr)? '&amp;subset='.implode(',',$subsets_arr) : '');
                    }

                    $display = $font->family.','.$font->category;
                    $fonts[$cat_key]['options'][sprintf($base_url, $font_name, $variants, $subsets, '&amp;category='.$font->category)] = $display;

                }
            }

            osc_cache_set($this->cache_key, $fonts );
        }

        return $fonts;
    }


}
