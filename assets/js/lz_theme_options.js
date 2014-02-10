
$(document).ready(function(){
	var last_message_type = 'flashmessage-info';
	var last_description = '';
	
	adjustContainerHeight();
	
	$(window).resize(function(e) {
		adjustContainerHeight();
    });
	
	/***************************************************************************
	 * THEME OPTIONS MENU
	 **************************************************************************/
	$('#lzto .menu ul > li > a').on('click', function(e){
		e.preventDefault();
		$(this).parent().find('div').eq(0).toggleClass('active');
		$(this).parents('.menu').find('.close_btn').eq(0).toggleClass('active');

		$('#lzto .menu .close_btn').off('click').on('click', function(e){
			e.preventDefault();
			$(this).parent().find('div.menu_form.active').removeClass('active');
			$(this).toggleClass('active');
			if( $('#lzto .info').hasClass('active') ){
				$('#lzto .info').removeClass('active').find('.inner').html('');
				last_description = '';
			}
		});
	});
	/***************************************************************************
	 * THEME OPTIONS COLORPICKERS
	 **************************************************************************/
	if( $('.colorpicker').length > 0 ){
		$('.colorpicker').each(function(index, elem){
			$(elem).colpick({
				layout:'hex',
				submit:0,
				colorScheme:'dark',
				onChange:function(hsb,hex,rgb,fromSetColor) {
					if(!fromSetColor) $(elem).val('#'+hex).css('border-color','#'+hex);
				}
			})
			.keyup(function(){
				$(this).colpickSetColor(this.value);
			});
			if( $(elem).val() !== "" ){
				$(elem).css('border-color', $(elem).val());
			}
		});
	}
	/***************************************************************************
	 * THEME OPTIONS SUBMIT HANDLER
	 **************************************************************************/
	 $('#lzto .form').submit( function(e){
		e.preventDefault();
		var self = $(this);
		var url  = $(this).attr('action');
		var data = $(this).serialize();
		self.find('p.error').remove();
		self.find('.error').removeClass('error');
		$.post(url, data, function(json){
			if( !json.status ){
				if( json.errors ){
					$.each( json.errors, function( name, error ){						
						if( typeof error == 'object' ){							
							$.each( error,function(field, msg ){
								var input = $('input[name^="lzto['+name+']['+field+']"]');
								input.parent().find('p.error').remove();
								input.addClass('error');
								input.after('<p class="error">'+msg+'</p>');
							});
						} 
					});
					showMessage( 'error', json.message );
				}
				return false;
			}
			showMessage( 'ok', json.message );
			reloadCanvas()
		},'json'); //
	});
	/***************************************************************************
	 * THEME OPTIONS COLOR SELECTORS
	 **************************************************************************/
	 if( $('.colorselector, .textureselector').length > 0 ){
		$('.colorselector, .textureselector').each( function( index, elem ){
			var self = $(elem);
			 self.find('ul > li > span').off('click').on('click', function(e){
				 e.preventDefault();
				 var value = $(this).data('value');
				 self.find('input').val(value);
				 $(this).parent().parent().find('li.active').removeClass('active');
				 $(this).parent().addClass('active');
			 });
		});
	}
	
	/***************************************************************************
	 * THEME OPTIONS FORM RESET
	 **************************************************************************/
	$('#lzto input[type="reset"]').off('click').on('click', function(e){
		e.preventDefault();
		if( confirm( $(this).data('confirm') ) ){
			var url = $(this).data('url');
			$.post( url, {}, function(json){
				if( !json.status ){
					showMessage( 'error', json.message );	
				} else {
					showMessage( 'success', json.message );	
					setTimeout(function(){
						location.reload(true);
					},1000);
				}
				
			},'json');
		}
	});
	/***************************************************************************
	 * THEME OPTIONS DESCRIPTIONS
	 **************************************************************************/
	$('#lzto .description').each( function(index, elem){
		$(elem).off('click').on('click', function(e){			
			
			if( $(elem).data('field') !== last_description ){
			
				$('#lzto .info').find('.inner').html('');
				$('#lzto .info').addClass('active').find('.inner').html( $(elem).data('description') );
				last_description = $(elem).data('field');
			
			} else {
				$('#lzto .info').removeClass('active').find('.inner').html('');
				last_description = '';
			}
			
		});
	});
	
	/***************************************************************************
	 * THEME OPTIONS SWICHS
	 **************************************************************************/
	if( $('#lzto input.toggleSwitch').length > 0 ){
		$('#lzto input.toggleSwitch').each( function(index, elem){
			$(elem).parent().addClass('toggle-dark');
			var id = 'switch_'+$(elem).attr( 'id' );
			var template = '<div id="'+id+'" data-checkbox="'+$(elem).attr( 'id' )+'"></div>';
			$(elem).before( template );
			$(elem).css( 'display','none' ).parent().find('label').css( 'display','none' );
			var data = { 
				checkbox: $(elem),
				width:    120, // width used if not set in css
   				height:   30, // height if not set in css
    			type:     'select',
				on:       $(elem).is(':checked'),
				text: {
				  on: 'ON', // text for the ON position
				  off: 'OFF' // and off
				}
			};
			$('#'+id).toggles( data ).on('toggle', function (e, active) {
				$(elem).prop('checked', active );
			});
		});
	}
	
	
	/***************************************************************************
	 * THEME OPTIONS DESCRIPTIONS
	 **************************************************************************/
	 $('#lzto .menu_form fieldset').each( function(index, elem){
		var parent = $(elem);
		var origin_height = $(this).find('.fieldset_inner').outerHeight(true);
			
		parent.find('legend').on('click', function(e){
			if( parent.hasClass('open') ){
				parent.removeClass('open');
				parent.find('.fieldset_inner').css({ minHeight: 25+'px' });
			} else {
				parent.find('.fieldset_inner').css({ minHeight: origin_height+'px' });
				parent.addClass('open');
			}
		});
		parent.find('legend').trigger('click');
	 });
	 
	 
	 
	  if( $('.sliderange_field').length > 0 ){
		  $('.sliderange_field').each( function(index, elem){
			  var slider = $(elem).find('.slider');
			  var type = slider.data('type');
			  var smin = slider.data('min');
			  var smax = slider.data('max');
			  var step = $(elem).data('step');
			  var val_min = parseInt( $('input#'+slider.attr('id')+"_min").val() );
			  var val_max = parseInt( $('input#'+slider.attr('id')+"_max").val() );
			  
			  if( !val_min ) val_min = 0;
		      if( !val_max ) val_max = 10000;

			  switch( type ){
					case "min":
					var options = {
						  range: "min",
						  min: smin,
						  max: smax,
						  value: val_max,
						  step: step,
						  slide: function( event, ui ) {
							 $('input#'+slider.attr('id')+"_max").val(ui.value);
						  }
					  };
						break; 
					case "max":
					var options = {
						  range: "max",
						  min: smin,
						  max: smax,
						  value: val_min,
						  step: step,
						  slide: function( event, ui ) {
							 $('input#'+slider.attr('id')+"_min").val( ui.value );
						  }
					  };
						break;
					default:
					var options = {
						  range: true,
						  min: smin,
						  max: smax,
						  values: [
						  	val_min,
							val_max
						  ],
						  step: step,
						  slide: function( event, ui ) {
							  console.log(ui);
							 $('input#'+slider.attr('id')+"_min").val(ui.values[ 0 ]);
							 $('input#'+slider.attr('id')+"_max").val(ui.values[ 1 ]);
						  }
					  };
						break; 
			  }
			  slider.slider(options);

		  });
	  }
	 
});

