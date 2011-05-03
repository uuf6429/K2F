<?php defined('K2F') or die;

	uses('core/errors.php','core/debug.php');

	/**
	 * Class for creating, managing and routing virtual URLs.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 12/10/2010 - Original implementation.
	 *          06/12/2010 - Now supports debugable interface.
	 *          25/04/2011 - Hotfix for route $_POST unsetter.
	 */

	class Router implements Debugable {
		/**
		 * @var array Array of routes.
		 */
		protected static $routes=array();
		/**
		 * Redirect from old url to new route.
		 * @param string $old Simple string (eg: "ice.html") or a regular expression (eg: "/^ice\.html|fire\.html/")
		 * @param string|array $new Simple string: function name (eg: "myfunction")
		 *                    Array of two strings: public static class method (eg: array("HomePage","render"))
		 *                    Array of object and string: public method of object (eg: array($obj,"render"))
		 */
		public static function add($old,$new){
			self::$routes[]=array($old,$new);
		}
		/**
		 * If no suitable page handler was found, call this method. Usually, this is automated.
		 */
		public static function handle(){
			$uri=$_SERVER['REQUEST_URI'];
			// clean up GET and REQUEST parameters from router trigger
			unset($_REQUEST['k2fhandleroute']);
			unset($_POST['k2fhandleroute']);
			unset($_GET['k2fhandleroute']);
			// do some logging
			xlog('Handling route ',$uri,$_REQUEST);
			if(CFG::get('DEBUG_VERBOSE'))xlog(count(self::$routes),' routes:',self::$routes);
			// handle the routes
			foreach(self::$routes as $route){
				list($regex,$function)=$route;
				$matches=array();
				Errors::trap_errors_begin();
				$result=preg_match($regex,$uri,$matches);
				if(Errors::trap_errors_end()==0 && $result){	// it is a regex and it matched
					self::_call($function,$regex,$matches);
				}elseif($regex==$uri)							// it is not a regex
					self::_call($function,$regex);
			}
		}
		/**
		 * Returns whether $regex is a regular expression or not.
		 * @param string $regex The text to check for.
		 * @return boolean Whether it is a regex or not.
		 */
		protected static function _is_regexp($regex){
			Errors::trap_errors_begin();
			preg_match($regex,'');
			return Errors::trap_errors_end()==0;
		}
		/**
		 * Call $function and pass it some stuff.
		 * @param string|array $function Same thing as the 2nd argument in ::add ($new).
		 * @param string $match The regex or url that matched with the current URI.
		 * @param array $matches Array of matches if $match is a regex, otherwise, it is null.
		 */
		protected static function _call($function,$match,$matches=null){
			if(is_string($function) || (is_array($function) && count($function)==2 &&
				((is_string($function[0]) || is_object($function[0])) && is_string($function[1])))){
				call_user_func($function/*,$match,$matches*/); // <-- not sure if I should be passing the regex and matches?
			}else trigger_error('Call type or format is not supported.',E_USER_ERROR);
		}
		/**
		 * Returns debug information.
		 */
		public static function onDebug(){
			return self::$routes;
		}
	}
	
	// Ensure routing handling is done at very end
	function _router_fini(){ register_shutdown_function(array('Router','handle')); }
	// If k2fhandleroute is set, do pass control to the routing mechanism
	if(isset($_REQUEST['k2fhandleroute']))register_shutdown_function('_router_fini');
		
?>