<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Sinic_Shortcodes class
 *
 * @category    Class
 */
class WC_Sinic_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
		    
		);
		
		$shortcodes =apply_filters('woo_ch_shortcodes', $shortcodes);
		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "woo_ch_shortcode_{$shortcode}", $shortcode ), $function );
		}
	}
	
	
}
