<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 公共方法
 *
 * @class    WC_Sinic_Helper
 * @since    1.0.0
 * @author   ranj
 */
class WC_Sinic_Helper{ 
    public static function generate_qrimg($data){
        if(!class_exists('QRcode')){require_once 'phpqrcode/phpqrcode.php';}
        $errorCorrectionLevel = 'L'; // 容错级别
        $matrixPointSize = 9; // 生成图片大小
        ob_start();
        QRcode::png($data,false,$errorCorrectionLevel,$matrixPointSize);
        return "data:image/png;base64,".base64_encode(ob_get_clean());
    }
    
    public static function generate_unique_id(){
        static $_unique_id;
        if(!$_unique_id){
            $_unique_id = 0;
        }
        
        return strtolower(WC_Sinic_Helper_String::guid().($_unique_id++));
    }
    public static function is_mobile($mobile){
        if(empty($mobile)){
            return false;
        }
        
        return preg_match('/^\+?[\d\-\+]+$/', $mobile);
    }
    
    public static function generate_hash( $datas,$hashkey){
        ksort($datas);
        reset($datas);
       
        $arg  = '';
        $index=0;
        foreach ($datas as $key=>$val){
            if($key=='hash'){
                continue;
            }
           
            if(is_null($val)||$val===''){
                continue;
            }

            if(!is_string($val)&&!is_numeric($val)){
                continue;
            }
            
            if($index++!=0){
                $arg.="&";
            }
            
            $arg.="$key=$val";
        }
     
        return md5($arg.$hashkey);
    }
}

/**
 * 字符串扩展方法
 * 
 * @author rain
 * @since    1.0.0
 */
class WC_Sinic_Helper_String{
    public static function is_null_or_empty($string){
        return is_null($string)||$string==='';
    }
    
    /**
     * 价格格式化
     * @param string $decimal
     * @since 1.0.0
     */
    public static function get_format_price($decimal){
        return sprintf('%01.2f',$decimal);
    }
    
    /**
     * xml转换成object
     * @param string $xml
     * @param string $return_array
     * @since 1.0.0
     */
    public static function xml_to_obj($xml,$return_array = true){
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$xml,true)){
            xml_parser_free($xml_parser);
            return false;
        }else{
            libxml_disable_entity_loader(true);
            return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), $return_array);
        }
    }
    
    /**
     * object|array 转换成xml
     * @param array|object $parameter
     * @return string|NULL
     * @since 1.0.0
     */
    public static function obj_to_xml($parameter){
        if(!$parameter){
            return null;
        }
        
        if(is_object($parameter)){
            $parameter = get_object_vars($parameter);
        }
        
        if(!is_array($parameter)){
            return null;
        }
        $xml = "<xml>";
        foreach ($parameter as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
    
        return $xml;
    }
    
    public static function sanitize_key_ignorecase( $key ) {
        $raw_key = $key;
        $key = preg_replace( '/[^a-z0-9_\-A-Z]/', '', $key );
    
        /**
         * Filter a sanitized key string.
         *
         * @since 3.0.0
         *
         * @param string $key     Sanitized key.
         * @param string $raw_key The key prior to sanitization.
         */
        return apply_filters( 'sanitize_key_ignorecase', $key, $raw_key );
    }

    public static function guid(){
        $guid = '';
        //extension=php_com_dotnet.dll
        if (function_exists('com_create_guid')) {
            $guid = com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000); // optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = chr(123) . // "{"
            substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12) . chr(125); // "}"
            $guid = $uuid;
        }
        
        return str_replace('-', '', trim($guid, '{}'));
    }
}

/**
 * 链接扩展方法
 * 
 * @author rain
 * @since    1.0.0
 */
class WC_Sinic_Helper_Uri{
    /**
     * 获取当前文件的url路径(wp-content/wp-plugins)
     * @param string $file
     * @return string
     * @since 1.0.0
     */
    public static function wp_url($file)
    {
        if(!function_exists('wp_normalize_path')){
            function wp_normalize_path( $path ) {
                $path = str_replace( '\\', '/', $path );
                $path = preg_replace( '|/+|','/', $path );
                if ( ':' === substr( $path, 1, 1 ) ) {
                    $path = ucfirst( $path );
                }
                return $path;
            }
        }
        $file_path = wp_normalize_path($file);
        $file_folder=str_replace("\\", "/",dirname($file_path));
        
        if(strpos($file_folder, str_replace("\\", "/",WP_CONTENT_DIR))===0){
            return set_url_scheme(WP_CONTENT_URL).substr($file_folder, strlen(WP_CONTENT_DIR));
        }
        
        if(strpos($file_folder, str_replace("\\", "/",WP_PLUGIN_DIR))===0){
            return set_url_scheme(WP_PLUGIN_URL).substr($file_folder, strlen(WP_PLUGIN_DIR));
        }
        
        return null;
    }
    
    /**
     * 获取当前文件的目录
     * @param string $file
     * @return string
     * @since 1.0.0
     */
    public static function wp_dir($file)
    {
        $dir = trailingslashit( dirname( $file ));
        return rtrim (str_replace('\\', '/',  $dir), '/' );
    }
    
