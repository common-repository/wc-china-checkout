<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

/**
 * 菜单：登录设置
 *
 * @since 1.0.0
 * @author ranj
 */
class WC_Sinic_Page_Default extends Abstract_WC_Sinic_Settings_Page{    
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
        $this->id='page_default';
        $this->title=__('Settings',WC_SINIC);
    }
    
    /* (non-PHPdoc)
     * @see Abstract_WC_Sinic_Settings_Menu::menus()
     */
    public function menus(){
        $submenus =array(
             WC_Sinic_Menu_Default_Basic::instance()
        );
        
        return apply_filters("woo_ch_admin_page_{$this->id}", WC_Sinic_Helper_Array::where($submenus, function($m){
            $menus= $m->menus();
            return count($menus)>0;
        }));
    }
}?>