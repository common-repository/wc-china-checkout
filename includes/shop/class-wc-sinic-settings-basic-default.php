<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly

/**
 * Social Admin
 *
 * @since 1.0.0
 * @author ranj
 */
class WC_Sinic_Settings_Default_Basic_Default extends Abstract_WC_Sinic_Settings{
    /**
     * Instance
     * @since  1.0.0
     */
    private static $_instance;

    /**
     * Instance
     * @since  1.0.0
     */
    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
            return self::$_instance;
    }

    private function __construct(){
        $this->id='settings_default_basic_default';
        $this->title=__('General',WC_SINIC);

        $this->init_form_fields();
    }

    public function init_form_fields(){
        $this->form_fields = array(
            'address_chinese' =>array(
                'title'=>__('Enable sinic address',WC_SINIC),
                'type'=>'checkbox',
                'default'=>'yes'
            )
        );
    }
}
?>