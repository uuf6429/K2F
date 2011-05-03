<?php defined('K2F') or die;

	uses('core/events.php','core/debug.php','core/classutils.php','core/security.php');

	/**
	 * A set of classes which virtualizes an application ecosystem.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 02/11/2010 - Original implementation.
	 *          06/12/2010 - Now supports debugable interface (where applicable).
	 *          06/01/2011 - Discovered a huge flaw in using get_called_class; fixed using get_class_name instead.
	 *          20/01/2011 - Added on_[before|after]_[app|apps]_load events.
	 *          07/04/2011 - Fixed application boot to ignore files in apps folder.
	 */

	class Application {

		/// EXTENDABLE PROPERTIES ///

		/**
		 * Short application name.
		 * @var string Application title.
		 */
		public $name='Untitled';
		/**
		 * Short application description.
		 * @var string Application description.
		 */
		public $description='No description';
		/**
		 * Current application verion
		 * @var string Period separated integers for; major, minor, build, revision.
		 */
		public $version='0.0.0.0';
		/**
		 * The date when application was created. IMPORTANT: Even if updated, this is the CREATION DATE.<br/>
		 * You set it's value ONCE when you create the application the FIRST TIME!
		 * @var string A date in the format "day month year", or anything understandable by PHP's strtotime.<br/>
		 *      The prefered format is like: "5 Jan 2009".
		 */
		public $date='';
		/**
		 * A set of tags which describe this application.
		 * @var string Comma/space separated list of tags.
		 */
		public $tags='';

		/// UTILITIES SECTION (DO NOT EXTEND) ///

		/**
		 * @var array An array of class=>instance of current application instances.
		 */
		protected static $instances=array();

		/**
		 * Returns the path to the current application's root.<br/>
		 * Example: foreach(glob($this->path().'*') as $file)echo $file.'&lt;br&rt;';
		 * @final DO NOT OVERRIDE THIS METHOD!
		 */
		public static function path(){
			return CFG::get('ABS_K2F').'apps/'.get_class(self::instance()).'/';
		}

		/**
		 * Returns the URL to the current application's root.<br/>
		 * Example: <img src="<?php echo $this->url(); ?>add.png">
		 * @final DO NOT OVERRIDE THIS METHOD!
		 */
		public static function url(){
			return CFG::get('REL_K2F').'apps/'.get_class(self::instance()).'/';
		}

		/**
		 * Returns application singleton instance for a class.
		 * @return Application|null The application instance or null on error.
		 */
		public static function instance(){
			$class=get_class_name();// die_r(__CLASS__,get_class(),__FILE__,getcwd(),debug_backtrace());
			return isset(self::$instances[$class]) ? self::$instances[$class] : null;
		}

		/// MANEGERIAL SECTIONS (INTERNAL) ///

		/**
		 * Perform some initialization stuff.
		 */
		final public function  __construct(){
			// Bind the current $this's methods to event stack.
			Events::add('on_admin_menu',array($this,'on_admin_menu'));
			Events::add('on_registered_menu',array($this,'on_registered_menu'));
			Events::add('on_guest_menu',array($this,'on_guest_menu'));
			// Make this the default application instance.
			self::$instances[get_class_name()]=$this;
		}
		/**
		 * Perform some final cleanup.
		 */
		final public function   __destruct(){
			// Remove all events which bind to $this.
			Events::remove('',$this);
		}

		/// EVENTS AND OVERRIDABLES ///

		/**
		 * Returns an AppIcon object which should contain paths to this app's main icons.
		 * @return AppIcon An object for managing paths to differently sized icons.
		 * @abstract
		 */
		public function mainicon(){ return new AppIcon(); }
		/**
		 * This event is fired if and only if:
		 * <br>- the user is logged in
		 * <br>- the user type is administrative (does not include eg; managers or authors)
		 */
		public function on_admin_menu(){ }
		/**
		 * This event is fired if and only if:
		 * <br>- the user is logged in
		 * <br>- the user type is non-administrative (including eg; managers or authors)
		 */
		public function on_registered_menu(){ }
		/**
		 * This event is fired if and only if:
		 * <br>- the user is not logged in
		 */
		public function on_guest_menu(){ }
	}

	class Applications implements Debugable {
		/**
		 * @var array List of application classname=>instances pairs.
		 */
		public static $apps=array();
		/**
		 * Performs initial application loading.
		 */
		public static function _init(){
			Events::call('on_before_apps_load');
			foreach(glob(CFG::get('ABS_K2F').'apps/*') as $appname)
				if(is_dir($appname))
					self::load(basename($appname));
			Events::call('on_after_apps_load');
		}
		/**
		 * Get rid of loaded application ;)
		 */
		public static function _fini(){
			Events::call('on_before_apps_unload');
			foreach(self::$apps as $appclass=>$inst)self::unload($appclass);
			Events::call('on_after_apps_unload');
		}
		/**
		 * Register a new application class name.
		 * @param string $appclass Application class name.
		 */
		public static function register($appclass){
			if(self::loaded($appclass))return false;
			self::$apps[$appclass]=new $appclass();
			return true;
		}
		/**
		 * @todo Extract zip into folder.
		 */
		public static function install($appname,$zipdata){
		}
		/**
		 * @todo Delete folder entirely.
		 */
		public static function uninstall($appname){
		}
		/**
		 * Returns whether a specific application is installed or not.
		 * @param string $appname Application (folder) name to check.
		 * @return boolean True if installed, false otherwise.
		 */
		public static function installed($appname){
			return file_exists(CFG::get('ABS_K2F').'apps/'.Security::filename($appname));
		}
		/**
		 * Loads an application from file system.
		 * @param string $appname Application (folder) name to load.
		 * @return boolean True on load success, false on error.
		 */
		public static function load($appname){
			if(self::loaded($appname))return true; // already loaded
			$dir=CFG::get('ABS_K2F').'apps/'.$appname;
			if(is_dir($dir) && file_exists($dir.'/index.php')){
				$prof=microtime(true);
				Events::call('on_before_app_load',array($appname));
				xlog('Application "'.$appname.'" is loading...');
				if(CFG::get('DEBUG_VERBOSE')){
					$prof_t=microtime(true);
					$prof_m=memory_get_usage();
				}
				if(!(include_once $dir.'/index.php')){
					xlog('Error: could not load application "'.$appname.'".');
				}else{
					if(CFG::get('DEBUG_VERBOSE')){
						$t=number_format(microtime(true)-$prof_t,6);
						$m=bytes_to_human(memory_get_usage()-$prof_m);
						xlog('Application "'.$appname.'" loaded in '.$t.'s ('.$m.' alloc).');
					}
					return true;
				}
				Events::call('on_after_app_load',array($appname));
			}else xlog('Warning: Application "'.$appname.'" might be corrupt.');
			return false;
		}
		/**
		 * Properly and cleanly removes a loaded K2F application.
		 * @param string $appclass The application's class name.
		 * @return boolean True on success, false otherwise.
		 */
		public static function unload($appclass){
			// if app was not loaded, fail
			if(!self::loaded($appclass))return false;
			// do some logging
			if(CFG::get('DEBUG_VERBOSE')){
				xlog('Application "'.basename($appclass).'" is unloading...');
				$prof_t=microtime(true); $prof_m=memory_get_usage();
			}
			// call event handlers
			Events::call('on_before_app_unload',array($appclass));
			// destroy instance
			self::$apps[$appclass]->__destruct();
			// remove from list
			unset(self::$apps[$appclass]);
			// finish logging
			if(CFG::get('DEBUG_VERBOSE')){
				$t=number_format(microtime(true)-$prof_t,6);
				$m=bytes_to_human($prof_m-memory_get_usage());
				xlog('Application "'.basename($appclass).'" unloaded in '.$t.'s ('.$m.' dealloc).');
			}
			// call event handlers
			Events::call('on_after_app_unload',array($appclass));
		}
		/**
		 * Returns whether a specific application was loaded or not.
		 * @param string $appclass The application's class name.
		 * @return boolean True if loaded, false otherwise.
		 */
		public static function loaded($appclass){
			return isset(self::$apps[$appclass]);
		}
		public static function onDebug(){
			return self::$apps;
		}
	}
	// Initialize applications automatically
	Applications::_init();
	// Ensure application finalization is done at very end
	function _app_fini(){ register_shutdown_function(array('Applications','_fini')); }
	// Finalize applications automatically
	register_shutdown_function('_app_fini');

	/**
	 * Holds a set of paths for icons (png) of different sizes.
	 */
	class AppIcon {
		/**
		 * Properties for icon paths of different resolutions.
		 */
		public $_16='', $_32='', $_48='', $_64='', $_128='';
		/**
		 * Creates a new multi-sized icon.
		 * @param string $_16 Path to a 16x16 icon.
		 * @param string $_32 Path to a 32x32 icon.
		 * @param string $_48 Path to a 48x48 icon.
		 * @param string $_64 Path to a 64x64 icon.
		 * @param string $_128  Path to a 128x128 icon.
		 */
		public function __construct($_16='',$_32='',$_48='',$_64='',$_128=''){
			$this->_16=$_16;
			$this->_32=$_32;
			$this->_48=$_48;
			$this->_64=$_64;
			$this->_128=$_128;
		}
	}

?>