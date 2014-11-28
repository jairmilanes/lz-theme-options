<?php
namespace Lib\Field;

use Lib\Field;

class AjaxFile extends Field
{
	public  $error = array();
	public  $field_type = 'file';

    private $label;
    private $type;
    private $required;
    private $max_size;
    
    private $max_files;

    private $min_height;
    private $min_width;

    private $max_height;
    private $max_width;

    private $upload_path;
    private $upload_thumb_path;
    private $multiple;

    private $mime_types = array(
        'image' => array(
            'image/gif', 'image/gi_', 'image/png', 'application/png', 'application/x-png', 'image/jp_',
            'application/jpg', 'application/x-jpg', 'image/pjpeg', 'image/jpeg', 'image/x-icon'
        ),
        'document' => array(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/mspowerpoint', 'application/powerpoint', 'application/vnd.ms-powerpoint',
            'application/x-mspowerpoint', 'application/plain', 'text/plain', 'application/pdf',
            'application/x-pdf', 'application/acrobat', 'text/pdf', 'text/x-pdf', 'application/msword',
            'pplication/vnd.ms-excel', 'application/msexcel', 'application/doc',
            'application/vnd.oasis.opendocument.text', 'application/x-vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet', 'application/x-vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation', 'application/x-vnd.oasis.opendocument.presentation'
        ),
        'archive' => array(
            'application/x-compressed', 'application/gzip-compressed', 'gzip/document',
            'application/x-zip-compressed', 'application/zip', 'multipart/x-zip',
            'application/tar', 'application/x-tar', 'applicaton/x-gtar', 'multipart/x-tar',
            'application/gzip', 'application/x-gzip', 'application/x-gunzip', 'application/gzipped'
        )
    );
    private $error_types = array(
        'image' => 'must be an image, e.g example.jpg or example.gif',
        'archive' => 'must be and archive, e.g example.zip or example.tar',
        'document' => 'must be a document, e.g example.doc or example.pdf',
        'all' => 'must be a document, archive or image',
        'custom' => 'is invalid, must be of type ( %s )'
    );



    public function __construct($label, $attributes = array() )
    {
        $this->type                 = lzto_var($attributes, 'type',                 'all');
        $this->label 		        = lzto_var($attributes, 'label',                '');
        $this->required 	        = lzto_var($attributes, 'required',             false);
        $this->max_size 	        = lzto_var($attributes, 'max-size',             osc_max_size_kb());
        $this->max_width 	        = lzto_var($attributes, 'max-width',            0);
        $this->max_height 	        = lzto_var($attributes, 'max-height',           0);
        $this->min_width 	        = lzto_var($attributes, 'min-width',            0);
        $this->min_height           = lzto_var($attributes, 'min-height',           0);

        $this->max_files            = lzto_var($attributes, 'max-files',            1);
        $this->multiple 	        = lzto_var($attributes, 'multiple',             false);
        $this->upload_path          = lzto_var($attributes, 'upload-path',          LZO_UPLOAD_PATH);
        $this->upload_thumb_path    = lzto_var($attributes, 'upload-thumb-path',    LZO_THUMB_PATH);


        if (is_array($this->type )) {
            $this->mime_types = $this->type;
            $this->type = 'custom';
        } else {
            if (isset($this->mime_types[$this->type])) {
                $this->mime_types = $this->mime_types[$this->type];
            } else {
                $temp = array();
                foreach ($this->mime_types as $mime_array)
                    foreach ($mime_array as $mime_type)
                        $temp[] = $mime_type;
                $this->mime_types = $temp;
                $this->type = 'all';
                unset($temp);
            }
        }
    }

    public function getConfig($key){
        if( isset($this->$key ) ){
            return $this->$key;
        }
        return false;
    }

    public function returnField($form_name, $name, $value = '', $group = '')
    {
        $class = !empty($this->error) ? ' class="error"' : '';

        return array(
            'messages' => !empty($this->custom_error) && !empty($this->error) ? $this->custom_error : $this->error,
            'label' => $this->label == false ? false : sprintf('<label for="%s_%s_%s"%s>%s</label>', $form_name, $group, $name, $class, $this->label),
            'field' => '<div class="upload_button" id="'.$name.'" data-name="'.$name.'" data-group="'.$group.'"></div>',
            'html' => $this->html
        );
    }

    public function validate($val)
    {
    	if( !empty($val) ){
    	
	        if ($this->required) {
	            if ($val['error'] != 0 || $val['size'] == 0) {
	                $this->error[] = 'is required';
	            }
	        }
	        if ($val['error'] == 0) {
	            if ($val['size'] > $this->max_size) {
	                $this->error[] = sprintf('must be less than %sMb', $this->max_size / 1024 / 1024);
	            }
	            if ($this->type == 'image') {
	                $image = getimagesize($val['tmp_name']);

	                if ($this->max_width > 0 && $image[0] > $this->max_width || $this->max_height > 0 && $image[1] > $this->max_height) {
	                    $this->error[] = sprintf('must contain an image no more than %s pixels wide and %s pixels high', $this->max_width, $this->max_height);
	                }
	                if ($this->min_width > 0 && $image[0] < $this->min_width || $this->min_height > 0 && $image[1] < $this->min_height) {
	                    $this->error[] = sprintf('must contain an image at least %s pixels wide and %s pixels high', $this->min_width, $this->min_height);
	                }
	                if (!in_array($image['mime'], $this->mime_types)) {
                        $this->error[] = $this->error_types[$this->type];
	                }
	            } elseif (!in_array($val['type'], $this->mime_types)) {
                    if( $this->type == 'custom' ){
                        $this->error[] = sprintf($this->error_types[$this->type], implode(',',$this->mime_types));
                    } else {
                        $this->error[] = $this->error_types[$this->type];
                    }
	            }
	        }
    	}
        return !empty($this->error) ? false : true;
    }

}
