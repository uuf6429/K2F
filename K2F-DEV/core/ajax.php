<?php defined('K2F') or die;

	uses('core/debug.php','core/events.php');

	/**
	 * A class which abstracts AJAX calls to static class methods.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 01/06/2010 - Initial implementation.
	 *          10/11/2010 - Modified ::handle() to show different errors.
	 *          10/11/2010 - Method ::url() accepts starting character as argument.
	 *          10/11/2010 - Modified ::handle() to empty buffer before output.
	 *          06/12/2010 - Now supports debugable interface.
	 *          09/01/2011 - A small hotfix to ensure handle() is always called once.
	 *          09/01/2011 - handle() can be made to return instead of echo result.
	 *          04/03/2011 - A hotfix has been issued to prevent unexpected redirection on output as well as setting ajax http status to 200.
	 *          12/04/2011 - A hotfix to allow people to cancel ajac call be unsetting $_REQUEST['ajax'].
	 *          15/04/2011 - Changed "?ajax..." to "?ajax=1" (old one didn't work in POST).
	 */
	class Ajax implements Debugable {
		protected static $handled=false;
		protected static $callable=array();
		/**
		 * Adds a class method to ajax access list.
		 * @param string $class Class name.
		 * @param string $method Method name.
		 * @param array $params An array of expected method parameters (name=>type).
		 * @example
		 *   class Math {
		 *     public static function add($first,$second){
		 *       return $first+$second;
		 *     }
		 *   }
		 *   Ajax::register('Math','add',array('first'=>'float','second'=>'float'));
		 */
		public static function register($class='',$method='',$params=array()){
			if(!isset(self::$callable[$class]))self::$callable[$class]=array();
			self::$callable[$class][$method]=$params;
		}
		/**
		 * Removes a class method from ajax access list.
		 * @param string $class Class name.
		 * @param string $method Method name.
		 */
		public static function unregister($class='',$method=''){
			if(!isset(self::$callable[$class]))self::$callable[$class]=array();
			unset(self::$callable[$class][$method]);
		}
		/**
		 * Returns a list of all ajax access methods.
		 * @param boolean $asString If true, methods are returned in a nice string format.<br>
		 *                          Otherwise, they are returned in their raw format. Default to true.
		 * @return mixed If asString is true, a string is returned, otherwise, an array of classes=>methods=>params is returned.
		 */
		public static function get($asString=true){
			if($asString)return self::$callable;
			$result=array();
			foreach(self::$callable as $class=>$methods)
				foreach($methods as $method=>$enabled)
					$result=$class.'::'.$method.'() ['.($enabled?'on':'off').']';
			return implode(CRLF,$result);
		}
		/**
		 * Returns whether the specified method is accessible or not.
		 * @param string $class Class name.
		 * @param string $method Method name.
		 * @return boolean Whether it exists or not.
		 */
		public static function exists($class,$method){
			return isset(self::$callable[$class]) && isset(self::$callable[$class][$method]);
		}
		/**
		 * Utility function to call a specific class method.
		 * @param string $cls Class name.
		 * @param string $mtd Method name.
		 * @return mixed Return value of method call.
		 */
		protected static function call($cls,$mtd){
			$params=self::$callable[$cls][$mtd];
			foreach($params as $name=>$type){
				$params[$name]=isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
				settype($params[$name],$type);
			}
			return call_user_func_array(array($cls,$mtd),array_values($params));
		}
		/**
		 * Handles any ajax calls. In normal circumstances, you shouldn't call this directly.
		 * @param boolean $return (Optional) Used to make this function return the result instead of echoing it. Default to false.
		 * @return string If $return is true, this is the json-encoded data response to the request.
		 */
		public static function handle($return=false){
			if(!self::$handled){
				self::$handled=true;
				// magic quotes in wordpress hotfix
				if(CFG::get('CMS_HOST')=='wordpress'){
					$_GET     = array_map( 'stripslashes_deep', $_GET );
					$_POST    = array_map( 'stripslashes_deep', $_POST );
					$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );
				}
				// ensure nothing was echoed up till now
				if(ob_get_level())ob_end_clean();
				// handle request
				$cls=isset($_REQUEST['cls']) ? $_REQUEST['cls'] : '';
				$mtd=isset($_REQUEST['mtd']) ? $_REQUEST['mtd'] : '';
				$result=self::exists($cls,$mtd)
					? self::call($cls,$mtd) : $result=array('success'=>false,'reason'=>'inexistent '.(!isset(self::$callable[$cls])?'class':'method'));
				if($return)return @json_encode($result);
				echo @json_encode($result);
			}
		}
		/**
		 * Constructs a url to an ajax method call.<br>
		 * Don't forget that it must be followed with relevant arguments.
		 * @param string $class Class name.
		 * @param string $method Method name.
		 * @param string $startChar Starting character (defaults to '?').
		 * @return string The generated url.
		 * @example
		 *   &lt;script type="text/javascript"&gt;
		 *     $.getJSON("<?php echo Ajax::url('Math','add'); ?>&first=5&second=2"); // returns 7
		 *   &lt;/script&gt;
		 */
		public static function url($class,$method,$startChar='?'){
			return $startChar.'ajax=1&cls='.urlencode($class).'&mtd='.urlencode($method);
		}
		/**
		 * This returns whether ajax mode is on or not.
		 * Make sure that if it is on, your code doesn't output ANYTHING to the browser.
		 * @return boolean Whether AJAX is on or not.
		 */
		public static function is_on(){
			return isset($_REQUEST['ajax']);
		}
		/**
		 * Similar to handle, this one renders the output and dies right here.
		 */
		public static function render(){
			while(ob_get_level())ob_end_clean();								// weird joomla hotfix
			$result=self::handle(true);
			if(function_exists('header_remove'))header_remove('Location');		// hotfix for location getting set for some dumb reason
			header('HTTP/1.0 200 OK',true,200);									// hotfix for location and http://bugs.php.net/bug.php?id=25044
			if(Ajax::is_on())die($result);
		}
		/**
		 * Returns debug data.
		 */
		public static function onDebug(){
			return self::$callable;
		}
	}

	// if ajax flag is set, process ajax request after booting
	if(Ajax::is_on())Events::add('on_after_boot',array('Ajax','render'));

?>