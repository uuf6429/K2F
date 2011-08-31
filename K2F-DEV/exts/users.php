<?php defined('K2F') or die;

	uses('core/security.php','core/cookies.php','exts/oodb.php','core/legacy.php');

	/**
	 * A simplistic user authentication system complete with users and groups.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 20/11/2010 - Initial implementation.
	 *          08/02/2011 - Class Users missed overriding method ->table().
	 *          03/06/2011 - Fix hotfix for bug that caused first-time login to fail.
	 */

	class Users extends DatabaseRows {
		/**
		 * @var User User object of the current user.
		 */
		public static $user=null;
		/**
		 * Returns the currently logged in user or null if none.
		 * @param boolean $cached True to return cached result, false to renew login.
		 * @return User|null The current user or null.
		 */
		public static function current($cached=true){
			if(!$cached || !self::$user){
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
			$user=self::singularize(get_called_class()); $user=new $user();
			if($user->load('`username`="'.Security::escape($username).'"')
			&& $user->password==self::hash($password)){
				$user->login($remember);
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
		/**
		 * Login as this user.
		 * @param boolean $remeber Whether to remember user login for a week or not.
		 */
		public function log_in($remember){
			$time=strtotime('+1 week');
			Cookies::set('k2fsuu',base64_encode($this->username),$remember ? $time : 0);
			Cookies::set('k2fsup',base64_encode($this->password),$remember ? $time : 0);
			Users::$user=$this;
		}
		/**
		 * Returns whether this is the currently logged in user or not.
		 * @return boolean True if logged in, false otherwise.
		 */
		public function logged_in(){
			return Users::$user===$this;
		}
		/**
		 * Log this user out from the system.
		 */
		public function log_out(){
			if($this->logged_in())
				Users::log_out();
		}
	}

?>