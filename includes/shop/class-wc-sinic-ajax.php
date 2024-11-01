<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Sinic_Ajax class
 *
 * @version     2.1.0
 * @category    Class
 */
class WC_Sinic_Ajax {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
		    'woo_ch_plugin'=>__CLASS__ . '::plugin',
		    'woo_ch_captcha'=>__CLASS__ . '::captcha',
		);
		
		$add_ons = WC_Sinic::instance()->get_available_addons();
		if($add_ons){
		    foreach ($add_ons as $add_on){
		        $shortcodes["woo_ch_{$add_on->id}"] =array($add_on,'do_ajax');
		    }
		}
		$shortcodes = apply_filters('woo_ch_ajax', $shortcodes);
		foreach ( $shortcodes as $shortcode => $function ) {
		    add_action ( "wp_ajax_$shortcode",        $function);
		    add_action ( "wp_ajax_nopriv_$shortcode", $function);
		}
	}
	/**
	 * 验证码
	 * @since 1.0.0
	 */
	public static function captcha(){
	    $func = apply_filters('woo_ch_captcha', function(){
	        require_once WC_SINIC_DIR.'/includes/captcha/CaptchaBuilderInterface.php';
	        require_once WC_SINIC_DIR.'/includes/captcha/PhraseBuilderInterface.php';
	        require_once WC_SINIC_DIR.'/includes/captcha/CaptchaBuilder.php';
	        require_once WC_SINIC_DIR.'/includes/captcha/PhraseBuilder.php';
	         
	        $action ='woo_ch_captcha';
	        $params=shortcode_atts(array(
	            'notice_str'=>null,
	            'action'=>$action,
	            $action=>null,
	            'hash'=>null
	        ), stripslashes_deep($_REQUEST));
	
	        if(isset($_REQUEST['captcha_key'])){
	            $params['captcha_key'] =sanitize_key($_REQUEST['captcha_key']);
	        }else{
	            $params['captcha_key'] ='woo_ch_captcha';
	        }
	
	        if(!WC_Sinic::instance()->WP->ajax_validate($params,$params['hash'],true)){
	            WC_Sinic::instance()->WP->wp_die(WC_Sinic_Error::err_code(701)->errmsg);
	            exit;
	        }
	
	        $builder = Gregwar\Captcha\CaptchaBuilder::create() ->build();
	        WC_Sinic::instance()->session->set($params['captcha_key'], $builder->getPhrase());
	
	        return WC_Sinic_Error::success($builder ->inline());
	    });
	         
        $error = call_user_func($func);
        echo $error->to_json();
        exit;
	}
	

	/**
	 * 管理员对插件的操作
	 */
	public static function plugin(){
	    
	    if(!WC_Sinic::instance()->WP->capability()){
	        echo (WC_Sinic_Error::err_code(501)->to_json());
	        exit;
	    }
	    
	    $action='woo_ch_plugin';
	  
	    $params=shortcode_atts(array(
	        'notice_str'=>null,
	        'action'=>$action,
	        $action=>null,
	        'plugin_id'=>null,
	        'tab'=>null
	    ), stripslashes_deep($_REQUEST));
	    if(!WC_Sinic::instance()->WP->ajax_validate($params, isset($_REQUEST['hash'])?$_REQUEST['hash']:null,true)){
	        echo (WC_Sinic_Error::err_code(701)->to_json());
	        exit;
	    }
	    
	    $plugins =WC_Sinic::instance()->WP->get_plugin_list_from_system();
	    if(!$plugins){
	        echo (WC_Sinic_Error::err_code(404)->to_json());
	        exit;
	    }
	    
	    $add_on =null;
	    $add_on_file='';
	    foreach ($plugins as $file=>$plugin){
	        if($plugin->id==$params['plugin_id']){
	            $add_on_file = $file;
	            $add_on=$plugin;
	            break;
	        }
	    }
	    
        if(!$add_on){
            echo (WC_Sinic_Error::err_code(404)->to_json());
            exit;
        }
       
	    $cache_time = 2*60*60; 
	    switch ($params['tab']){
	        //插件安装
	        case 'install':
	            $installed = get_option('woo_ch_plugins_installed',array());
	            if(!$installed||!is_array($installed)){
	                $installed =array();
	            }
	            $has = false;
	            foreach ($installed as $item){
	                if($item==$add_on_file){
	                    $has=true;break;
	                }
	            }
	           
	            if(!$has){
	                $installed[]=$add_on_file;
	                
	                try {
	                    if($add_on->depends){
	                        foreach ($add_on->depends as $id=> $depend){
	                           $contains = false;
	                           foreach (WC_Sinic::instance()->plugins as $plugin){
	                               if(!$plugin->is_active){
	                                   continue;
	                               }
	                               
	                               if($plugin->id==$id){
	                                   $contains=true;
	                                   break;
	                               }
	                           }
	                           
	                           if(!$contains){//依赖第三方插件
	                               echo (WC_Sinic_Error::error_custom(sprintf(__('Current add-on is relies on %s!',WC_SINIC),"“{$depend['title']}”"))->to_json());
	                               exit;
	                           }
	                        }
	                    }
	                    
	                    if(!empty($add_on->min_core_version)){
    	                    if(version_compare(WC_Sinic::instance()->version,$add_on->min_core_version, '<')){
    	                        echo (WC_Sinic_Error::error_custom(sprintf(__('Core version must greater than or equal to %s!',WC_SINIC),$add_on->min_core_version))->to_json());
    	                        exit;
    	                    }
	                    }
	                    
	                    WC_Sinic::instance()->__load_plugin($add_on);	                
	                    $add_on->on_install(); 
	                    
	                    ini_set('memory_limit','128M');
	                    do_action('woo_ch_flush_rewrite_rules');
                        flush_rewrite_rules();
	                } catch (Exception $e) {
	                    echo (WC_Sinic_Error::error_custom($e->getMessage())->to_json());
	                    exit;
	                }
	               
	            }
	           
	            $plugins_find = WC_Sinic::instance()->WP->get_plugin_list_from_system();
	            if(!$plugins_find||!is_array($plugins_find)){
	                $plugins_find=array();
	            }
	             
	            $options = array();
	            foreach ($installed as $item){
	                $has = false;
	                foreach ($plugins_find as $file=>$plugin){
	                    if($item==$file){
	                        $has =true;
	                        break;
	                    }
	                }
	                if($has){
	                    $options[]=$file;
	                }
	            }
	            
	           wp_cache_delete("woo_ch_plugins_installed",'options');
	           update_option('woo_ch_plugins_installed', $options,true);
	           
	           echo (WC_Sinic_Error::success()->to_json());
	           exit;
	        //插件卸载   
	        case 'uninstall':
	            $installed = get_option('woo_ch_plugins_installed',array());
	         
	            if(!$installed||!is_array($installed)){
	                $installed =array();
	            }
	            
	            $new_values = array();
	            foreach ($installed as $item){
	                if($item!=$add_on_file){
	                    $new_values[]=$item;
	                }
	            }
	           
	            try {
	                foreach (WC_Sinic::instance()->plugins as $plugin){
	                    if(!$plugin->is_active){
	                        continue;
	                    }
	                    
	                    if(!$plugin->depends){
	                        continue;
	                    }
	                    
	                    foreach ($plugin->depends as $id=>$depend){
	                        if($id==$add_on->id){
	                            echo (WC_Sinic_Error::error_custom(sprintf(__('"%s" is relies on current add-on!',WC_SINIC),"“{$plugin->title}”"))->to_json());
	                            exit;
	                        }
	                    }
	                }
	                
	                $add_on->on_uninstall();
	            } catch (Exception $e) {
	                echo (WC_Sinic_Error::error_custom($e)->to_json());
	                exit;
	            }
	            
	            $plugins_find = WC_Sinic::instance()->WP->get_plugin_list_from_system();
	            if(!$plugins_find||!is_array($plugins_find)){
	                $plugins_find=array();
	            }
	            
	            $options = array();
	            foreach ($new_values as $item){
	                $has = false;
	                foreach ($plugins_find as $file=>$plugin){
	                    if($item==$file){
	                        $has =true;
	                        break;
	                    }
	                }
	                if($has){
	                    $options[]=$file;
	                }
	            }
	            
	            wp_cache_delete('woo_ch_plugins_installed', 'options');
	            $update =update_option('woo_ch_plugins_installed', $options,true);
	            echo (WC_Sinic_Error::success()->to_json());
	            exit;
	        //插件更新
	        case 'update':
	        case 'update_admin_options':
	        case 'update_plugin_list':
	           $info =get_option("wc-sinic-ajax:plugin:update:{$add_on->id}");
	           if(!$info||!is_array($info)){
	               $info=array();
	           }
	           
	           if(!isset($info['_last_cache_time'])||$info['_last_cache_time']<time()){
	               $api ='https://www.wpweixin.net/wp-content/plugins/xh-hash/api-add-ons.php';
	               $request_data = array(
	                   'l'=>$add_on->id,
	                   's'=>get_option('siteurl'),
	                   'v'=>$add_on->version,
	                   'a'=>'update'
	               );
	               //插件为非授权插件
	               $license =null;
	                $info =WC_Sinic_Install::instance()->get_plugin_options();
	                if($info){
	                    if(isset($info[$add_on->id])){
	                        $license=$info[$add_on->id];
	                    }
	                    
	                    if(empty($license)){
	                        $license = isset($info['license'])?$info['license']:null;
	                    }
	                }
	                if(empty($license)){
	                    echo WC_Sinic_Error::error_unknow()->to_json();
	                    exit;
	                }
	                
	               $request_data['c']=$license;
	                
	               $request =wp_remote_post($api,array(
	                   'timeout'=>10,
	                   'body'=>$request_data
	               ));
	              
	               if(is_wp_error( $request )){
	                   echo (WC_Sinic_Error::error_custom($request)->to_json());
	                   exit;
	               }
	               
	               $info = json_decode( wp_remote_retrieve_body( $request ) ,true);
	               if(!$info||!is_array($info)){
	                   echo (WC_Sinic_Error::error_unknow()->to_json());
	                   exit;
	               }
	               
	               //缓存30分钟
	               $info['_last_cache_time'] = time()+$cache_time;
	               update_option("wc-sinic-ajax:plugin:update:{$add_on->id}", $info,false);
	           }
	            
	           $msg =WC_Sinic_Error::success();
	           switch($params['tab']){
	               case 'update_admin_options':
	                   $txt =sprintf(__('There is a new version of %s - %s. <a href="%s" target="_blank">View version %s details</a> or <a href="%s" target="_blank">download now</a>.',WC_SINIC),
	                       $info['name'],
	                       $info['upgrade_notice'],
	                       $info['homepage'],
	                       $info['version'],
	                       $info['download_link']
	                       );
	                   $msg = new WC_Sinic_Error(0, version_compare($add_on->version,  $info['version'],'<')?$txt:'');
	                   break;
	               case 'update_plugin_list':
	                   $txt =sprintf(__('<tr class="plugin-update-tr active">
	                       <td colspan="3" class="plugin-update colspanchange">
	                       <div class="notice inline notice-warning notice-alt">
	                       <p>There is a new version of %s available.<a href="%s"> View version %s details</a> or <a href="%s" class="update-link">download now</a>.</p>
	                       <div class="">%s</div>
	                       </div></td></tr>',WC_SINIC),
	                       $info['name'],
	                       $info['homepage'],
	                       $info['version'],
	                       $info['download_link'],
	                       $info['upgrade_notice']
	                   );
	                   $msg = new WC_Sinic_Error(0, version_compare($add_on->version,  $info['version'],'<')?$txt:'');
	                   break; 
	           }
	           
	           echo $msg->to_json();
	           exit;
	    }
	}
}
