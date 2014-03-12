<?php 
/*
	Plugin Name: Theme options
	Plugin URI: http://www.jmilanes.com.br/
	Description: Theme options plugin
	Version: 1.0
	Author: Jair Milanes Junior
	Author URI: http://www.jmilanes.com.br/
	Short Name: lzto
	Plugin update URI: lzto
*/
/**
 * Load
 */
function lzto_init(){
	fb('Lzto init started');
	
	$theme_options = false;

	$theme = osc_theme();
	
	if( function_exists('lz_demo_selected_theme') ){
		$theme = lz_demo_selected_theme();
	}
	
	$file = osc_themes_path().$theme.'/options.php';

	fb('Lzto theme is '.$theme);
	
	fb('Lzto theme file is '.$file);
	if( file_exists( $file ) ){
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
		require $file;
		
		if( function_exists( 'get_theme_options' ) ){
			require osc_plugins_path('lz_theme_options').'lz_theme_options/builder.php';
			$theme_options = Builder::newInstance()->setOptions( get_theme_options() );
			if( $theme_options ){
				lzto_register_scripts();
			}
		}
	}
	
	define( 'THEME_OPTIONS_ENABLED', $theme_options );
	
	fb('Lzto is enabled '.(( !THEME_OPTIONS_ENABLED )? 'no' : 'yes' ) );
	
	if( THEME_OPTIONS_ENABLED ){
		$themes = WebThemes::newInstance()->getListThemes();
		foreach( $themes as $theme ){
			osc_add_hook('theme_delete_'.$theme, 'lzto_theme_delete');
		}
	}
	
	return true;
}

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

function lzto_getGroupTitle( $group_slug ){
	return Builder::newInstance()->getGroupName( $group_slug );
}

/**
 * Process the admin panel settings post
 */
function lzto_settingsPost($settings){
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
	Builder::newInstance()->resetOptions();
}

/**
 * Loads files
 */
function lzto_admin_header(){
	if( THEME_OPTIONS_ENABLED ){
		if( ( OC_ADMIN && Params::getParam('page') == 'plugins' 
				&& Params::getParam('file') == 'lz_theme_options/view/settings.php' )
					|| !OC_ADMIN ){
			osc_enqueue_style('jqueryui', osc_plugin_url('lz_theme_options/assets').'assets/css/ui-theme/jquery-ui.min.css' );
			osc_enqueue_style('icheck', osc_plugin_url('lz_theme_options/assets').'assets/js/icheck/skins/polaris/polaris.css' );
			osc_enqueue_style('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/toggles.css' );
			osc_enqueue_style('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/themes/toggles-dark.css' );
			osc_enqueue_style('colpick', osc_plugin_url('lz_theme_options/assets').'assets/css/colpick.css' );
			osc_enqueue_style('lz_options', osc_plugin_url('lz_theme_options/assets').'assets/css/lz_options.css' );
			if( !OC_ADMIN ){
				osc_enqueue_style('lz_options_extra', osc_plugin_url('lz_theme_options/assets').'assets/css/extra.css' );
			}
			osc_enqueue_script('jquery');
			osc_enqueue_script('jqueryui');
			osc_enqueue_script('icheck');
			osc_enqueue_script('toggles');
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
		  // <li><a href="' . osc_admin_render_plugin_url(osc_plugin_folder(__FILE__) . '/view/conf.php') . '">&raquo; ' . __('Configuration', 'lzto') . '</a></li>	
}

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
 * Creates a link on the admin toolbar menu
 */
function lzto_admin_toolbar_menus(){
	if( defined( 'THEME_OPTIONS_ENABLED' ) && true === THEME_OPTIONS_ENABLED ){
		osc_admin_menu_appearance( __('LZ Theme options', 'lz_theme_options'), osc_admin_render_plugin_url( osc_plugin_path( dirname(__FILE__) ) . '/view/settings.php'), 'lz_theme_options');
	}
}

/**
 * Install
 */
function lzto_uninstall(){
	Preference::newInstance()->delete( array( 's_section' => 'lz_theme_options' ) );
	Preference::newInstance()->delete( array( 's_section' => 'lz_theme_options_uploads' ) );
	Session::newInstance()->_drop('ajax_files');
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

function lzto_register_scripts(){
	osc_register_script('jquery', osc_plugin_url('lz_theme_options/assets').'assets/js/jquery.js' );
	osc_register_script('jqueryui', osc_plugin_url('lz_theme_options/assets').'assets/js/jquery-ui.min.js' );
	osc_register_script('colpick', osc_plugin_url('lz_theme_options/assets').'assets/js/colpick.js' );
	osc_register_script('icheck', osc_plugin_url('lz_theme_options/assets').'assets/js/icheck/jquery.icheck.min.js' );
	osc_register_script('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/toggles.min.js');
	osc_register_script('lz_theme_options', osc_plugin_url('lz_theme_options/assets').'assets/js/lz_theme_options.js' );
}

osc_add_hook( 'init', 'lzto_init', 1 );
/*
if( !OC_ADMIN ){
	if( Params::existParam('hook') && strstr( Params::getParam('hook'), 'lzto_') ){
		lzto_init();
	} else {
		osc_add_hook( 'before_html', 'lzto_init' );
	}
} else {
	lzto_init();
}
*/
osc_add_hook('plugin_categories_lz_theme_options/index.php', 'lzto_settingsPost' );

osc_add_hook( 'ajax_lzto_upload_file', 'lzto_uploadFile' );
osc_add_hook( 'ajax_lzto_delete_upload_file', 'lzto_deleteUploadFile' );
osc_add_hook( 'ajax_lzto_load_upload_files', 'lzto_loadUploadFiles' );
osc_add_hook( 'ajax_lzto_reset_form', 'lzto_resetOptions' );

osc_add_hook('admin_header', 'lzto_admin_header');
osc_add_hook('admin_menu', 'lzto_admin_menu');

osc_add_hook( osc_plugin_path( __FILE__ ) . '_uninstall', 'lzto_uninstall' );

osc_add_hook('add_admin_toolbar_menus', 'lzto_admin_toolbar_menus');

// @todo implement config page
//osc_add_hook( osc_plugin_path( __FILE__ ) . '_configure', 'lzto_conf' );

osc_register_plugin( osc_plugin_path( __FILE__ ), '' );

if( OSCLASS_VERSION < 3.3 ){
	osc_register_script('jquery-fineuploader', osc_plugin_url('lz_theme_options/assets').'assets/js/fineuploader/jquery.fineuploader.min.js' );
}


