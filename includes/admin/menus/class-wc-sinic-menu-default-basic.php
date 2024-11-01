<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * 菜单：其他
 *
 * @since 1.0.0
 * @author ranj
 */
class WC_Sinic_Menu_Default_Basic extends Abstract_WC_Sinic_Settings_Menu{
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
    
    /**
     * 菜单初始化
     *
     * @since  1.0.0
     */
    private function __construct(){
        $this->id='menu_default_basic';
        $this->title=__('Billing Address',WC_SINIC);
    }
    
    /* (non-PHPdoc)
     * @see Abstract_WC_Sinic_Settings_Menu::menus()
     */
    public function menus(){
        return apply_filters("woo_ch_admin_menu_{$this->id}", array(
            WC_Sinic_Settings_Default_Basic_Default::instance()
        ));
    }
}


?>