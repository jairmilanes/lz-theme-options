<?php
/*
	Plugin Name: LZThemeOptions
	Plugin URI: http://www.layoutz.com.br/
	Description: Theme options plugin, allows a developer to include theme options with ease.
	Version: 1.4.0
	Author: Jair Milanes Junior
	Author URI: http://www.layoutz.com.br/
	Short Name: lzto
	Plugin update URI: lzto
*/

require dirname(__FILE__) . '/Lib/Builder.php';

define( 'LZO_UPLOAD_PATH', UPLOADS_PATH.'lz_theme_options/' );
define( 'LZO_THUMB_PATH', LZO_UPLOAD_PATH.'thumbnails/' );
define( 'LZO_PRESETS_PATH', UPLOADS_PATH.'presets/' );

if( lzto_isDemo() ){
    $ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP );
    define('DEMO_USER_IP', ip2long($ip) );
    define('LZO_DEMO_USER_PATH', UPLOADS_PATH.'lz_theme_demo_users/'.DEMO_USER_IP.'/' );
    define('LZO_DEMO_USER_THUMB_PATH', LZO_DEMO_USER_PATH.'thumbnails/' );

    if( !file_exists(LZO_DEMO_USER_PATH) ){
        @mkdir( LZO_DEMO_USER_PATH, 0777 );
    }

    if( !file_exists(LZO_DEMO_USER_THUMB_PATH) ){
        @mkdir( LZO_DEMO_USER_THUMB_PATH, 0777 );
    }
}

define('ENABLE_FRONTEND_OPTIONS_PANEL', false); // UNDER DEVELOPMENT

/**
 * Load Lz Theme Options
 *
 * @return bool
 */
function lzto_init(){

    $theme_options = false;
    $theme = osc_current_web_theme();

    if( function_exists('lz_demo_selected_theme') ){
        $theme = lz_demo_selected_theme();
    }

    $options_method = osc_current_web_theme().'_get_theme_options';
    $file = osc_themes_path().$theme.'/options.php';

    if(!file_exists( $file )){
        return;
        //osc_add_flash_error_message('Theme options.php file not found! If your theme does not use Lz Theme Options plugin please disabled it to avoid conflicts with your theme.','admin');
    } else {

        require_once $file;
        if( function_exists( $options_method ) ){
            $theme_options = Builder::newInstance()->setOptions( $options_method() );
            if( $theme_options ){
                osc_register_script('jquery', osc_plugin_url('lz_theme_options/assets').'assets/js/jquery.js' );
                if( !OC_ADMIN ){
                    osc_register_script('jqueryui', osc_plugin_url('lz_theme_options/assets').'assets/js/jquery-ui.min.js' );
                }
                osc_register_script('colpick', osc_plugin_url('lz_theme_options/assets').'assets/js/colpick.js' );
                osc_register_script('icheck', osc_plugin_url('lz_theme_options/assets').'assets/js/icheck/jquery.icheck.min.js' );
                osc_register_script('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/toggles.min.js');
                osc_register_script('perfect_scroll', osc_plugin_url('lz_theme_options/assets').'assets/js/perfect-scrollbar.js');
                if( OSCLASS_VERSION < 3.3 ){
                    osc_register_script('jquery-fineuploader', osc_plugin_url('lz_theme_options/assets').'assets/js/fineuploader/jquery.fineuploader.min.js' );
                }
                osc_register_script('lz_theme_options', osc_plugin_url('lz_theme_options/assets').'assets/js/lz_theme_options.js' );
            }
        } else {
            osc_add_flash_error_message(sprintf('Options function "%s" was not found, please check you options.php function name.',$options_method),'admin');
        }
    }

    define( 'THEME_OPTIONS_ENABLED', $theme_options );

    if( THEME_OPTIONS_ENABLED ){
        $themes = WebThemes::newInstance()->getListThemes();
        foreach( $themes as $theme ){
            osc_add_hook('theme_delete_'.$theme, 'lzto_theme_delete');
        }
    }

    return true;
}

/**
 * Get theme options by it's group name
 * @param string $group Name of the group
 */
function lzto_getOptionsByGroupName($group){
    return Builder::newInstance()->getOptionsByGroupName($group);
}

/**
 * Retrives a single option value from db
 *
 * @param string $field
 * @param string $group
 */
function lzto_getOption( $group, $field ){
    return Builder::newInstance()->getOption( $group, $field );
}

/**
 * Retrieves a boolean value from a switch button element from db
 *
 * @param $group
 * @param $field
 * @return bool
 */
function lzto_getSwitchOption( $group, $field ){
    $option = Builder::newInstance()->getOption( $group, $field );
    if( is_array($option) ){
        return (bool)($option[0]);
    }
    return false;
}

/**
 * Gets a google font field value
 *
 * @param $group
 * @param $name
 * @return string
 */
function lzto_getGoogleFont($group, $name ){
    $font_uri = lzto_getOption($group, $name);

    if( !empty($font_uri) ){
        parse_str(html_entity_decode($font_uri), $parsed);

        $categories = array(
            'display' => 'cursive',
            'monospace' =>  '',
            'sans-serif' => 'sans-serif',
            'serif' => 'serif'
        );

        $category = $parsed['category'];
        unset($parsed['category']);
        $family = explode(':',$parsed['family']);

        return array(
            'family' => '"'.$family[0].'"'.(!empty($categories[$category])? ', '.$categories[$category] : '').'',
            'url' => 'http://fonts.googleapis.com/css?'.http_build_query($parsed)
        );
    }

    return array(
        'family' => '',
        'url' => ''
    );;
}

/**
 * Check if a field has value
 * @return mixed array|string
 */
function lzto_hasOption($form, $field){
    return Builder::newInstance()->hasOption($form, $field);
}



osc_add_hook( 'init', 'lzto_init', 0 );
osc_register_plugin( osc_plugin_path( __FILE__ ),               'lzto_install' );
osc_add_hook( osc_plugin_path( __FILE__ ) . '_uninstall',       'lzto_uninstall' );


