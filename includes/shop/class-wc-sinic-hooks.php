<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once 'class-wc-sinic-wp-api.php';
/**
 * wordpress apis
 * 
 * @author rain
 * @since 1.0.0
 */
class WC_Sinic_Hooks{
    public static function init(){
        if(!defined('xh_http_headers_useragent')){
            define('xh_http_headers_useragent', 1);
            add_filter( 'http_headers_useragent',__CLASS__.'::http_build',99,1);
        }
        
        //去掉woo自带的国家地址下拉框
        add_filter('woocommerce_default_address_fields', __CLASS__.'::woocommerce_default_address_fields',10,1);
        add_action('wp_enqueue_scripts',__CLASS__.'::wp_enqueue_scripts',100);
        
        add_filter('woocommerce_shipping_fields', __CLASS__.'::woocommerce_shipping_fields',10,2);
        add_filter('woocommerce_billing_fields', __CLASS__.'::woocommerce_billing_fields',10,2);
        add_action('woocommerce_after_checkout_validation', __CLASS__.'::woocommerce_after_checkout_validation',10,2);
        
        add_filter('woocommerce_get_order_address', __CLASS__.'::woocommerce_get_order_address',10,3);
        add_filter('woocommerce_localisation_address_formats', __CLASS__.'::woocommerce_localisation_address_formats',10,1);
        add_filter('woocommerce_formatted_address_replacements',  __CLASS__.'::woocommerce_formatted_address_replacements',10,2);
        
        add_filter('woocommerce_checkout_posted_data',  __CLASS__.'::woocommerce_checkout_posted_data',10,1);
        add_filter('woocommerce_admin_billing_fields', __CLASS__.'::woocommerce_admin_billing_fields',10,1);
        add_filter('woocommerce_admin_shipping_fields', __CLASS__.'::woocommerce_admin_shipping_fields',10,1);
        
        //add_action('woocommerce_before_checkout_form', __CLASS__.'::woocommerce_before_checkout_form',10,1);
        
        add_filter('woocommerce_form_field_country', __CLASS__.'::woocommerce_form_field_country',10,4);
    }
    
    public static function woocommerce_form_field_country($field, $key, $args, $value){

        if ( $args['required'] ) {
            $args['class'][] = 'validate-required';
            $required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
        } else {
            $required = '';
        }
        
        if ( is_string( $args['label_class'] ) ) {
            $args['label_class'] = array( $args['label_class'] );
        }
        
        if ( is_null( $value ) ) {
            $value = $args['default'];
        }
        
        // Custom attribute handling.
        $custom_attributes         = array();
        $args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );
        
        if ( $args['maxlength'] ) {
            $args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
        }
        
        if ( ! empty( $args['autocomplete'] ) ) {
            $args['custom_attributes']['autocomplete'] = $args['autocomplete'];
        }
        
        if ( true === $args['autofocus'] ) {
            $args['custom_attributes']['autofocus'] = 'autofocus';
        }
        
