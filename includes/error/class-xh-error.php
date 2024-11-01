<?php
if (! defined ( 'ABSPATH' ))
	exit (); // Exit if accessed directly
/**
 * Error
 *
 * @since 1.0.0
 * @author 		ranj
 */
class WC_Sinic_Error{
    public $errcode,$errmsg,$data,$errors=array();

    /**
     * initialize
     * 
     * @since  1.0.0
     * @param int $errcode
     * @param string $errmsg
     */
	public function __construct($errcode=0, $errmsg='',$data=null) {
		$this->errcode = $errcode;
		$this->errmsg = $errmsg;
		$this->data = $data;
		$this->errors = array (
		    403 => __('Sorry!Your are offline.',WC_SINIC),
		    404 => __('The resource was not found!',WC_SINIC),
		    405 => __('Your account has been frozen!',WC_SINIC),
		    500 => __('Server internal error, please try again later!',WC_SINIC),
		    501 =>__('You are accessing unauthorized resources!',WC_SINIC),
		    600 =>__('Your request is invalid!',WC_SINIC),
		    700 => __('Frequent operation, please try again later!',WC_SINIC),
		    701 => __('Sorry,Your request is timeout!',WC_SINIC),
		    1000 => __('Sorry,Network error!',WC_SINIC)
		);
	}
	
	/**
	 * Success result.
	 * 
	 * @since  1.0.0
	 * @return WC_Sinic_Error
	 */
	public static function success($data=null) {
		return new WC_Sinic_Error ( 0, '' ,$data);
	}
	
	/**
	 * Unknow error result.
	 *
	 * @since  1.0.0
	 * @return WC_Sinic_Error
	 */
	public static function error_unknow() {
		return new WC_Sinic_Error ( - 1, __('Ops!Something is wrong.',WC_SINIC) );
	}
	
	public static function wp_error($error) {
	    if(is_wp_error($error))
	    return new WC_Sinic_Error ( - 1, $error->get_error_message() );
	    
	    return self::error_unknow();
	}
	/**
	 * Custom error result.
	 *
	 * @since  1.0.0
	 * @param string $errmsg
	 * @return WC_Sinic_Error
	 */
	public static function error_custom($errmsg='') {
	    if($errmsg instanceof Exception){
	        $errmsg ="errcode:{$errmsg->getCode()},errmsg:{$errmsg->getMessage()}";
	    }else if($errmsg instanceof WP_Error){
	        $errmsg ="errcode:{$errmsg->get_error_code()},errmsg:{$errmsg->get_error_message()}";
	    }
		return new WC_Sinic_Error ( - 1, $errmsg );
	}
	
	/**
	 * Defined error result.
	 *
	 * @since  1.0.0
	 * @param int $error_code
	 * @return WC_Sinic_Error
	 */
	public static function err_code($err_code) {
	    $self = WC_Sinic_Error::error_unknow ();
	    
	    if(isset($self->errors[$err_code])){
	        $self->errcode=$err_code;
	        $self->errmsg=$self->errors[$err_code];
	    }
	    
	    return $self;
	}
	
	/**
	 * check error result is valid.
	 *
	 * @since  1.0.0
	 * @param WC_Sinic_Error $woo_ch_error
	 * @return bool
	 */
	public static function is_valid(&$woo_ch_error) {
	    if(!$woo_ch_error){
	        $woo_ch_error = WC_Sinic_Error::error_unknow ();
	        return false;
	    }
	    
	    if($woo_ch_error instanceof WC_Sinic_Error){
	        return $woo_ch_error->errcode == 0;
	    }
	    

	    if(isset($woo_ch_error->errcode)){
	        return $woo_ch_error->errcode == 0;
	    }
	    
	    return true;
	}
	
	/**
	 * serialize the error result.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_json() {
		return json_encode ( array(
				'errcode'=>$this->errcode,
				'errmsg'=>$this->errmsg,
		         'data'=>$this->data
		));
	}
	
	public function to_wp_error(){
	    return new WP_Error($this->errcode,$this->errmsg,$this->data);
	}
	
	public function to_string(){
	    return "errcode:{$this->errcode};errmsg:{$this->errmsg}";
	}
}