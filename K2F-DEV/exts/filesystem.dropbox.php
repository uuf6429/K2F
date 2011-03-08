<?php defined('K2F') or die;

	uses('core/connect.php');

	/**
	 * Add Dropbox streamwrapper support to PHP.
	 * @example ftp://username:password@host/path/to/file
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 08/10/2010
	 */
	class Dropbox {
		/**
		 * @var string Dropbox application API key.
		 */
		public static $APIKEY='';
		/**
		 * @var string Dropbox application API secret.
		 */
		public static $SECRET='';
		/**
		 * @var string User account token given after login is accepted.
		 */
		protected static $TOKEN='';
		/**
		 * @var string URL to dropbox API service.
		 */
		protected static $API_URL='https://api.dropbox.com/';
		/**
		 * @var string API version.
		 */
		protected static $API_VERSION='0';
		/**
		 * @var string The current user's email.
		 */
		protected static $USER_EMAIL='';
		/**
		 * Login to dropbox account.
		 * @return boolean Whether successful or not.
		 */
		public static function login($email,$pass){
			self::$USER_EMAIL=$email;
			$url=self::$API_URL.self::$API_VERSION.'/token';
			$vars=array(
				'oauth_consumer_key'=>self::$APIKEY, // undocumented (?)
				//'callback'=>'', // unused
				'email'=>$email,
				'password'=>$pass,
				'status_in_response'=>true
			);
			$res=@json_decode(Connect::post($url,$vars));
			if($res->status==200)self::login_save($email,$res->body->token,$res->body->secret);
			return $res->status==200;
		}
		/**
		 * Saves dropbox login token for later (re)use.
		 * @param string $email Account's email.
		 * @param string $token Login token.
		 * @param string $secret Login secret.
		 */
		private static function login_save($email,$token,$secret){
if(!isset($GLOBALS['dbemail']))$GLOBALS['dbemail']=array();
$GLOBALS['dbemail'][$email]=array('token'=>$token,'secret'=>$secret);
		}
		/**
		 * Loads previously saved dropbox login token.
		 * @param string $email Account's email.
		 * @return array|null An array of "token" and "secret" or null if no such account exists.
		 */
		private static function login_load($email){
return isset($GLOBALS['dbemail'][$email]) ? $GLOBALS['dbemail'][$email] : null;
		}
		public static function account_details(){
			$url=self::$API_URL.self::$API_VERSION.'/account/info';
			$vars=array(
	//			'oauth_consumer_key'=>self::$APIKEY, // undocumented (?)
				//'callback'=>'', // unused
				'status_in_response'=>true
			);
			xlog(Connect::post($url,$vars)); die;
			$res=@json_decode(Connect::post($url,$vars));
			xlog($res);
		}
		public static function account_create(){

		}
	}

?>