function reloadCanvas(){
	parent.frames['preview_iframe'].window.location.reload(true);	
}
/***************************************************************************
 * THEME OPTIONS SETTINGS DEPENDENT FUNCTIONS
 **************************************************************************/
function lzto_init( settings ){
	if( $('#lzto .upload_button').length > 0 ){
		$('#lzto .upload_button').each( function( index, elem ){
			
			var field_name = $(this).data('name');
			var group = $(this).data('group');
			var self = $(elem);
		
			$(elem).fineUploader({
			   //debug: true,
				multiple: false,
				request: {
					endpoint: settings.upload_endpoint+field_name+'&group='+group
				},
				deleteFile: {
					enabled: true,
					method: "POST",
					forceConfirm: true,
					endpoint: settings.upload_delete_endpoint+field_name+'&group='+group
				},
				template: settings.upload_template,
				fileTemplate: settings.upload_file_template
					
			}).on('delete', function(event, id, name, json){
				reloadCanvas();
			}).on('complete', function(event, id, name, json){
				if( json.thumbnailUrl ){
					self.find('.thumb')
							.find('img')
								.attr({ 
									id: field_name+'_thumb',
									src : json.thumbnailUrl, 
									//width : 150, 
									//height : 16, 
									alt : "Test Image", 
									title : "Test Image"
								});
					
				}
				
				setTimeout(function(){
					self.find('.qq-progress-bar').animate({backgroundColor: '#333'},1000);
				},2000);
				
				reloadCanvas();
				
			}).on('progress',function( id, name, uploadedBytes, totalBytes ){
				var per = parseInt( ( ( uploadedBytes / totalBytes ) * 100 ) );
				self.find('.qq-progress-bar').css({ width: per+'%' });
			});
				
				
			var url = settings.upload_load_endpoint+field_name+'&group='+group
				
			$.get( url, {}, function( json ){

				if( false !== json.status ){
					delete json.status;
					$.each( json, function( field, options ){
						
						var list = self.find('.qq-upload-list');
	
						list.append( settings.upload_file_template );
	
						$( 'li', list ).addClass('qq-upload-success');
						$( '.qq-upload-file', list ).html(options.name);
						$( '.qq-upload-size', list ).html(options.size);
						$( '.thumb', list ).find('img').attr( 'src', options.thumbnailUrl );
						
						list.find( '.qq-upload-delete' ).on('click', function(){
							if( confirm('Are you sure you want to delete '+options.name+'?') ){
								var delete_button = $(this);
								var delete_url = settings.upload_init_delete_endpoint;
								var data = { 
									qquuid: options.uuid, 
									field_name: field_name,
									ajax_upload: true 
								};
								$.post( delete_url, data, function( json ){
									if( json.success ){
										delete_button.parent().remove();
									}
								},'json');
							}
						});
					});
				}
			},'json');
		});
	}
}


