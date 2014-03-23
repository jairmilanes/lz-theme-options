
$(document).ready(function(){
	var last_message_type = 'flashmessage-info';
	var last_description = '';
	
	adjustContainerHeight();
	
	$(window).resize(function(e) {
		adjustContainerHeight();
    });
	
	/*	
	var menu_timeout;

	 $('#lzto .menu').on('mouseleave', function(){
		 menu_timeout = setTimeout(function(){
			 if( $('#lzto .menu.open').length ){
			 	$('#lzto .menu .toggle_btn').trigger('click');
			 }
	 	 },3000);
	 });
	 
	 $('#lzto .menu').on('mouseover', function(){
		 window.clearTimeout(menu_timeout);
	 });
	 */

	if( $('#presets_box').length > 0 ){
		if( $('#lzto_preset_create').length > 0 ){
			$('#lzto_preset_create').on('click', function(e){
				e.preventDefault();
				newDialog( 
					'Name your preset',
					'<p>What is the name of your preset?</p><input type="text" name="preset_name" id="preset_name" />',
					{ "Create preset?": function() {
						
						  showDialogLoading($(this));
					
						  var $this = $(this);
						  var preset_name = $('input#preset_name');
						  
						  $.post( $('#lzto_preset_create').attr('href'), { preset_name: preset_name.val() }, function(json){
								if( !json ){
									showMessage('error', 'Could not save preset!');
									$this.dialog( "close" );
									return false;
								}
								if( !json.status ){
									showMessage('error', json.message);
									$this.dialog( "close" );
									return false;
								}					
								showMessage('ok', json.message);
								if( json.presets ){
									$('#presets_box > ul').html('');
									refreshPresets(json.presets);
								}
								hideDialogLoading($(this));
								$this.dialog( "close" );
								return true;					
						  },'json');
					},
					Cancel: function() {
					  $( this ).dialog( "close" );
					}
				});
			});
		}
		init_presets();
	}
	/***************************************************************************
	 * THEME OPTIONS TOGGLE
	 **************************************************************************/
	var last_menu_width = 0;
	$('#lzto .menu .toggle_btn').on('click', function(e){
		e.preventDefault();
		if( $(this).hasClass('active') ){
			$(this).removeClass('active');
			$(this).parent().removeClass('open');
			$('#lzto .menu').css('width', '0%');
			$('#lzto .menu > .inner').css('overflow', 'hidden');
			$('#lzto .canvas').css({'width': '100%','margin-left': '0'});
			if( $('#lzto .menu_form.active').length > 0 ){
				$('#lzto .menu .close_btn').trigger('click');	
			}
			$('#lzto .info').hide();
		} else {
			$(this).addClass('active');
			$('#lzto .menu').addClass('open').css('width', '' );
			$('#lzto .menu > .inner').css('overflow', 'auto');
			$('#lzto .info').hide();
			//window.clearTimeout(menu_timeout);
		}
	});
	$('#lzto .menu .toggle_btn').trigger('click');
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
	$('#lzto .menu_form .form-group-container').perfectScrollbar({
		suppressScrollX: true
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
		showOptionsLoader();
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
			reloadCanvas();
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
	$('#lzto .reset').off('click').on('click', function(e){
		e.preventDefault();
		if( confirm( $(this).data('confirm') ) ){
			var url = $(this).data('url');
			$.post( url, {}, function(json){
				if( !json.status ){
					showMessage( 'error', json.message );	
				} else {
					showMessage( 'ok', json.message );	
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
	$('#lzto .menu .description').each( function(index, elem){
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
			$(elem).css( 'display','none' );//.parent().find('label').css( 'display','none' );
			var data = { 
				checkbox: $(elem),
				width:    ( ( $(elem).parent().width() / 100 ) * 28 ), // width used if not set in css
   				height:   25, // height if not set in css
    			
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
	 * SELECTS
	 **************************************************************************/
	if( typeof selectUi !== 'function'){
		$('select').each(function(){
			selectUi($(this));
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
						  selection: 'none',
						  slide: function( ev, ui ) {
								 $('input#'+slider.attr('id')+"_max").val(ui.value);
							}
					  };
					/*
				    var slide_ev = function( ev ) {
							 $('input#'+slider.attr('id')+"_max").val(ev.value);
						}; */
						break; 
					case "max":
					var options = {
						  range: "max",
						  min: smin,
						  max: smax,
						  value: val_min,
						  step: step,
						  slide: function( ev, ui ) {
							 $('input#'+slider.attr('id')+"_min").val( ui.value );
					 	 }
					  };
					  /*
					 var slide_ev = function( ev ) {
							 $('input#'+slider.attr('id')+"_min").val( ev.value );
					  };*/
						break;
					default:
					var options = {
						  range: true,
						  min: smin,
						  max: smax,
						  value: [
						  	val_min,
							val_max
						  ],
						  step: step,
						  slide: function( ev, ui ) {
							 $('input#'+slider.attr('id')+"_min").val(ui.values[ 0 ]);
							 $('input#'+slider.attr('id')+"_max").val(ui.values[ 1 ]);
					  	  }
					  };
					  /*
					  var slide_ev = function( ev ) {
							 $('input#'+slider.attr('id')+"_min").val(ev.values[ 0 ]);
							 $('input#'+slider.attr('id')+"_max").val(ev.values[ 1 ]);
					  }; */
						break; 
			  }
			  
				slider.slider(options);
			  
		  });
	 }
	
	
	  $('#lzto .menu_action a').tooltip({placement: 'right'});
	
	 
});


function showDialogLoading( $dialog ){
	 $dialog.append('<img class="loading" src="/oc-content/plugins/lz_theme_options/assets/img/loader32.gif" width="32" />');
	 $('*', $dialog).fadeOut('fast');
	 $dialog.find('img.loading').fadeIn('fast');
}
function hideDialogLoading( $dialog ){
	$dialog.find('img.loading').fadeOut('fast', function(){
		$(this).remove();	
	});
	$('*', $dialog).fadeIn('fast');
}
function showOptionsLoader(){
	 $('#lzto .options_loader').addClass('active');	
}
function hideOptionsLoader(){
	 $('#lzto .options_loader').removeClass('active');	
}
function newDialog( title, desc, actions ){
	$("#preset_dialog").html(desc);
	var box = $( "#preset_dialog" ).dialog({
		  title: title,
		  autoOpen: false,
		  show: {
			  effect: "drop",
			  duration: 350
		  },
		  hide: {
			  effect: "puff",
			  duration: 350
		  },
		  modal: true,
		  buttons: actions
	});
	return box.dialog( "open" );
}
function init_presets(){
	$('#presets_box > ul > li > a').each( function(index, elem){
		
		$(elem).on('click', function(e){
			e.preventDefault();
			var url = $(this).attr('href');
			newDialog( 
				'Load preset!', 
				'All the uploaded files will be overwriten by the ones in the new preset, do you wish to load this new preset?', {
				'Load': function(){
					var $this = $(this);
					$.post( url, function(json){
						if( !json ){
							showMessage('error', 'Could not load preset!');
							$this.dialog('close');
							return false;
						}
						if( !json.status ){
							showMessage('error', json.message);
							$this.dialog('close');
							return false;
						}					
						showMessage('ok', json.message+'<br/><strong>Be pacient we are reloading!</strong>' );
						$this.dialog('close');
						setTimeout(function(){
							window.location.reload();
						},1000);
						return true;	
					},'json');
				},
				'Cancel': function(){
					$(this).dialog('close');
				}
			});
		});
		
		$(elem).next('span.delete_preset').on('click', function(e){
			e.preventDefault();
			var url = $(this).data('url');
			newDialog( 
				'Delete preset!', 
				'This action cant be undone, are you sure?', {
					'Delete preset': function(){
						var $this = $(this);
						$.post( url, function(json){
							if( !json ){
								showMessage('error', 'Could not delete preset!');
								return false;
							}
							if( !json.status ){
								showMessage('error', json.message);
								return false;
							}					
							showMessage('ok', json.message );
							refreshPresets(json.presets);
							$this.dialog('close');
							return true;
						},'json');
					},
					'Cancel': function(){
						$(this).dialog('close');
					}
			});
		});
	});
	
}
function refreshPresets(presets){
	var img = '/oc-content/plugins/lz_theme_options/assets/img/close-icon15.png';
	$('#presets_box > ul').html('');
	$.each( presets, function(name, preset){
		if( name == 'empty' ){
			$('#presets_box > ul').append(
				'<li>'+preset.title+'</li>'
			);
		} else {
			$('#presets_box > ul').append(
				'<li><a href="'+preset.load_url+'">'+preset.title+'</a>'+
				'<span class="delete_preset" data-url="'+preset.delete_url+'"><img src="'+img+'" width="16"/></span></li>'
			);
		}
	});
	init_presets();
}


if( typeof selectUi !== 'function'){

	function selectUi(thatSelect){
		var uiSelect = $('<a href="#" class="select-box-trigger"></a>');
		var uiSelectIcon = $('<span class="select-box-icon"><div class="ico ico-20 ico-drop-down"></div></span>');
		var uiSelected = $('<span class="select-box-label">'+thatSelect.find("option:selected").text()+'</span>');
	
		thatSelect.css('filter', 'alpha(opacity=40)').css('opacity', '0');
		thatSelect.wrap('<div class="select-box '+thatSelect.attr('class')+'" />');
	
	
		uiSelect.append(uiSelected).append(uiSelectIcon);
		thatSelect.parent().append(uiSelect);
		uiSelect.click(function(){
			return false;
		});
		thatSelect.change(function(){
			uiSelected.text(thatSelect.find('option:selected').text());
		});
		thatSelect.on('remove', function() {
			uiSelect.remove();
		});
	}
}
function reloadCanvas(){
	var frame = $('iframe#preview_iframe')
	/*
	var type = 1;
	if( !( frame && frame.length > 0 ) ){
		frame = $('iframe#preview_iframe');
		type = 2;
	}
	*/
	$(frame).on('load', function(){
		hideOptionsLoader();
	}).attr('src', $('iframe#preview_iframe').attr('src') );
	//console.log($(frame));
	/*
	if( frame ){
		
	}
	switch(type){
		case 1:
			frame.window.location.reload(true);	
			break;
		case 2:
			$(frame).attr('src', $('iframe#preview_iframe').attr('src') );
			break;
	};*/
	return true;
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
					
			}).on('submit', function(){
				showOptionsLoader();
			}).on('delete', function(event, id, name, json){
				showOptionsLoader();
			}).on('deleteComplete', function(event, id, name, json){
				reloadCanvas();
			}).on('complete', function(event, id, name, json){
				showOptionsLoader();
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
    
    var newHeight = windowHeight - header_height; //sub_header_height footer_height
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