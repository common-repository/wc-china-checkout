<?php
if (! defined('ABSPATH')) {
    exit();
}

/**
 * wordpress apis
 *
 * @author rain
 * @since 1.0.0
 */
class WC_Sinic_WP_Api
{

    /**
     * The single instance of the class.
     *
     * @since 1.0.0
     * @var WC_Sinic_WP_Api
     */
    private static $_instance = null;

    /**
     * Main Social Instance.
     *
     * Ensures only one instance of Social is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     *
     * @return WC_Sinic - Main instance.
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {}

    /**
     * @since 1.0.0
     */
    public function get_plugin_settings_url()
    {
        return admin_url("admin.php?page=woo_ch_page_default");
    }

    /**
     * 判断当前用户是否允许操作
     * 
     * @param array $roles            
     * @since 1.0.0
     */
    public function capability($roles = array('administrator'))
    {
        global $current_user;
        if (! is_user_logged_in()) {}
        
        if (! $current_user->roles || ! is_array($current_user->roles)) {
            $current_user->roles = array();
        }
        
        foreach ($roles as $role) {
            if (in_array($role, $current_user->roles)) {
                return true;
            }
        }
        return false;
    }

   
    /**
     *
     * @since 1.0.9
     * @param array $request            
     * @param bool $validate_notice            
     * @return bool
     */
    public function ajax_validate( $request, $hash, $validate_notice = true)
    {
        if (is_null($hash)||empty($hash)||WC_Sinic_Helper::generate_hash($request, WC_Sinic::instance()->get_hash_key()) != $hash) {
            return false;
        }

        return true;
    }

    /**
     * 设置错误
     * 
     * @param string $key            
     * @param string $error            
     * @since 1.0.5
     */
    public function set_wp_error($key, $error)
    {
        WC_Sinic::instance()->session->set("error_{$key}", $error);
    }

    /**
     * 清除错误
     * 
     * @param string $key            
     * @param string $error            
     * @since 1.0.5
     */
    public function unset_wp_error($key)
    {
        WC_Sinic::instance()->session->__unset("error_{$key}");
    }

    /**
     * 获取错误
     * 
     * @param string $key            
     * @param string $error            
     * @since 1.0.5
     */
    public function get_wp_error($key, $clear = true)
    {
        $cache_key = "error_{$key}";
        $session = WC_Sinic::instance()->session;
        $error = $session->get($cache_key);
        if ($clear) {
            $this->unset_wp_error($key);
        }
        return $error;
    }
    
    /**
     * wp die
     * 
     * @param Exception|WC_Sinic_Error|WP_Error|string|object $err            
     * @since 1.0.0
     */
    public function wp_die($err = null, $include_header_footer = true, $exit = true)
    {
        WC_Sinic_Temp_Helper::set('atts', array(
            'err' => $err,
            'include_header_footer' => $include_header_footer
        ), 'template');
        
        ob_start();
        require WC_Sinic::instance()->WP->get_template(WC_SINIC_DIR, 'wp-die.php');
        echo ob_get_clean();
        if ($exit) {
            exit();
        }
    }

    /**
     * 获取插件列表
     * 
     * @return NULL|Abstract_WC_Sinic_Add_Ons[]
     */
    public function get_plugin_list_from_system()
    {
        $content_dir = WP_CONTENT_DIR;
        $base_dirs = array(
            str_replace('\\', '/', $content_dir).'/wc-sinicization/add-ons/',
            WC_SINIC_DIR . '/add-ons/'
        );
        
        $plugins = array();
        
        $include_files = array();
        
        foreach ($base_dirs as $base_dir) {
            try {
                if (! is_dir($base_dir)) {
                    continue;
                }
                
                $handle = opendir($base_dir);
                if (! $handle) {
                    continue;
                }
             
                try {
                    while (($file = readdir($handle)) !== false) {
                        
                        if (empty($file) || $file == '.' || $file == '..' || $file == 'index.php') {
                            continue;
                        } 
                        if (in_array($file, $include_files)) {
                            continue;
                        }
                        // 排除多个插件目录相同插件重复includ的错误
                        $include_files[] = $file;
                        
                        try {
                            if (strpos($file, '.') !== false) {
                                if (stripos($file, '.php') === strlen($file) - 4) {
                                    $file = str_replace("\\", "/", $base_dir . $file);
                                }
                            } else {
                                $file = str_replace("\\", "/", $base_dir . $file . "/init.php");
                            }
                           
                            if (file_exists($file)) {
                                $add_on = null;
                                
                                if (isset(WC_Sinic::instance()->plugins[$file])) {
                                    // 已安装
                                    $add_on = WC_Sinic::instance()->plugins[$file];
                                } else {
                                    // 未安装
                                    $add_on = require_once $file;
                                    
                                    if ($add_on && $add_on instanceof Abstract_WC_Sinic_Add_Ons) {
                                        $add_on->is_active = false;
                                        WC_Sinic::instance()->plugins[$file] = $add_on;
                                    } else {
                                        $add_on = null;
                                    }
                                }
                                
                                if ($add_on) {
                                    $plugins[$file] = $add_on;
                                }
                            }
                        } catch (Exception $e) {}
                    }
                } catch (Exception $e) {}
                
                closedir($handle);
            } catch (Exception $e) {}
        }
        
        return $plugins;
    }

    /**
     *
     * @param string $dir            
     * @param string $template_name            
     * @param mixed $params            
     * @return string
     */
    public function requires($dir, $template_name, $params = null)
    {
        if (! is_null($params)) {
            WC_Sinic_Temp_Helper::set('atts', $params, 'templates');
        }
        ob_start();
        $dir =apply_filters('woo_ch_require_dir', $dir,$template_name);

        require $this->get_template($dir, $template_name);
        return ob_get_clean();
    }

    /**
     *
     * @param string $page_template_dir            
     * @param string $page_template            
     * @return string
     * @since 1.0.0
     */
    public function get_template($page_template_dir, $page_template)
    {
        if (file_exists(STYLESHEETPATH . '/wc-sinicization/' . $page_template)) {
            return STYLESHEETPATH . '/wc-sinicization/' . $page_template;
        }
        
        return $page_template_dir . '/templates/' . $page_template;
    }
    
}