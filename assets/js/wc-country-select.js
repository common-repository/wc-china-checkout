/*global wc_country_select_params */
jQuery( function( $ ) {

	// wc_country_select_params is required to continue, ensure the object exists
	if ( typeof woo_ch_wc_country_select_param === 'undefined' ) {
		return false;
	}
	
	function getEnhancedSelectFormatString() {
		if ( typeof wc_country_select_params === 'undefined' ) {
			return false;
		}
		return {
			'language': {
				errorLoading: function() {
					// Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
					return wc_country_select_params.i18n_searching;
				},
				inputTooLong: function( args ) {
					var overChars = args.input.length - args.maximum;

					if ( 1 === overChars ) {
						return wc_country_select_params.i18n_input_too_long_1;
					}

					return wc_country_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
				},
				inputTooShort: function( args ) {
					var remainingChars = args.minimum - args.input.length;

					if ( 1 === remainingChars ) {
						return wc_country_select_params.i18n_input_too_short_1;
					}

					return wc_country_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
				},
				loadingMore: function() {
					return wc_country_select_params.i18n_load_more;
				},
				maximumSelected: function( args ) {
					if ( args.maximum === 1 ) {
						return wc_country_select_params.i18n_selection_too_long_1;
					}

					return wc_country_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
				},
				noResults: function() {
					return wc_country_select_params.i18n_no_matches;
				},
				searching: function() {
					return wc_country_select_params.i18n_searching;
				}
			}
		};
	}
	
	var woo_ch_state_func_init = function(prefix){
		var $statebox   = $( prefix+'state');
		if($statebox.length==0){return;}
		
		$statebox.attr('data-prefix',prefix);
		$statebox.change(function(){
			var _prefix =$(this).data('prefix');
			var current_state = $(this).val();
			var $citybox = $( _prefix+'city'),
			input_name  = $citybox.attr( 'name' ),
			input_id    = $citybox.attr( 'id' );
			
			var $container = $citybox.parent().find( '.select2-container' );
			if($container.length>0)$container.remove();
			
			var state = typeof woo_ch_wc_country_select_param.countries[current_state]==='undefined'?null:woo_ch_wc_country_select_param.countries[current_state];
			if(!state||typeof state.citys==='undefined'){
				$citybox.replaceWith( '<input data-state="'+current_state+'" type="text" class="input-text" name="' + input_name + '" id="' + input_id + '"  style="width:100%;" />' );
			}else{
				var html = '<select data-state="'+current_state+'" name="' + input_name + '" id="' + input_id + '" style="width:100%;">';
				$.each(state.citys,function(key,val){
					html+='<option value="'+val.title+'">'+val.title+'</option>';
				});
				html+='</select>';
				$citybox.replaceWith(html);
				
				var select2_args = $.extend({
					placeholderOption: 'first',
					width: '100%'
				}, getEnhancedSelectFormatString() );

				$( _prefix+'city').select2( select2_args );
				// Maintain focus after select https://github.com/select2/select2/issues/4384
				$( _prefix+'city').on( 'select2:select', function() {
					$( this ).focus();
				} );
			}
			
			woo_ch_city_func_init(_prefix);
			$( _prefix+'city').change();
		});
		
	}
	
	var woo_ch_city_func_init = function(prefix){
		var $citybox   = $( prefix+'city');
		if($citybox.length==0){return;}
		
		$citybox.attr('data-prefix',prefix);
		$citybox.change(function(){
			var _prefix =$(this).data('prefix');
			var current_city_title = $(this).val();
			var current_state = $(this).data('state');
			
			var $address_1box = $( _prefix+'address_1'),
			input_name  = $address_1box.attr( 'name' ),
			input_id    = $address_1box.attr( 'id' );
			
			var state = typeof woo_ch_wc_country_select_param.countries[current_state]==='undefined'?null:woo_ch_wc_country_select_param.countries[current_state];
			var city = null;
			if(state&&typeof state.citys!=='undefined'){
				$.each(state.citys,function(key,val){
					if(val.title==current_city_title){
						city=val;
						return false;
					}
				});
			}
			
			var $container = $address_1box.parent().find( '.select2-container' );
			if($container.length>0)$container.remove();
			
			if(!city||typeof city.districts==='undefined'){
				$address_1box.replaceWith( '<input type="text" class="input-text " name="' + input_name + '" id="' + input_id + '"  style="width:100%;" />' );
			}else{
				var html = '<select name="' + input_name + '" id="' + input_id + '"  style="width:100%;" >';
				$.each(city.districts,function(key,val){
					html+='<option value="'+val+'">'+val+'</option>';
				});
				html+='</select>';
				$address_1box.replaceWith(html);
				
				var select2_args = $.extend({
					placeholderOption: 'first',
					width: '100%'
				}, getEnhancedSelectFormatString() );

				 $( _prefix+'address_1').select2( select2_args );
				// Maintain focus after select https://github.com/select2/select2/issues/4384
				 $( _prefix+'address_1').on( 'select2:select', function() {
					$( this ).focus();
				} );
			}
			
			$address_1box.change();
		});
	}
	
	var woo_ch_on_state_city_district_change = function(){
		var prefixs = ['.woocommerce-billing-fields #billing_','.woocommerce-shipping-fields #shipping_','.woocommerce-shipping-calculator #calc_shipping_'];

		for(var index=0;index<prefixs.length;index++){
			woo_ch_state_func_init(prefixs[index]);
		}
	};
	
	$(function(){
		woo_ch_on_state_city_district_change();
	});
	
	//如果系统省份已有下拉框变为文本框，那么city ,district也应该恢复为文本框
	$( document.body ).bind( 'country_to_state_changed',function(e,country_code,$element){
		woo_ch_on_state_city_district_change();
		
		var prefixs = ['.woocommerce-billing-fields #billing_','.woocommerce-shipping-fields #shipping_','.woocommerce-shipping-calculator #calc_shipping_'];
		
		for(var index=0;index<prefixs.length;index++){
			var prefix = prefixs[index];
			var $statebox =$(prefix+'state');
			if($statebox.length>0){
				$statebox.change();
			}
			
			//省份已不是下拉框，而是文本框
			var $statebox =$('input'+prefix+'state');
			if($statebox.length>0){
				var $citybox   = $( prefix+'city'),
				city = '';
				if($citybox.length>0){
					city =$citybox.val();
					var input_name  = $citybox.attr( 'name' ),
					input_id    = $citybox.attr( 'id' );
					var $container = $citybox.parent().find( '.select2-container' );
					if($container.length>0)$container.remove();
					$citybox.replaceWith( '<input data-state="'+$statebox.val()+'" type="text" class="input-text" name="' + input_name + '" id="' + input_id + '"  style="width:100%;" />' );
				}
				
				var $district   = $( prefix+'district');
				if($district.length>0){
					var input_name  = $district.attr( 'name' ),
					input_id    = $district.attr( 'id' );
					var $container = $district.parent().find( '.select2-container' );
					if($container.length>0)$container.remove();
					$district.replaceWith( '<input data-city="'+city+'" type="text" class="input-text" name="' + input_name + '" id="' + input_id + '"  style="width:100%;" />' );
				}
			}
		}
	});
});
