<div id="lzto">
	<div class="canvas">
		<iframe id="preview_iframe" src="<?php echo osc_base_url()?>" width="100%" height="100%" ></iframe>
	</div>
	<div class="menu">
		<div class="inner">
		<?php echo lzto_openForm();?>
			<h2><?php _e('LZ Theme options','lzto')?></h2>
			<div class="menu_container">
				<input type="hidden" name="page" value="plugins" />
				<input type="hidden" name="action" value="configure_post" />
	        	<input type="hidden" name="plugin" value="lz_theme_options/index.php" />
	        	<input type="hidden" name="plugin_short_name" value="lz_theme_options" />
				<ul>
				<?php
					$fields = lzto_getFields();
					foreach(  $fields as $grandpa => $field ){
						echo '<li>';
						echo '	<a href="#" >'.ucfirst( strtolower( lzto_getGroupTitle( $grandpa ) ) ).'<span></span></a>';
						echo '	<div class="menu_form">';
						echo '		<h3>'.lzto_getGroupTitle( $grandpa ).'</h3>';
						echo '      <div class="form-group-container">';
										lzto_prepareRowHtml( $field, $grandpa );
						echo '      </div>';
						echo '	</div>';
						echo '<li>';
					}
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
				?>
				</ul>
			</div>
			<div class="menu_action">
				<input type="reset" value="reset" class="btn"
					data-url="<?php echo osc_ajax_hook_url( 'lzto_reset_form' ); ?>"
						data-confirm="<?php _e('Do you realy wish to reset all your options to it\'s default values ?','lz_theme_options')?>"/>
						<a href="#" id="full_screen_btn"><img src="<?php echo osc_plugin_url('lz_theme_options/assets').'assets/img/expand_icon_green.png';?>" width="24"/> Full screen</a>
				
				<input type="submit" value="save" class="btn btn-submit"/>
			</div>
			<?php echo lzto_closeForm();?>
		</div>
        <div class="close_btn"></div>
	</div>
	<div class="info"><div class="inner"></div></div>
</div>

