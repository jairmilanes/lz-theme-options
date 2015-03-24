<?php

/**
 * Just make sure this function exists
 */
if( !function_exists('osc_uploads_url') ){
    function osc_uploads_url($item = ''){
        return osc_base_url().'oc-content/uploads/'.$item;
    }
}


/**
 * Formats a file size
 *
 * @param $bytes
 * @param int $decimals
 * @return string
 */
function lzto_format_file_size($bytes, $decimals = 2)
{
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);

    $prefix = @$sz[$factor];
    $prefix .= (!empty($prefix)&&$prefix!=='B')? 'B':'';

    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).$prefix;
}


/**
 * Returns a value from a array
 *
 * @param $array
 * @param $key
 * @param string $default
 * @return string
 */
function lzto_var($array, $key, $default = ''){
    if( isset($array[$key])){
        return $array[$key];
    }
    return $default;
}


/**
 * Check if LzThemeOptions is loaded
 *
 * @return bool
 */
function lzto_is_ready(){
    return defined('THEME_OPTIONS_ENABLED') && THEME_OPTIONS_ENABLED;
}


/**
 * Check if we are in DEMO mode
 *
 * @return bool
 */
function lzto_isDemo(){
    return defined('DEMO') && DEMO == true && !osc_is_admin_user_logged_in();
}

if(  !function_exists('printR') ){
    function printR($msg, $exit = false){
        echo '<pre>'.print_r($msg, true).'</pre>';
        if( $exit ){
            die();
        }
    }
}