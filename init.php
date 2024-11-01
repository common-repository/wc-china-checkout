<?php
/*
 * Plugin Name: WC China Checkout
 * Plugin URI: https://www.wpweixin.net
 * Description: WooCommerce 中国购物功能优化
 * Author: 迅虎网络
 * Version: 1.0.0
 * Author URI:  http://www.wpweixin.net
 * Text Domain: wc-sinic
 * Domain Path: /lang
 * WooCommerce tested up to: 3.4.2
 */

if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
	
if(defined('WP_DEBUG')&&WP_DEBUG===true){
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
}	

if ( ! class_exists( 'WC_Sinic' ) ) :
final class WC_Sinic {
    /**
     * Social version.
     *
     * @since 1.0.0
     * @var string
     */
    public $version = '1.0.0';
    
    /**
     * 最小wp版本
     * @var string
     */
    public $min_wp_version='3.7';
    
    /**
     * License ID
     * 
     * @var string
     */
    public static $license_id=array(
        'wc_sinicization'
    );
    
    /**
     *
     * @var string[]
     */
    public $plugins_dir =array();
  
    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var Social
     */
    private static $_instance = null;
    
    /**
     * 已安装的插件（包含激活的，可能包含未激活的）
     * is_active 标记是否已被激活
     * 
     * 一般请求：只加载被激活的插件，
     * 在调用 WC_Sinic_WP_Api::get_plugin_list_from_system后，加载所有已安装的插件
     * @var Abstract_WC_Sinic_Add_Ons[]
     */
    public $plugins=array();
    
    /**
     * session
     * 缓存到自定义数据库中
     * 
     * @var XH_Session_Handler
     */
    public $session;
   
    /**
     * wordpress接口
     * @var WC_Sinic_WP_Api
     */
    public $WP;

    /**
     * Main Social Instance.
     *
     * Ensures only one instance of Social is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @return WC_Sinic - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Cloning is forbidden.
     * 
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WC_SINIC ), '1.0.0' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     * 
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WC_SINIC ), '1.0.0' );
    }

    public function supported_wp_version(){
        global $wp_version;
        return version_compare( $wp_version, $this->min_wp_version, '>=' );
    }
    
    /**
     * Constructor.
     * 
     * @since 1.0.0
     */
    private function __construct() {
        $this->define_constants();
    
        $this->includes();  
        $this->init_hooks();
        do_action( 'woo_ch_loaded' );
    }

    /**
     * Hook into actions and filters.
     * 
     * @since 1.0.0
     */
    private function init_hooks() {
        load_plugin_textdomain( WC_SINIC, false,dirname( plugin_basename( __FILE__ ) ) . '/lang/'  );
        
        $this->include_plugins();
      
        add_action( 'init', array( $this,                   'init' ), 1 );
        add_action( 'init', array( $this,                   'after_init' ), 999 );
    
        add_action( 'init', array( 'WC_Sinic_Shortcodes',      'init' ), 10 );
        add_action( 'init', array( 'WC_Sinic_Ajax',            'init' ), 10 );
        add_action('after_setup_theme', array($this,        'after_setup_theme'),10);
        WC_Sinic_Hooks::init();
        add_action( 'admin_enqueue_scripts', array($this,'admin_enqueue_scripts'),99);
        add_action('wp_enqueue_scripts', array($this,'wp_enqueue_scripts'),99);
        WC_Sinic_Log::instance( new WC_Sinic_Log_File_Handler ( WC_SINIC_DIR . "/logs/" . date ( 'Y/m/d' ) . '.log' ));
        register_activation_hook ( WC_SINIC_FILE, array($this,'_register_activation_hook'),10 );
        register_deactivation_hook(WC_SINIC_FILE,  array($this,'_register_deactivation_hook'),10);        
        add_action ( 'plugin_action_links_'. plugin_basename( WC_SINIC_FILE ),array($this,'_plugin_action_links'),10,1);
    }

    public function after_setup_theme(){
        global $pagenow;
        // Load the functions for the active theme, for both parent and child theme if applicable.
        if ( ! wp_installing() || 'wp-activate.php' === $pagenow ) {
            if ( TEMPLATEPATH !== STYLESHEETPATH && file_exists( STYLESHEETPATH . '/wc-sinicization/functions.php' ) ){
                include( STYLESHEETPATH . '/wc-sinicization/functions.php' );
            }
    
            if ( file_exists( TEMPLATEPATH . '/wc-sinicization/functions.php' ) ){
                include( TEMPLATEPATH . '/wc-sinicization/functions.php' );
            }
        }
    }
    
