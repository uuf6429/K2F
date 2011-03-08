<?php defined('K2F') or die;

	uses('core/security.php','core/cookies.php','exts/oodb.php','core/legacy.php');

	/**
	 * A simplistic user authentication system complete with users and groups.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 20/11/2010 - Initial implementation.
	 *          08/02/2011 - Class Users missed overriding method ->table().
	 */

	class Users extends DatabaseRows {
		/**
		 * @var User User object of the current user.
		 */
		protected static $user=null;
		/**
		 * Returns the currently logged in user or null if none.
		 * @return User|null The current user or null.
		 */
		public static function current(){
			if(!self::$user){
				$user=self::singularize(get_called_class()); $user=new $user();
				if( $user->load('`username`="'.Security::escape(@base64_decode(Cookies::get('k2fsuu'))).'"')
				 && $user->password==@base64_decode(Cookies::get('k2fsup')) )
					self::$user=$user;
			}
			return self::$user;
		}
		/**
		 * Returns whether user is logged in or not (by testing current()).
		 * @return boolean True if logged in, false otherwise.
		 */
		public static function logged_in(){
			return self::current()!=null;
		}
		/**
		 * Log the user in given user credentials.
		 * @param string $username Plain text username.
		 * @param string $password Plain text password.
		 * @param boolean $remember Whether to remember user or not.
		 * @return boolean True if user got authenticated, false otherwise.
		 */
		public static function log_in($username,$password,$remember){
			$user=self::singularize(get_called_class()); $user=new $user(); $time=strtotime('+1 week');
			if($user->load('username="'.Security::escape($username).'"')
			&& $user->password==self::hash($password)){
				Cookies::set('k2fsuu',base64_encode($user->username),$remember ? $time : 0);
				Cookies::set('k2fsup',base64_encode($user->password),$remember ? $time : 0);
				return true;
			}
			return false;
		}
		/**
		 * Logs the user put.
		 */
		public static function log_out(){
			Cookies::clear('k2fsuu');
			Cookies::clear('k2fsup');
		}
		/**
		 * Hash a token (such as username or password).
		 * @param string $original The original plain text.
		 * @return string The resulting hash.
		 */
		public static function hash($original){
			return hash_hmac('md5',$original,CFG::get('SALT'));
		}
		public function table(){
			return 'users';
		}
	}

	class User extends DatabaseRow {
		/**
		 * @var string The username.
		 */
		public $username='';
		/**
		 * @var string In truth, this is the hashed password, not the original one.
		 */
		public $password='';
		/**
		 * @var string Email address of user.
		 */
		public $email='';
		public function table(){
			return 'users';
		}
	}

?>