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

    private $height;
    private $width;

    private $min_height;
    private $min_width;

    private $upload_path;
    private $multiple;

    private $mime_types = array(
        'image' => array(
            'image/gif', 'image/gi_', 'image/png', 'application/png', 'application/x-png',
            'image/jp_', 'application/jpg', 'application/x-jpg', 'image/pjpeg', 'image/jpeg'
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
        'custom' => 'is invalid'
    );



    public function __construct($label, $type = 'all', $required = false, $max_size = 2097152, $width = 1600, $height = 1600, $min_width = 0, $min_height = 0)
    {
    	//printR($type);
    	if( is_array($type) ){
    		extract($type);
    	}

        $this->label 		= @$label;
        $this->required 	= @$required;
        $this->max_size 	= @$max_size;
        $this->width 		= @$width;
        $this->height 		= @$height;
        $this->min_width 	= @$min_width;
        $this->min_height   = @$min_height;
        $this->max_files    = 1;
		/*
        $upload_path 	 	= osc_content_path().'uploads/theme_options_uploads';
		if( !file_exists($upload_path) ){
			mkdir($upload_path);
		}
		*/
        $this->multiple 	= ( isset( $multiple ) )? true : false;
        $this->upload_path  =   LZO_UPLOAD_PATH;
        //$this->max_files 	= ( isset( $this->max_files ) && is_numeric( $this->max_files ) && $this->max_files > 0 ) ? $this->max_files : 1;

        if (is_array($type)) {
            $this->mime_types = $type;
            $this->type = 'custom';
        } else {
            $this->type = $type;
            if (isset($this->mime_types[$type])) {
                $this->mime_types = $this->mime_types[$type];
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
	                if ($image[0] > $this->width || $image[1] > $this->height) {
	                    $this->error[] = sprintf('must contain an image no more than %s pixels wide and %s pixels high', $this->width, $this->height);
	                }
	                if ($image[0] < $this->min_width || $image[1] < $this->min_height) {
	                    $this->error[] = sprintf('must contain an image at least %s pixels wide and %s pixels high', $this->min_width, $this->min_height);
	                }
	                if (!in_array($image['mime'], $this->mime_types)) {
	                    $this->error[] = $this->error_types[$this->type];
	                }
	            } elseif (!in_array($val['type'], $this->mime_types)) {
	                $this->error[] = $this->error_types[$this->type];
	            }
	        }
    	}
        return !empty($this->error) ? false : true;
    }

}