<script type="text/javascript">
var last_message_type = 'flashmessage-info';
$(document).ready(function(){

	$('#full_screen_btn').on('click', function(e){
		e.preventDefault();

		var adjust;
		if( !$('#lzto').hasClass('full_screen') ){
			adjust = function(){
				var timeout = setTimeout( function(){
					if( $('#header').height() == 0 ){
						window.clearTimeout(timeout);
						$(window).trigger('resize');
					} else {
						$(window).trigger('resize');
						window.clearTimeout(timeout);
						adjust();
					}
				}, 10);
			}
			$('#lzto').addClass('full_screen');
			$('#header').addClass('full_screen');
			$('#content-head').addClass('full_screen');
			$('#sidebar').addClass('full_screen');
			$('#content').addClass('full_screen');
			$('#footer').addClass('full_screen');
			$(this).html('<img src="<?php echo osc_plugin_url('lz_theme_options/assets').'assets/img/expand_icon_gray.png';?>" width="24"/> Exit full screen');

		} else {
			var i = 0;
			adjust = function(){
				var timeout = setTimeout( function(){
					if( i <= 10 ){
						$(window).trigger('resize');
						i++;
						adjust();
					} else {
						i = 0;
					}
				}, 10);
			}
			$('#lzto').removeClass('full_screen');
			$('#header').removeClass('full_screen');
			$('#content-head').removeClass('full_screen');
			$('#sidebar').removeClass('full_screen');
			$('#content').removeClass('full_screen');
			$('#footer').removeClass('full_screen');
			$(this).html('<img src="<?php echo osc_plugin_url('lz_theme_options/assets').'assets/img/expand_icon_green.png';?>" width="24"> Full screen');	
		}
		adjust();

	});

	lzto_init({
		upload_endpoint: 				'<?php echo osc_ajax_hook_url('lzto_upload_file'); ?>&ajax_upload=true&field_name=',
		upload_delete_endpoint: 		'<?php echo osc_ajax_hook_url('lzto_delete_upload_file'); ?>&ajax_upload=true&field_name=',
		upload_load_endpoint: 			'<?php echo osc_ajax_hook_url('lzto_load_upload_files'); ?>&ajax_upload=true&field_name=',
		upload_init_delete_endpoint: 	'<?php echo osc_ajax_hook_url('lzto_delete_upload_file'); ?>',
		upload_template: 				'<div class="qq-uploader">'+
            '<div class="qq-upload-drop-area" style="display: none;"><span>{dragZoneText}</span></div>'+
            '<div class="qq-upload-button"><button class="btn btn-mini btn-orange">{uploadButtonText}</button></div>'+
            '<span class="qq-drop-processing"  style="display: none;"><span>{dropProcessingText}</span><span class="qq-drop-processing-spinner"></span></span>'+
            '<ul class="qq-upload-list"></ul>'+
        '</div>',
        upload_file_template: 			'<li>'+
	            '<div class="qq-progress-bar-container-selector">'+
	    		'<div class="qq-progress-bar-selector qq-progress-bar"></div>'+
				'<span class="qq-upload-status-text-selector qq-upload-status-text"></span>'+
	    	'</div>'+
	    	'<span class="qq-upload-spinner-selector qq-upload-spinner"></span>'+
	    	'<span class="qq-upload-file-selector qq-upload-file"></span>'+
	    	'<span class="qq-upload-size-selector qq-upload-size"></span>'+
	    	'<a class="qq-upload-cancel-selector qq-upload-cancel" style="display: none;" href="#">Cancel</a>'+
	    	'<a class="qq-upload-delete-selector qq-upload-delete" href="#"></a>'+
	    	'<div class="thumb"><img src="<?php echo osc_plugin_url('lz_theme_options/assets/img').'img/thumb-placeholder.png'?>" /></div>'+
	    '<li>',
	});
});
<?php $path = 'admin'; ?>
function locationSelector(){
	$("#countryId").on("change",function(){
        var pk_c_code = $(this).val();
        <?php if($path=="admin") { ?>
            var url = '<?php echo osc_admin_base_url(true)."?page=ajax&action=regions&countryId="; ?>' + pk_c_code;
        <?php } else { ?>
            var url = '<?php echo osc_base_url(true)."?page=ajax&action=regions&countryId="; ?>' + pk_c_code;
        <?php }; ?>
        var result = '';

        if(pk_c_code != '') {

            $("#regionId").attr('disabled',false);
            $("#cityId").attr('disabled',true);

            $.ajax({
                type: "POST",
                url: url,
                dataType: 'json',
                success: function(data){
                    var length = data.length;

                    if(length > 0) {

                        result += '<option value=""><?php echo osc_esc_js(__("Select a region...")); ?></option>';
                        for(key in data) {
                            result += '<option value="' + data[key].pk_i_id + '">' + data[key].s_name + '</option>';
                        }

                        $("#region").before('<select name="regionId" id="regionId" ></select>');
                        $("#region").remove();

                        $("#city").before('<select name="cityId" id="cityId" ></select>');
                        $("#city").remove();

                        $("#regionId").val("");

                    } else {

                        $("#regionId").before('<input type="text" name="region" id="region" />');
                        $("#regionId").remove();

                        $("#cityId").before('<input type="text" name="city" id="city" />');
                        $("#cityId").remove();

                    }

                    $("#regionId").html(result);
                    $("#cityId").html('<option selected value=""><?php _e("Select a city..."); ?></option>');
                }
             });

         } else {

             // add empty select
             $("#region").before('<select name="regionId" id="regionId" ><option value=""><?php echo osc_esc_js(__("Select a region...")); ?></option></select>');
             $("#region").remove();

             $("#city").before('<select name="cityId" id="cityId" ><option value=""><?php echo osc_esc_js(__("Select a city...")); ?></option></select>');
             $("#city").remove();

             if( $("#regionId").length > 0 ){
                 $("#regionId").html('<option value=""><?php echo osc_esc_js(__("Select a region...")); ?></option>');
             } else {
                 $("#region").before('<select name="regionId" id="regionId" ><option value=""><?php echo osc_esc_js(__("Select a region...")); ?></option></select>');
                 $("#region").remove();
             }
             if( $("#cityId").length > 0 ){
                 $("#cityId").html('<option value=""><?php echo osc_esc_js(__("Select a city...")); ?></option>');
             } else {
                 $("#city").before('<select name="cityId" id="cityId" ><option value=""><?php echo osc_esc_js(__("Select a city...")); ?></option></select>');
                 $("#city").remove();
             }
             $("#regionId").attr('disabled',true);
             $("#cityId").attr('disabled',true);
         }
    });

    $("#regionId").on("change",function(){
        var pk_c_code = $(this).val();
        <?php if($path=="admin") { ?>
            var url = '<?php echo osc_admin_base_url(true)."?page=ajax&action=cities&regionId="; ?>' + pk_c_code;
        <?php } else { ?>
            var url = '<?php echo osc_base_url(true)."?page=ajax&action=cities&regionId="; ?>' + pk_c_code;
        <?php }; ?>

        var result = '';

        if(pk_c_code != '') {

            $("#cityId").attr('disabled',false);
            $.ajax({
                type: "POST",
                url: url,
                dataType: 'json',
                success: function(data){
                    var length = data.length;
                    if(length > 0) {
                        result += '<option selected value=""><?php echo osc_esc_js(__("Select a city...")); ?></option>';
                        for(key in data) {
                            result += '<option value="' + data[key].pk_i_id + '">' + data[key].s_name + '</option>';
                        }

                        $("#city").before('<select name="cityId" id="cityId" ></select>');
                        $("#city").remove();
                    } else {
                        result += '<option value=""><?php echo osc_esc_js(__('No results')); ?></option>';
                        $("#cityId").before('<input type="text" name="city" id="city" />');
                        $("#cityId").remove();
                    }
                    $("#cityId").html(result);
                }
             });
         } else {
            $("#cityId").attr('disabled',true);
         }
    });

    if( $("#regionId").attr('value') == "") {
        $("#cityId").attr('disabled',true);
    }

    if($("#countryId").length != 0) {
        if( $("#countryId").prop('type').match(/select-one/) ) {
            if( $("#countryId").attr('value') == "") {
                $("#regionId").attr('disabled',true);
            }
        }
    }
}
</script>