var message_timeout = 0;
function showMessage( type, message ){
	var msg_container = $('.jsMessage.flashmessage');
	msg_container.removeClass( last_message_type+' hide' ).addClass('flashmessage-'+type);
	last_message_type = 'flashmessage-'+type;
	msg_container.find('p').html( message );
	msg_container.slideDown('fast');
	clearTimeout( message_timeout );
	message_timeout = setTimeout( function(){
		msg_container.slideUp('fast');
	}, 3000 );
}

function adjustContainerHeight(){
	var lzto_height = parseInt($('#lzto').height());
	var conteiner_height = parseInt($('#content-page').height());
	var windowHeight = window.innerHeight;
	//console.log('WINDOW height = '+windowHeight);
    var lzto_height =       parseInt($('#lzto').outerHeight(true));
	//console.log('LZTO height = '+lzto_height);
    var header_height =     parseInt($('#header').outerHeight(true));
	//  console.log('HEADER height = '+header_height);
    var sub_header_height = parseInt($('#content-head').outerHeight(true));
	// console.log('SUB HEADER height = '+sub_header_height);
    var footer_height =     parseInt($('#footer').outerHeight(true));
	//console.log('FOOTER height = '+footer_height); 
    
    var newHeight = windowHeight - header_height - sub_header_height - footer_height;
   // console.log('NEW height = '+newHeight);
    $('#lzto').height(newHeight);
}

function truncate(n, len) {
    var ext = n.substring(n.lastIndexOf(".") + 1, n.length).toLowerCase();
    var filename = n.replace('.'+ext,'');
    if(filename.length <= len) {
        return n;
    }
    filename = filename.substr(0, len) + (n.length > len ? '[...]' : '');
    return filename + '.' + ext;
};