    public function after_init(){
        do_action('woo_ch_after_init');
    }
    
    /**
     * 获取已激活的扩展
     * @param string $add_on_id
     * @return Abstract_WC_Sinic_Add_Ons|NULL
     * @since 1.0.0
     */
    public function get_available_addon($add_on_id){
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->id==$add_on_id&&$plugin->is_active){
                return $plugin;
            }
        }
        
        return null;
    }
    /**
     * 获取已激活的扩展
     * @return Abstract_WC_Sinic_Add_Ons[]
     * @since 1.0.0
     */
    public function get_available_addons(){
        $results = array();
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->is_active){
                $results[]= $plugin;
            }
        }
    
        return $results;
    }

    /**
     * 获取已安装的扩展
     * @param string $add_on_id
     * @return Abstract_WC_Sinic_Add_Ons|NULL
     * @since 1.1.7
     */
    public function get_installed_addon($add_on_id){
        foreach ($this->plugins as $file=>$plugin){
            if($plugin->id==$add_on_id){
                return $plugin;
            }
        }
    
        return null;
    } 
    /**
     * 加载扩展
     * @since 1.0.0
     */
    private function include_plugins(){
        $installed = get_option('woo_ch_plugins_installed',array());
        if(!$installed){
            return;
        }
        $dirty=false;
        foreach ($installed as $file){
            $file = str_replace('\\', '/', $file);
            $valid = false;
            foreach ($this->plugins_dir as $dir){
                if(strpos($file, $dir)===0){
                    $valid=true;
                    break;
                }
            }
            if(!$valid){
                continue;
            }
            
            $add_on=null;
            if(isset($this->plugins[$file])){
                $add_on=$this->plugins[$file];
            }else{
                if(file_exists($file)){
                    $add_on = require_once $file;
                    if($add_on&&$add_on instanceof Abstract_WC_Sinic_Add_Ons){
                        $this->plugins[$file]=$add_on;
                    }else{
        	            $add_on=null;
        	        }
                }else{
                    unset($installed[$file]);
                    $dirty =true;
                }
            }
            
            if($add_on){
                $this->__load_plugin($add_on);
            }
            
            if($dirty){
                update_option('woo_ch_plugins_installed', $installed,true);
            }
        }
    }
    
    /**
     * 当前api为内部api，轻忽随意使用
     * @param Abstract_WC_Sinic_Add_Ons $add_on
     * @since 1.0.3
     */
    public function __load_plugin($add_on){
        $add_on->is_active=true;
        //初始化插件
        $add_on->on_load();
        //监听init
        add_action('init', array($add_on,'on_init'),10);
        add_action('woo_ch_after_init', array($add_on,'on_after_init'),10);
        add_filter('woo_ch_shortcodes', array($add_on,'add_shortcodes'),10,1);    
        add_action('init', array($add_on,'register_post_types'),10);
        add_action('woo_ch_flush_rewrite_rules', array($add_on,'register_post_types'),10);
        add_action('woo_ch_cron', array($add_on,'on_cron'),10);
        add_action('woo_ch_after_init', array($add_on,'register_fields'),10);
    }
    
    /**
     * ajax url
     * @param string|array $action
     * @param bool $hash
     * @return string
     * @since 1.0.0
     */
    public function ajax_url($action=null,$hash = false,$notice=false,$_params = array()) {   
        $ps =array();
        $url = WC_Sinic_Helper_Uri::get_uri_without_params(admin_url( 'admin-ajax.php' ),$ps);
        $params = array();
        
        if($action){
            if(is_string($action)){
                $params['action']=$action;
            }else if(is_array($action)){
                $params=$action;
            }
        }
        
        if(isset($params['action'])&&!empty($params['action'])){
            if($notice){
                $params[$params['action']] = wp_create_nonce($params['action']);
            }
        }
        
        if($hash){
            $params['notice_str'] = str_shuffle(time());
            $params['hash'] = WC_Sinic_Helper::generate_hash($params, $this->get_hash_key());
        }
        
        if(count($_params)>0){
           foreach ($_params as $k=>$v){
               $params[$k]=$v;
           } 
        }
        if(count($params)>0){
            $url.="?".http_build_query($params);
        }
        return $url;
    }
    
    /**
     * 生成请求
     * @param array $request
     * @return array
     */
    public function generate_request_params($request,$notice_key=null){
        if(!empty($notice_key)){
            $request[$notice_key] = wp_create_nonce($notice_key);
        }
       
        $request['notice_str'] = str_shuffle(time());
        $request['hash'] = WC_Sinic_Helper::generate_hash($request, $this->get_hash_key());  
    
        
        return $request;
    }
    
    /**
     * 获取加密参数
     * @return string
     * @since 1.0.0
     */
    public function get_hash_key(){
        $hash_key = AUTH_KEY;
        if(empty($hash_key)){
            $hash_key = WC_SINIC_FILE;
        }
        
        return $hash_key;
    }
    
    /**
     * 插件初始化
     * 
     * 在ini 之前已启用
     * 初始化需要的数据库，初始化资源等
     * @since 1.0.0
     */
    public function _register_activation_hook(){
        //第一次安装，所有插件自动安装
        $plugins_installed =get_option('woo_ch_plugins_installed',null);
        if(!is_array($plugins_installed)||count($plugins_installed)==0){
            //默认只允许中国售卖
            update_option('woocommerce_allowed_countries', 'specific');
            update_option('woocommerce_specific_allowed_countries', array('CN'));
           
//             update_option('woo_ch_plugins_installed', array(
//                 WC_SINIC_DIR.'/add-ons/wpopen-alipay/init.php',
//                 WC_SINIC_DIR.'/add-ons/wpopen-wechat/init.php'
//             ),true);
           
            $this->include_plugins();
            unset($plugins_installed);
        }
        
        //插件初始化
        foreach ($this->plugins as $file=>$plugin){
            $plugin->on_install();
        }
        
        //数据表初始化
        $session_db =new XH_Session_Handler_Model();
        $session_db->init();
      
        WC_Sinic_Hooks::check_add_ons_update();
       
        do_action('woo_ch_register_activation_hook');
        
        ini_set('memory_limit','128M');
        
        do_action('woo_ch_flush_rewrite_rules');
        flush_rewrite_rules(); 
    }
    
    public function _register_deactivation_hook(){
        //插件初始化
        foreach ($this->plugins as $file=>$plugin){
            $plugin->on_uninstall();
        }
        do_action('woo_ch_register_deactivation_hook');
    }
    
       
    /**
     * 定义插件列表，设置菜单键
     * @param array $links
     * @return array
     * @since 1.0.0
     */
    public function _plugin_action_links($links){
        if(!is_array($links)){$links=array();}
         return array_merge ( array (
                'settings' => '<a href="' . $this->WP->get_plugin_settings_url().'">'.__('Settings').'</a>',
            ), $links );
    }
    
    /**
     * Define Constants.
     * @since 1.0.0
     */
    private function define_constants() {
        self::define( 'WC_SINIC', 'wc-sinic' );
        self::define( 'WC_SINIC_FILE', __FILE__ );
        
        require_once 'includes/class-xh-helper.php';
        self::define( 'WC_SINIC_DIR', WC_Sinic_Helper_Uri::wp_dir(__FILE__));
        self::define( 'WC_SINIC_URL', WC_Sinic_Helper_Uri::wp_url(__FILE__) );
        
        $content_dir = WP_CONTENT_DIR;
        $this->plugins_dir=array(
            str_replace('\\', '/', $content_dir).'/wc-sinicization/add-ons/',
            WC_SINIC_DIR.'/add-ons/',
        );
    }

    /**
     * Define constant if not already set.
     *
     * @since 1.0.0
     * @param  string $name
     * @param  string|bool $value
     */
    public static function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * What type of request is this?
     * 
     * @since 1.0.0
     * @param  string $type admin, ajax, cron or frontend.
     * @return bool
     */
    public static function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
            case 'ajax' :
                return defined( 'DOING_AJAX' );
            case 'cron' :
                return defined( 'DOING_CRON' );
            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     * @since  1.0.0
     */
    private function includes() {
        require_once 'includes/error/class-xh-error.php';
        require_once 'includes/logger/class-xh-log.php';
        
        require_once 'includes/class-xh-cache-helper.php';
        include_once 'includes/abstracts/abstract-xh-schema.php';
        
        if(!class_exists('Abstract_XH_Session')){
            require_once 'includes/class-xh-session-handler.php';
        }

        require_once 'includes/abstracts/abstract-xh-settings.php';
        require_once 'includes/abstracts/abstract-xh-add-ons.php';
        
        require_once 'includes/abstracts/abstract-xh-object.php';
        require_once 'includes/abstracts/abstract-xh-fields.php';
        
        require_once 'includes/admin/class-wc-sinic-admin.php';
        require_once 'includes/admin/abstracts/abstract-xh-view-form.php';
        require_once 'includes/admin/abstracts/abstract-xh-settings-menu.php';
        require_once 'includes/admin/abstracts/abstract-xh-settings-page.php';
        
        require_once 'includes/shop/class-wc-sinic-hooks.php';
        require_once 'includes/shop/class-wc-sinic-shortcodes-functions.php';
        require_once 'includes/shop/class-wc-sinic-shortcodes.php';
        require_once 'includes/shop/class-wc-sinic-ajax.php';
     
        require_once 'includes/shop/class-wc-sinic-settings-basic-default.php';
    }

    /**
     * Init shop when WordPress Initialises.
     * 
     * @since 1.0.0
     */
    public function init() {
        // Before init action.
        do_action( 'woo_ch_init_before' );
        
        $this->session =XH_Session_Handler::instance();
        $this->WP = WC_Sinic_WP_Api::instance();
        
        if(self::is_request( 'admin' )){
            //初始化 管理页面
            WC_Sinic_Admin::instance();
        }
        
        // Init action.
        do_action( 'woo_ch_init' );
    }
    
    public function on_update($version){
        do_action('woo_ch_on_update',$version);
    
        WC_Sinic_Hooks::check_add_ons_update();
    }
    /**
     * admin secripts
     *
     * @since 1.0.0
     */
    public function admin_enqueue_scripts(){
       $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
       wp_enqueue_script('jquery');
       wp_enqueue_script('media-upload');
       add_thickbox();
       wp_enqueue_media();
       
       wp_enqueue_script('jquery-loading',WC_SINIC_URL."/assets/js/jquery-loading$min.js",array('jquery'),$this->version,true);
       wp_enqueue_script('WdatePicker',WC_SINIC_URL."/assets/My97DatePicker/WdatePicker.js",array(),$this->version,true);
       wp_enqueue_script('select2',WC_SINIC_URL."/assets/select2/select2.full$min.js",array('jquery'),$this->version,true);
       wp_enqueue_script('jquery-tiptip', WC_SINIC_URL . "/assets/jquery-tiptip/jquery.tipTip$min.js", array( 'jquery' ), $this->version ,true);       
       wp_enqueue_script('wc-sinic-admin',WC_SINIC_URL."/assets/js/admin.js",array('jquery','select2','jquery-tiptip'),$this->version,true);
    
       wp_localize_script( 'wc-sinic-admin', 'woo_ch_enhanced_select', array(
           'i18n_no_matches'           => __( 'No matches found', WC_SINIC ),
           'i18n_ajax_error'           => __( 'Loading failed', WC_SINIC ),
           'i18n_input_too_short_1'    => __( 'Please enter 1 or more characters', WC_SINIC ),
           'i18n_input_too_short_n'    => __( 'Please enter %qty% or more characters', WC_SINIC ),
           'i18n_input_too_long_1'     => __( 'Please delete 1 character', WC_SINIC ),
           'i18n_input_too_long_n'     => __( 'Please delete %qty% characters', WC_SINIC ),
           'i18n_selection_too_long_1' => __( 'You can only select 1 item', WC_SINIC ),
           'i18n_selection_too_long_n' => __( 'You can only select %qty% items', WC_SINIC ),
           'i18n_load_more'            => __( 'Loading more results&hellip;', WC_SINIC ),
           'i18n_searching'            => __( 'Loading...', WC_SINIC ),
           'ajax_url'=>$this->ajax_url(array(
               'action'=>'woo_ch_obj_search'
           ),true,true)
       ));
       
       wp_enqueue_style('jquery-tiptip', WC_SINIC_URL . "/assets/jquery-tiptip/tipTip$min.css", array( ), $this->version );
       wp_enqueue_style('jquery-loading',WC_SINIC_URL."/assets/css/jquery.loading$min.css",array(),$this->version);
       wp_enqueue_style('wc-sinic-admin',WC_SINIC_URL."/assets/css/admin.css",array(),$this->version);
       
       do_action('woo_ch_admin_enqueue_scripts');
    }
   
   /**
    * front secripts
    *
    * @since 1.0.0
    */
    public function wp_enqueue_scripts(){
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script('jquery');
        
        wp_register_script('wc-sinic-wc-country-select', WC_SINIC_URL."/assets/js/wc-country-select.js",array('jquery'),$this->version);
        
        require_once 'includes/shop/class-wc-sinic-address.php';       
        wp_localize_script( 'wc-sinic-wc-country-select', 'woo_ch_wc_country_select_param', array(
            'countries'=>Woo_CH_Address::$data
        ));
        
        do_action('woo_ch_wp_enqueue_scripts');
    }
    
}

endif;

WC_Sinic::instance();

