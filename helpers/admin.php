<?php


/**
 * Loads files
 */
function lzto_admin_header(){

    if( osc_plugin_is_enabled('lz_theme_options/index.php') ){
        if( ( OC_ADMIN && Params::getParam('page') == 'plugins' && Params::getParam('file') == 'lz_theme_options/view/settings.php' ) || !OC_ADMIN ){
            if( !OC_ADMIN ){
                osc_enqueue_style('jquery-ui', osc_plugin_url('lz_theme_options/assets').'assets/css/ui-theme/jquery-ui.custom.min.css' );
            }

            osc_enqueue_style('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/themes/toggles-dark.css' );
            //osc_enqueue_style('toggles', osc_plugin_url('lz_theme_options/assets').'assets/js/toggles/toggles.css' );
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
 * Process the admin panel settings post
 */
function lzto_settingsPost(){

    if( THEME_OPTIONS_ENABLED && Params::existParam('lzto') ){

        Builder::newInstance()->save();
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
 * Inserts a link on the admin menu bar

function lzto_admin_menu() {
    echo '<h3><a href="#">' . __('Theme options', 'lzto') . '</a></h3>
		  <ul>
		      <li><a href="' . osc_admin_render_plugin_url(osc_plugin_folder('lz_theme_options') . '/view/settings.php') . '">&raquo; ' . __('Theme options', 'lzto') . '</a></li>
		  </ul>';
}
 * */
function lzto_admin_menu_init(){
    osc_add_admin_menu_page(
        __('Theme Options', 'lzto'),
        osc_admin_render_plugin_url(osc_plugin_folder('lz_theme_options/index.php') . '/view/settings.php'),
        'lz_theme_options',
        'administrator'
    );
}


/**
 * Creates a link on the admin toolbar menu
 */
function lzto_admin_toolbar_menus(){
    if( lzto_is_ready() ){
        osc_admin_menu_appearance(
            __('Theme Options', 'lz_theme_options'),
            osc_admin_render_plugin_url( osc_plugin_path( 'lz_theme_options' ) . '/view/settings.php'),
            'lz_theme_options');
    }
}

/**
 * Customize admin icon
 */
function lzto_admin_menu_icon() {
    if( lzto_is_ready() ){?>
    <style>
        #sidebar .oscmenu li li a#lz_theme_options {
            padding: 8px 15px;
            border-top: 1px solid #eee;
            color: #c43c35;
            font-size: 14px;
            font-weight: bold;
        }
        .ico-lz_theme_options {
            background-image: url('<?php echo osc_plugin_url('lz_theme_options/index.php').'assets/img/admin_panel_icon.png'; ?>') !important;
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
<?php } }

/**
 * Install
 */
function lzto_install(){
    return OSCLztoModel::newInstance()->install();
}

/**
 * Uninstall
 */
function lzto_uninstall(){
    return OSCLztoModel::newInstance()->uninstall();
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

/**
 * Hourly cron job to clear demo user settings
 */
function lzto_db_reset(){
    OSCLztoModel::newInstance()->cleanUpUsersSettings();
}
osc_add_hook( 'cron_hourly', 'lzto_db_reset');






/*************************************************************
 * HOOKS
 ************************************************************/
osc_add_hook( 'ajax_lzto_reset_form', 							'lzto_resetOptions' );
osc_add_hook( 'plugin_categories_lz_theme_options/index.php', 	'lzto_settingsPost' );
osc_add_hook( 'ajax_lzto_post', 								'lzto_settingsPost' );
osc_add_hook( 'admin_header', 									'lzto_admin_header');
osc_add_hook( 'admin_menu', 									'lzto_admin_menu');
osc_add_hook( 'admin_menu_init', 								'lzto_admin_menu_init');
osc_add_hook( 'add_admin_toolbar_menus', 						'lzto_admin_toolbar_menus');
osc_add_hook('admin_page_header',                               'lzto_admin_menu_icon',9);
