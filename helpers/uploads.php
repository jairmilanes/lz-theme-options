<?php

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

osc_add_hook( 'ajax_lzto_upload_file', 							'lzto_uploadFile' );
osc_add_hook( 'ajax_lzto_delete_upload_file', 					'lzto_deleteUploadFile' );
osc_add_hook( 'ajax_lzto_load_upload_files', 					'lzto_loadUploadFiles' );