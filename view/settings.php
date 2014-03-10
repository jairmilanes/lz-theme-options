	<?php 
	$lz_options = defined( 'THEME_OPTIONS_ENABLED' ) && THEME_OPTIONS_ENABLED === true;
	$theme = ( Params::existParam('theme') )? '&theme='.Params::getParam('theme') : ''; ?>
	<div id="lzto">
		<div class="canvas <?php echo ( $lz_options !== false )? 'no-options' : 'options'; ?>">
			<iframe id="preview_iframe" style="visibility:hidden;" onload="this.style.visibility = 'visible';" src="<?php echo osc_base_url(true).$theme;?>" width="100%" height="100%" ></iframe>
		</div>
		<?php if( $lz_options ){  ?>
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
						?>
						</ul>
					</div>
					<div class="menu_action">
						<input type="reset" value="reset" class="btn" data-url="<?php echo osc_ajax_hook_url( 'lzto_reset_form' ); ?>" data-confirm="<?php _e('Do you realy wish to reset all your options to it\'s default values ?','lz_theme_options')?>"/>
						<?php if(OC_ADMIN){ ?>
						<a href="#" id="full_screen_btn"><img src="<?php echo osc_plugin_url('lz_theme_options/assets').'assets/img/expand_icon_green.png';?>" width="24"/> <span>Full screen</span></a>
						<?php }?>
						<input type="submit" value="save" class="btn btn-submit"/>
					</div>
					<?php echo lzto_closeForm();?>
				</div>
				<div class="toggle_btn active"></div>
		        <div class="close_btn"></div>
		        
			</div>
			<div class="info"><div class="inner"></div></div>
		<?php } ?>
	</div>
	
	<script type="text/javascript">
	var last_message_type = 'flashmessage-info';
	$(document).ready(function(){
		<?php if(OC_ADMIN){ ?>
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
				$(this).html('<img src="<?php echo osc_plugin_url('lz_theme_options/assets').'assets/img/expand_icon_gray.png';?>" width="24"/> <span>Exit full screen</span>');
	
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
		<?php }?>
		<?php if( $lz_options ){  ?>
			lzto_init({
				upload_endpoint: 				'<?php echo osc_ajax_hook_url('lzto_upload_file'); ?>&ajax_upload=true&field_name=',
				upload_delete_endpoint: 		'<?php echo osc_ajax_hook_url('lzto_delete_upload_file'); ?>&ajax_upload=true&field_name=',
				upload_load_endpoint: 			'<?php echo osc_ajax_hook_url('lzto_load_upload_files'); ?>&ajax_upload=true&field_name=',
				upload_init_delete_endpoint: 	'<?php echo osc_ajax_hook_url('lzto_delete_upload_file'); ?>',
				upload_template: 				
					'<div class="qq-uploader">'+
		            	'<div class="qq-upload-drop-area" style="display: none;"><span>{dragZoneText}</span></div>'+
		            	'<div class="qq-upload-button"><button class="btn btn-mini btn-orange">{uploadButtonText}</button></div>'+
		            	'<span class="qq-drop-processing"  style="display: none;"><span>{dropProcessingText}</span><span class="qq-drop-processing-spinner"></span></span>'+
		            	'<ul class="qq-upload-list"></ul>'+
		        	'</div>',
		        upload_file_template: 			
				    '<li>'+
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
		<?php } ?>
	});
</script>
