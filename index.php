<?php
/*
	Plugin Name: LZThemeOptions
	Plugin URI: http://www.layoutz.com.br/
	Description: Theme options plugin, allows a developer to include theme options with ease.
	Version: 1.0
	Author: Jair Milanes Junior
	Author URI: http://www.layoutz.com.br/
	Short Name: lzto
	Plugin update URI: lzto
*/
define('ENABLE_FRONTEND_OPTIONS_PANEL', true);

/**
 * Load
 */
function lzto_init(){

    //$i = (int)Session::newInstance()->_get('lzto_count');
    //osc_add_flash_info_message('pass '.$i);
    //Session::newInstance()->_set('lzto_count', $i++);

    $theme_options = false;

    $theme = osc_current_web_theme();

    if( function_exists('lz_demo_selected_theme') ){
        $theme = lz_demo_selected_theme();
    }

    $options_method = osc_current_web_theme().'_get_theme_options';

    $file = osc_themes_path().$theme.'/options.php';

    if( file_exists( $file ) && !function_exists($options_method) ){
        if( OC_ADMIN ){
            if( Params::getParam('page') !== 'plugins' ){
                return;
            }
            if( Params::getParam('action') == 'configure_post' && !strstr( Params::getParam('plugin'),'lz_theme_options/index' ) ){
                return;
            }
            if( Params::getParam('action') == 'renderplugin' && !strstr( Params::getParam('file'),'lz_theme_options/view/settings' ) ){
                return;
            }
        }
        $settings = array();
        require_once $file;

        if( function_exists( $options_method ) ){
            require_once osc_plugins_path('lz_theme_options').'lz_theme_options/builder.php';

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

            $theme_options = Builder::newInstance()->setOptions( $options_method() );

            if( $theme_options ){
                lzto_register_scripts();
            }
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
 * Saves a new preset
 *
 * @todo Move to a preset helper
 */
function lzto_save_preset(){
    $return = Builder::newInstance()->savePreset();
    if( false !== $return ){
        switch($return){
            case 1;
                die(json_encode(array('status' => false, 'message' => 'No options found in the database, try changing some options and saving the preset again.')));
                break;
            case 2;
                die(json_encode(array('status' => false, 'message' => 'Admin user not logged in, cant create preset.')));
                break;
            case 3;
                die(json_encode(array('status' => true, 'message' => 'Preset saved.', 'presets' => lzto_load_presets())));
                break;
        }
    }
    die(json_encode(array('status' => false, 'message' => 'Failed to save the zip arquive.')));
}

/**
 * Loads a existing preset
 *
 * @todo Move tho a preset helper
 */
function lzto_load_preset(){
    $return = Builder::newInstance()->loadPreset();
    if( false !== $return ){
        die(json_encode(array('status' => true, 'message' => 'Preset loaded success!.')));
    }
    die(json_encode(array('status' => false, 'message' => 'Could not load preset.' )));
}

/**
 * Get all existing presets
 *
 * @todo Move tho a preset helper
 */
function lzto_load_presets(){
    return Builder::newInstance()->loadPresets();
}

/**
 * Remove a existing preset
 *
 * @todo Move tho a preset helper
 */
function lzto_remove_preset(){
    $return = Builder::newInstance()->removePreset();
    if( false !== $return ){
        die(json_encode(array('status' => true, 'message' => 'Preset removed!.', 'presets' => $return)));
    }
    die(json_encode(array('status' => false, 'message' => 'Could not remove preset.' )));
}

/**
 * Get theme optios by it�s group name
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
 * Get all available fields in array format
 * @return array Field array:
 */
function lzto_getFields( $group = null ){
    return Builder::newInstance()->getFields($group);
}

/**
 * Check if a field has value
 * @return mixed array|string
 */
function lzto_hasOption($form, $field){
    return Builder::newInstance()->hasOption($form, $field);
}

/**
 * Open form html for rendering
 */
function lzto_openForm(){
    return Builder::newInstance()->openForm();
}

/**
 * Close form html for rendering
 */
function lzto_closeForm(){
    return Builder::newInstance()->closeForm();
}

/**
 * Renders one complete field
 */
function lzto_renderField( $field, $parent, $group = null ){
    return Builder::newInstance()->renderField( $field, $parent, $group );
}

/**
 * Returns the specifyed group title
 * @param string $group_slug
 */
function lzto_getGroupTitle( $group_slug ){
    return Builder::newInstance()->getGroupName( $group_slug );
}

/**
 * Process the admin panel settings post
 */
function lzto_settingsPost(){
    if( THEME_OPTIONS_ENABLED && Params::existParam('lzto') ){
        Builder::newInstance()->save();
    }
}

/**
 * Saves a new upload file
 */
function lzto_uploadFile(){
    Builder::newInstance()->saveUpload();
}

/**
 * Delete a uploaded file
 */
function lzto_deleteUploadFile(){
    Builder::newInstance()->deleteUpload();
}

/**
 * Load existsing files
 */
function lzto_loadUploadFiles(){
    return Builder::newInstance()->getUploadFilesAsJson();
}

/**
 * Build options menu
 */
function lzto_prepareRowHtml( $fields, $parent, $group = null ){
    foreach(  $fields as $par => $field ){
        // we are in a group
        if( is_array($field) ){
            lzto_prepareRowHtml( $field, $par, $parent );
        }
        // this is a single field
        else {
            echo lzto_renderField( $field, $parent, $group );
        }
    }
}

/**
 * Reset options form
 */
function lzto_resetOptions(){
    $rs = Builder::newInstance()->resetOptions();
    if( $rs ){
        die(json_encode(array('status' => true, 'message' => _m('Reset sucessefuly completed!') )));
    }
    die(json_encode(array('status' => false, 'message' => _m('Problem during reset operation.') )));
}

/**
 * Loads files
 */
function lzto_admin_header(){

    if( osc_plugin_is_enabled('lz_theme_options/index.php') ){
        if( ( OC_ADMIN && Params::getParam('page') == 'plugins'
                && Params::getParam('file') == 'lz_theme_options/view/settings.php' )
            || !OC_ADMIN ){
            if( !OC_ADMIN ){
                osc_enqueue_style('jquery-ui', osc_plugin_url('lz_theme_options/assets').'assets/css/ui-theme/jquery-ui.custom.min.css' );
            }
            //osc_enqueue_style('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/toggles.css' );
            osc_enqueue_style('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/themes/toggles-dark.css' );
            //osc_enqueue_style('slider', osc_plugin_url('lz_theme_options/assets').'assets/css/slider.css' );
            osc_enqueue_style('perfect_scroll', osc_plugin_url('lz_theme_options/assets').'assets/css/perfect-scrollbar.css' );
            osc_enqueue_style('colpick', osc_plugin_url('lz_theme_options/assets').'assets/css/colpick.css' );
            osc_enqueue_style('lz_options', osc_plugin_url('lz_theme_options/assets').'assets/css/lz_options.css' );
            if( !OC_ADMIN ){
                osc_enqueue_style('lz_options_extra', osc_plugin_url('lz_theme_options/assets').'assets/css/extra.css' );
            }
            osc_enqueue_script('jquery');
            if( !OC_ADMIN ){
                osc_enqueue_script('jqueryui');
            }
            osc_enqueue_script('icheck');
            osc_enqueue_script('toggles');
            osc_enqueue_script('perfect_scroll');

            osc_enqueue_script('jquery-fineuploader');
            osc_enqueue_script('colpick');
            osc_enqueue_script('lz_theme_options');
        }
    }
}

/**
 * Inserts a link on the admin menu bar
 */
function lzto_admin_menu() {
    echo '<h3><a href="#">' . __('Theme options', 'lzto') . '</a></h3>
		  <ul>
		      <li><a href="' . osc_admin_render_plugin_url(osc_plugin_folder(__FILE__) . '/view/settings.php') . '">&raquo; ' . __('Theme options', 'lzto') . '</a></li>
		  </ul>';
}

function lzto_admin_menu_init(){
    osc_add_admin_menu_page(
        __('Theme Options', 'lzto'),
        osc_admin_render_plugin_url(osc_plugin_folder(__FILE__) . '/view/settings.php'),
        'lz_theme_options',
        'administrator'
    );
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
 * Creates a link on the admin toolbar menu
 */
function lzto_admin_toolbar_menus(){
    //if( defined( 'THEME_OPTIONS_ENABLED' ) && true === THEME_OPTIONS_ENABLED ){
    osc_admin_menu_appearance( __('LZ Theme options', 'lz_theme_options'), osc_admin_render_plugin_url( osc_plugin_path( dirname(__FILE__) ) . '/view/settings.php'), 'lz_theme_options');
    //}
}

/**
 * Add to Admin menu
 */
function lzto_admin_menu_icon() { ?>
    <style>
        .ico-lz_theme_options {
            background-image: url('<?php echo osc_base_url();?>oc-content/plugins/<?php echo osc_plugin_folder(__FILE__);?>assets/img/admin_panel_icon.png') !important;
            background-position:0px 0px;
        }
        .ico-lz_theme_options,
        .current .ico-lz_theme_options{
            background-position:0px -48px;
        }

        body.compact .ico-lz_theme_options{
            background-position:-48px -48px;
        }
        body.compact .ico-lz_theme_options:hover,
        body.compact .current .ico-lz_theme_options{
            background-position:-48px 0px;
        }
    </style>
<?php }
osc_add_hook('admin_page_header','lzto_admin_menu_icon',9);


/**
 * Loads plugins configurations
 */
function lzto_conf(){
    osc_admin_render_plugin( osc_plugin_path( dirname(__FILE__) ) . '/view/conf.php' );
}

/**
 * Just make sure this function exists
 */
if( !function_exists('osc_uploads_url') ){
    function osc_uploads_url($item = ''){
        return osc_base_url().'oc-content/uploads/'.$item;
    }
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
 * Install
 */
function lzto_install(){
    if( !class_exists('Builder')){
        require osc_plugins_path('lz_theme_options').'lz_theme_options/builder.php';
    }
    return Builder::newInstance()->install();
}
/**
 * Uninstall
 */
function lzto_uninstall(){
    if( !class_exists('Builder')){
        require osc_plugins_path('lz_theme_options').'lz_theme_options/builder.php';
    }
    return Builder::newInstance()->uninstall();
}

/**
 * On theme delete
 */
function lzto_theme_delete(){
    $theme = Params::getParam('webtheme');
    Preference::newInstance()->dao->where('s_section', 'lz_theme_options');
    Preference::newInstance()->dao->like( 's_name', $theme );
    Preference::newInstance()->dao->delete( Preference::newInstance()->getTableName() );
    Session::newInstance()->_drop('ajax_files');
}

/**+
 * Registers necessary scripts
 */
function lzto_register_scripts(){
    osc_register_script('jquery', osc_plugin_url('lz_theme_options/assets').'assets/js/jquery.js' );
    if( !OC_ADMIN ){
        osc_register_script('jqueryui', osc_plugin_url('lz_theme_options/assets').'assets/js/jquery-ui.min.js' );
    }
    osc_register_script('colpick', osc_plugin_url('lz_theme_options/assets').'assets/js/colpick.js' );
    osc_register_script('icheck', osc_plugin_url('lz_theme_options/assets').'assets/js/icheck/jquery.icheck.min.js' );
    osc_register_script('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/toggles.min.js');
    osc_register_script('perfect_scroll', osc_plugin_url('lz_theme_options/assets').'assets/js/perfect-scrollbar.js');
    osc_register_script('lz_theme_options', osc_plugin_url('lz_theme_options/assets').'assets/js/lz_theme_options.js' );
}

function lzto_db_reset(){
    echo 'End LZTO';
}

function lzto_is_ready(){
    return defined('THEME_OPTIONS_ENABLED') && THEME_OPTIONS_ENABLED;
}

function lzto_isDemo(){
    return defined('DEMO') && DEMO == true && !osc_is_admin_user_logged_in();
}

/*************************************************************
 * HOOKS
 ************************************************************/
osc_add_hook( 'init', 'lzto_init', 0 );

osc_add_hook( 'plugin_categories_lz_theme_options/index.php', 	'lzto_settingsPost' );
osc_add_hook( 'ajax_lzto_post', 								'lzto_settingsPost' );
osc_add_hook( 'ajax_lzto_save_preset', 							'lzto_save_preset' );
osc_add_hook( 'ajax_lzto_load_preset', 							'lzto_load_preset' );
osc_add_hook( 'ajax_lzto_remove_preset', 						'lzto_remove_preset' );
osc_add_hook( 'ajax_lzto_upload_file', 							'lzto_uploadFile' );
osc_add_hook( 'ajax_lzto_delete_upload_file', 					'lzto_deleteUploadFile' );
osc_add_hook( 'ajax_lzto_load_upload_files', 					'lzto_loadUploadFiles' );
osc_add_hook( 'ajax_lzto_reset_form', 							'lzto_resetOptions' );
osc_add_hook( 'admin_header', 									'lzto_admin_header');

osc_add_hook( 'admin_menu', 									'lzto_admin_menu');
osc_add_hook( 'admin_menu_init', 								'lzto_admin_menu_init');
osc_add_hook( 'add_admin_toolbar_menus', 						'lzto_admin_toolbar_menus');

osc_register_plugin( osc_plugin_path( __FILE__ ), 'lzto_install' );
osc_add_hook( osc_plugin_path( __FILE__ ) . '_uninstall', 'lzto_uninstall' );


osc_add_hook( 'lz_demo_reset_complete', 'lzto_db_reset');

//osc_add_hook('cron_hourly', )

if( OSCLASS_VERSION < 3.3 ){
    osc_register_script('jquery-fineuploader', osc_plugin_url('lz_theme_options/assets').'assets/js/fineuploader/jquery.fineuploader.min.js' );
}