    public static function is_wechat_app(){
        return strripos(strtolower($_SERVER['HTTP_USER_AGENT']),'micromessenger')!=false;
    }
    
    public static function is_ios() {
        $ua = strtolower($_SERVER ['HTTP_USER_AGENT']);
        return strripos ( $ua, 'iphone' ) != false || strripos ( $ua, 'ipad' ) != false;
    }
    public static function is_android() {
        return strripos ( strtolower($_SERVER ['HTTP_USER_AGENT']), 'android' ) != false;
    }
    /**
     * 判断是否是移动浏览器
     * @since 1.0.0
     */
    public static function is_app_client(){
        if(!isset($_SERVER['HTTP_USER_AGENT'])){
            return false;
        }
    
        $u=strtolower($_SERVER['HTTP_USER_AGENT']);
        if($u==null||strlen($u)==0){
            return false;
        }
    
        preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/',$u,$res);
    
        if($res&&count($res)>0){
            return true;
        }
    
        if(strlen($u)<4){
            return false;
        }
    
        preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/',substr($u,0,4),$res);
        if($res&&count($res)>0){
            return true;
        }
    
        $ipadchar = "/(ipad|ipad2)/i";
        preg_match($ipadchar,$u,$res);
        if($res&&count($res)>0){
            return true;
        }
    
        return false;
    }
    /**
     * 获取当前链接
     *
     * @return string
     * @since 1.0.0
     */
    public static function get_location_uri(){
        $protocol = (! empty ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] !== 'off' || $_SERVER ['SERVER_PORT'] == 443) ? "https://" : "http://";
        return $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
    
    public static function get_new_uri($uri,$params=array()){
        $_params = array();
        $uri = WC_Sinic_Helper_Uri::get_uri_without_params($uri,$_params);
        $_params = array_merge($_params,$params);
        
        return $uri.(count($_params)>0?("?".http_build_query($_params)):"");
    }
    
    /**
     * 获取链接不带参数
     * @param string $uri
     * @param array $params
     * @return string
     */
    public static function get_uri_without_params($uri,&$params=array(),$urldecode=true){
        $urls = explode('?', $uri);
        $qty =count($urls);
        if($qty<=1){
            return $uri;
        }
        
        $paramcs = explode('&', $urls[1]);
        foreach ($paramcs as $paramc){
            $ps = explode('=', $paramc);
            if(count($ps)!=2){
                continue;
            }
        
            if($urldecode){
                $params[$ps[0]]=urldecode($ps[1]);
            }else{
                $params[$ps[0]]=$ps[1];
            }
            
        }
        
        return $urls[0];
    }
}

/**
 * 数组扩展方法
 * @author   ranj
 * @since    1.0.0
 */
class WC_Sinic_Helper_Array{
    public static function is_null_or_empty($source){
        return is_null($source)||!is_array($source)||count($source)==0;
    }
    
    /**
     * 判断数组中是否包含
     * 
     * @param array $source
     * @param function $where
     * @return bool
     */
    public static function any($source,$where=null){
        if(self::is_null_or_empty($source)){
            return false;
        }
        
        $args_qty = func_num_args();
        $params = array();
        $params[]=null;
        if($args_qty>2){
            for ($i=2;$i<$args_qty;$i++){
                $params[]=func_get_arg($i);
            }
        }
        
        foreach ($source as $item){
            if(is_null($where)){
                return true;
            }
        
            $params[0]=$item;
            if(call_user_func_array($where, $params)){
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 返回一个新的筛选后的数组
     * 
     * @param array $source
     * @param function $where
     * @return array
     * @since 1.0.0
     */
    public static function where($source,$where){
        if(self::is_null_or_empty($source)){
            return null;
        }
        
        $args_qty = func_num_args();
        $params = array();
        $params[]=null;
        if($args_qty>2){
            for ($i=2;$i<$args_qty;$i++){
                $params[]=func_get_arg($i);
            }
        }
        if(is_null($where)){
           throw new Exception('Parameter "where" is required!');
        }
        
        $results = array();
        foreach ($source as $item){
            $params[0]=$item;
            if(call_user_func_array($where, $params)){
               $results[]=$item;
            }
        }
        
        return $results;
    }
    
    /**
     * 获取数组第一项
     * 
     * @param array $source
     * @param function $where
     * @return mixed
     * @since 1.0.0
     */
    public static function first_or_default($source ,$where=null){
        if(self::is_null_or_empty($source)){
            return null;
        }
        
        $args_qty = func_num_args();
        $params = array();
        $params[]=null;
        if($args_qty>2){
            for ($i=2;$i<$args_qty;$i++){
                $params[]=func_get_arg($i);
            }
        }
        
        foreach ($source as $item){
            if(is_null($where)){
                return $item;
            }
            
            $params[0]=$item;
            if(call_user_func_array($where, $params)){
                return $item;
            }
        }
        
        return null;
    }
}