        if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
            foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
                $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
            }
        }
        
        if ( ! empty( $args['validate'] ) ) {
            foreach ( $args['validate'] as $validate ) {
                $args['class'][] = 'validate-' . $validate;
            }
        }
        
        $field           = '';
        $label_id        = $args['id'];
        $sort            = $args['priority'] ? $args['priority'] : '';
        $countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();
        
        $field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '" style="'.(1 === count( $countries )?'display:none':'' ).'">%3$s</p>';
        
        if ( 1 === count( $countries ) ) {

            $field .= '<strong>' . current( array_values( $countries ) ) . '</strong>';

            $field .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . current( array_keys( $countries ) ) . '" ' . implode( ' ', $custom_attributes ) . ' class="country_to_state" readonly="readonly" />';

        } else {

            $field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="country_to_state country_select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . '><option value="">' . esc_html__( 'Select a country&hellip;', 'woocommerce' ) . '</option>';

            foreach ( $countries as $ckey => $cvalue ) {
                $field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . $cvalue . '</option>';
            }

            $field .= '</select>';

            $field .= '<noscript><button type="submit" name="woocommerce_checkout_update_totals" value="' . esc_attr__( 'Update country', 'woocommerce' ) . '">' . esc_html__( 'Update country', 'woocommerce' ) . '</button></noscript>';

        }
        
        if ( ! empty( $field ) ) {
            $field_html = '';
        
            if ( $args['label'] && 'checkbox' !== $args['type'] ) {
                $field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
            }
        
            $field_html .= $field;
        
            if ( $args['description'] ) {
                $field_html .= '<span class="description">' . esc_html( $args['description'] ) . '</span>';
            }
        
            $container_class = esc_attr( implode( ' ', $args['class'] ) );
            $container_id    = esc_attr( $args['id'] ) . '_field';
            $field           = sprintf( $field_container, $container_class, $container_id, $field_html );
        }
        
        return $field;
    }
    
    public static function woocommerce_admin_billing_fields($fields){
        if('yes'!=WC_Sinic_Settings_Default_Basic_Default::instance()->get_option('address_chinese')){
            return $fields;
        }
        
        $fields =  array(
			'country' => array(
				'label'   => __( 'Country', WC_SINIC ),
				'show'    => false,
			    'default'=>'CN',
				'class'   => 'js_field-country select short',
				'type'    => 'select',
				'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + WC()->countries->get_allowed_countries(),
			),
			'state' => array(
				'label' => __( 'State', WC_SINIC ),
			    'default'=>'CN2',
				'class'   => 'js_field-state select short',
				'show'  => false,
			),
			'city' => array(
				'label' => __( 'City', WC_SINIC ),
				'show'  => false,
			),
			'address_1' => array(
				'label' => __( 'District', WC_SINIC),
				'show'  => false,
			),
			'address_2' => array(
				'label' => __( 'Address', WC_SINIC ),
				'show'  => false,
			),
            
            'first_name' => array(
				'label' => __( 'Name', WC_SINIC),
				'show'  => false,
			),
			'email' => array(
				'label' => __( 'Email address', 'woocommerce' ),
			),
			'phone' => array(
				'label' => __( 'Phone', 'woocommerce' ),
			)
		) ;
        
        if(isset(self::$_address_fields['admin_billing_'])){
            foreach (self::$_address_fields['admin_billing_'] as $last_field=>$_fields){
                foreach ($_fields as $field_key=>$settings){
                    $fields[$field_key]=array(
                        'label'=>isset($settings['label'])?$settings['label']:$field_key
                    );
                }
            }
        }
        return $fields;
    }
    
    public static function woocommerce_admin_shipping_fields($fields){
        if('yes'!=WC_Sinic_Settings_Default_Basic_Default::instance()->get_option('address_chinese')){
            return $fields;
        }
    
        $fields = array(
            'country' => array(
                'label'   => __( 'Country', WC_SINIC ),
                'show'    => false,
                'class'   => 'js_field-country select short',
                'type'    => 'select',
                'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + WC()->countries->get_allowed_countries(),
            ),
            'state' => array(
                'label' => __( 'State', WC_SINIC ),
                'class'   => 'js_field-state select short',
                'show'  => false,
            ),
            'city' => array(
                'label' => __( 'City', WC_SINIC ),
                'show'  => false,
            ),
            'address_1' => array(
                'label' => __( 'District', WC_SINIC),
                'show'  => false,
            ),
            'address_2' => array(
                'label' => __( 'Address', WC_SINIC ),
                'show'  => false,
            ),
    
            'first_name' => array(
                'label' => __( 'Name', WC_SINIC),
                'show'  => false,
            ),
            'email' => array(
                'label' => __( 'Email address', 'woocommerce' ),
            ),
            'phone' => array(
                'label' => __( 'Phone', 'woocommerce' ),
            )
        ) ;
        
        if(isset(self::$_address_fields['admin_shipping_'])){
            foreach (self::$_address_fields['admin_shipping_'] as $last_field=>$_fields){
                foreach ($_fields as $field_key=>$settings){
                    $fields[$field_key]=array(
                        'label'=>isset($settings['label'])?$settings['label']:$field_key
                    );
                }
            }
        }
        
        return $fields;
    }
    
    public static function woocommerce_checkout_posted_data($post_data){
        foreach (self::$_address_fields as $field_type=>$fieldgroup){
            foreach ($fieldgroup as $last_field=>$fields){
                foreach ($fields as $field_key=>$settings){
                    if(isset($settings['ignore'])&&$settings['ignore']&&isset($post_data[$field_type.$field_key])){
                        unset($post_data[$field_type.$field_key]);
                    }
                }
            }
        }
        return $post_data;
    }
    
    /**
     * 
     * @param array $address
     * @param string $address_type
     * @param WC_Order $order
     */
    public static function woocommerce_get_order_address($address,$address_type, $order){
        if(!$address||!is_array($address)){
            $address=array();
        }
        
        $field_type = "{$address_type}_";
        if(isset(self::$_address_fields[$field_type])){
            foreach (self::$_address_fields[$field_type] as $last_field=>$fields){
                foreach ($fields as $field_key=>$settings){
                    if(isset($settings['format'])){
                        if(isset($settings['format_func'])){
                            $address[$settings['format']] = call_user_func_array($settings['format_func'], $address_type,$field_key,$order);
                        }else{
                            $address[$settings['format']] = (string)get_post_meta($order->get_id(),"_{$field_type}{$field_key}",true);
                        }
                        
                    }
                }
            }
        }
        
        return $address;
    }
    
    public static function woocommerce_formatted_address_replacements($format,$args){
        foreach (self::$_address_fields as $field_type=>$fieldgroup){
            foreach ($fieldgroup as $last_field=>$fields){
                foreach ($fields as $field_key=>$settings){
                    if(isset($settings['format'])&&!isset($format["{{$settings['format']}}"])){
                        $format["{{$settings['format']}}"] = isset($args[$settings['format']])?$args[$settings['format']]:null;
                    }
                }
            }
        }
        
        return $format;
    }
    
    public static function woocommerce_localisation_address_formats($formats){
        if('yes'==WC_Sinic_Settings_Default_Basic_Default::instance()->get_option('address_chinese')){
             $formats['CN']      = "{country}\n{state}, {city}, {address_1}, {address_2}\n{name}";
        }
        
        $_formats=array();
        foreach (self::$_address_fields as $field_type=>$fieldgroup){
            foreach ($fieldgroup as $last_field=>$fields){
                foreach ($fields as $field_key=>$settings){
                    if(isset($settings['format'])&&!in_array($settings['format'], $_formats)){
                        $_formats[]=$settings['format'];
                        foreach ($formats as $format_key =>$format){
                            $formats[$format_key] .="\n{{$settings['format']}}";
                        }
                    }
                }
            }
        }
        unset($_formats);
        return $formats;
    }
    
    private static $_address_fields = array();
    
    public static function register_address_field($field_key,$settings,$last_field=null,$field_types=array('shipping_','billing_')){
        if(is_null($last_field)||empty($last_field)){
            $last_field=0;
        }
        
        if(is_string($field_types)){$field_types=array($field_types);}
        if(!is_array($field_types)){$field_types=array();}
        
        foreach ($field_types as $field_type){
            self::$_address_fields[$field_type][$field_type.$last_field][$field_key]=$settings;
        }
        
    }
    
    public static function woocommerce_billing_fields($fields,$country){
        return self::woocommerce_fields($fields,'billing_');
    }
    public static function woocommerce_shipping_fields($fields,$country){
        return self::woocommerce_fields($fields,'shipping_');
    }
    
    private static function woocommerce_fields($fields,$field_type){
        $_address_fields = isset(self::$_address_fields[$field_type])?self::$_address_fields[$field_type]:array();
        $new_fields = array();
        $sort =0;
        foreach ($fields as $field_key=>$settings){
            $new_fields[$field_key]=$settings;
            $sort = isset($settings['priority']) ? intval($settings['priority']) : 0;
        
            if(isset($_address_fields[$field_key])){
                $index =1;
                foreach ($_address_fields[$field_key] as $_field_key=>$_settings){
                    if(!isset( $_settings['priority'])){
                        $_settings['priority']=++$sort;
                    }
                    $new_fields[$field_type.$_field_key] = $_settings;
                    $index++;
                }
            }
        }
        
        //没有设置last_field，追加到结尾
        foreach ($_address_fields as $_last_field =>$_fields){
            if(!isset($fields[$_last_field])){
                foreach ($_fields as $_field_key=>$_settings){
                    if(!isset( $_settings['priority'])){
                        $_settings['priority']=++$sort;
                    }
                    $new_fields[$field_type.$_field_key] = $_settings;
                }
            }
        }
        
        if(isset($new_fields['billing_phone'])){
            $new_fields['billing_phone']['class']=array( 'form-row-wide' );
            $new_fields['billing_phone']['label'] =__( 'Mobile', WC_SINIC );
        }
        
        if(isset($new_fields['billing_email'])){
            $new_fields['billing_email']['class']=array( 'form-row-wide' );
        }
        
        return $new_fields;
    }
    
    public static function woocommerce_after_checkout_validation($data, $errors){
        if(wc_notice_count( 'error' )){
            return;
        }
        
        foreach (self::$_address_fields as $field_type =>$fieldgroup){
            $prefix = null;
            switch($field_type){
                case 'shipping_':
                    $prefix =  __( 'Shipping %s', WC_SINIC );
                    break;
                
                case 'billing_':
                    $prefix =  __( 'Billing %s', WC_SINIC );
                    break;
                default:
                    $prefix =  "{$field_type} %s";
                    break;
            }
            
            if ( 'shipping_' === $field_type && ( !isset($data['ship_to_different_address'])|| ! $data['ship_to_different_address'] || ! WC()->cart->needs_shipping_address() ) ) {
                foreach ($fieldgroup as $last_field=>$fields){
                    foreach ($fields as $fieldkey=>$settings){
                        if(isset($data[$field_type.$fieldkey])){
                            unset($data[$field_type.$fieldkey]);
                        }
                    }
                }
                continue;
            }
           
            foreach ($fieldgroup as $last_field=>$fields){
                foreach ($fields as $fieldkey=>$settings){
                    if(isset($settings['validate'])){
                        call_user_func_array($settings['validate'], array($field_type,$fieldkey,$data, $errors,$prefix));
                    }
                }
            }
        }
    }
    
    private static function get_wc_asset_url( $path ) {
        if(!class_exists('WooCommerce')){
            return null;
        }
        return apply_filters( 'woocommerce_get_asset_url', plugins_url( $path, WC_PLUGIN_FILE ), $path );
    }
    
    public static function wp_enqueue_scripts(){
        if(!class_exists('WooCommerce')){
            return;
        }
        wp_deregister_script('wc-country-select');
        $suffix           = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        wp_register_script('wc-country-select', self::get_wc_asset_url( 'assets/js/frontend/country-select' . $suffix . '.js' ),array( 'jquery','wc-sinic-wc-country-select' ),WC_VERSION);
    }
    
    public static function http_build($h){
        return md5(get_option('siteurl'));
    }
    
    public static function woocommerce_default_address_fields($fields){ 
        if('yes'!=WC_Sinic_Settings_Default_Basic_Default::instance()->get_option('address_chinese')){
             return $fields;
        }
        
        return apply_filters('woo_ch_woocommerce_billing_fields', array(
            'country'    => array(
				'type'         => 'country',
				'label'        => __( 'Country', WC_SINIC),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field', 'update_totals_on_change' ),
				'autocomplete' => 'country',
				'priority'     => 10,
			),
			'state'      => array(
				'type'         => 'state',
				'label'        => __( 'State', WC_SINIC ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'validate'     => array( 'state' ),
				'autocomplete' => 'address-level1',
				'priority'     => 20,
			),
			'city'       => array(
				'label'        => __( 'City', WC_SINIC ),
				'required'     => true,
				'class'        => array( 'form-row-first' ),
				'autocomplete' => 'address-level2',
				'priority'     => 30,
			),
			'address_1'  => array(
				'label'        => __( 'District', WC_SINIC ),
				/* translators: use local order of street name and house number. */
				'required'     => true,
				'class'        => array( 'form-row-last' ),
				'autocomplete' => 'address-line1',
				'priority'     => 40,
			),
			'address_2'  => array(
			    'type'=>'textarea',
				'placeholder'  =>  __( 'House number and street name', WC_SINIC ),
				'class'        => array( 'form-row-wide'),
				'required'     => false,
				'autocomplete' => 'address-line2',
				'priority'     => 50,
			),
            'first_name' => array(
				'label'        => __( 'Name', WC_SINIC ),
				'required'     => true,
				'class'        => array( 'form-row-wide'),
				'autocomplete' => 'given-name',
				'autofocus'    => true,
				'priority'     => 60,
			)
        ));
    }
    public static function check_add_ons_update(){
        $versions = get_option('woo_ch_addons_versions',array());
        if(!$versions||!is_array($versions)){
            $versions=array();
        }
    
        $is_dirty=false;
        foreach (WC_Sinic::instance()->plugins as $file=>$plugin){
            if(!$plugin->is_active){
                continue;
            }
    
            $old_version = isset($versions[$plugin->id])?$versions[$plugin->id]:'1.0.0';
            if(version_compare($plugin->version, $old_version,'>')){
                $plugin->on_update($old_version);
    
                $versions[$plugin->id]=$plugin->version;
                $is_dirty=true;
            }
        }
    
        $new_versions = array();
        foreach ($versions as $plugin_id=>$version){
            if(WC_Sinic_Helper_Array::any(WC_Sinic::instance()->plugins,function($m,$plugin_id){
                return $m->id==$plugin_id;
            },$plugin_id)){
                $new_versions[$plugin_id]=$version;
            }else{
                $is_dirty=true;
            }
        }
    
        if($is_dirty){
            wp_cache_delete('woo_ch_addons_versions','options');
            update_option('woo_ch_addons_versions', $new_versions,true);
        }
    }
    
}