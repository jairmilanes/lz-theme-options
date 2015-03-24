<?php
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


/*************************************************************
 * HOOKS
 ************************************************************/
osc_add_hook( 'ajax_lzto_save_preset', 							'lzto_save_preset' );
osc_add_hook( 'ajax_lzto_load_preset', 							'lzto_load_preset' );
osc_add_hook( 'ajax_lzto_remove_preset', 						'lzto_remove_preset' );