<?php defined('K2F') or die;

	/**
	 * A class which abstracts cookie manipulation.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 28/08/2010
	 */
	class Cookies {
		/**
		 * Sets the value of a cookie. The cookie is cleared if $value is empty.<br>
		 * <b>NB:</b> Cookie changes are immediate, no need for page refreshes.
		 * @param string $name Cookie name.
		 * @param string $value Cookie value.
		 * @param string $time Cookie time to live date (set to 0 for session cookie).
		 */
		public static function set($name,$value='',$time=0){
			// the domain is set in a way to fix development on localhost
			$domain=$_SERVER['SERVER_ADDR']=='127.0.0.1' ? false : '.'.$_SERVER['SERVER_NAME'];
			if(!headers_sent() && setcookie($name,$value,$time,'/',$domain,CFG::get('SSL_MODE'))){
				if($time<0){ // clear
					unset($_COOKIE[$name]);
				}else{ // set
					$_COOKIE[$name]=$value;
				}
			}else xlog('Error: Cannot set cookie; headers were already sent!',func_get_args(),debug_backtrace());
		}
		/**
		 * Gets the value of a cookie.
		 * @param string $name Cookie name.
		 * @param sring $default This value is returned when cookie doesn't exist. Defaults to an empty string.
		 * @return string Cookie data (or empty string if not found).
		 */
		public static function get($name,$default=''){
			return ( isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default );
		}
		/**
		 * Clears/removes a cookie.
		 * @param string $name Cookie name.
		 */
		public static function clear($name){
			self::set($name);
		}
	}

?>