<?php

	/**
	 * This is the bootloader for K2F. In order to use K2F in your system, just include this file.
	 * @copyright 2010 Covac Software
	 * @author K2F Team
	 * @version 28/08/2010 - Initial implementation.
	 *          10/01/2010 - Minor update to _k2f_boot_check: only the faulty extention is reported, not parent loaders AND also handles error_get_last - very helpful.
	 */

	/**
	 * @var string Used to check if files were loaded from K2F or not as well as K2F version.
	 */
	define('K2F','2.3.0d');

	/**
	 * This function is to replace PHP's extremely buggy realpath().
	 * @param string The original path, can be relative etc.
	 * @return string The resolved path, it might not exist.
	 */
	function truepath($path){
		// whether $path is unix or not
		$unipath=strlen($path)==0 || $path{0}!='/';
		// attempts to detect if path is relative in which case, add cwd
		if(strpos($path,':')===false && $unipath)
			$path=getcwd().DIRECTORY_SEPARATOR.$path;
		// resolve path parts (single dot, double dot and double delimiters)
		$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
		$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
		$absolutes = array();
		foreach ($parts as $part) {
			if ('.'  == $part) continue;
			if ('..' == $part) {
				array_pop($absolutes);
			} else {
				$absolutes[] = $part;
			}
		}
		$path=implode(DIRECTORY_SEPARATOR, $absolutes);
		// resolve any symlinks
		if(file_exists($path) && linkinfo($path)>0)$path=readlink($path);
		// put initial separator that could have been lost
		$path=!$unipath ? '/'.$path : $path;
		return $path;
	}

	/**
	 * Core class for managing configuration.
	 * @copyright 2010 Covac Software
	 * @author K2F Team
	 * @version 28/08/2010
	 */
	class CFG {
		/**
		 * @var boolean (default is true) Set this to false if you want to
		 * lock existing configuration settings so that they won't be overwritten
		 * this is useful in case where you want to set some config before
		 * actually loading the config file, so you ensure that the config file
		 * will not mess any settings you did earlier.
		 * @example
		 *     CFG::set('DEBUG_MODE','console');
		 *     CFG::$override=false;
		 *     include_once('config.php');
		 */
		public static $override=true;
		/**
		 * @var array Configuration storage.
		 */
		private static $store=array();
		/**
		 * Set value of a configuration.
		 * @param string|array $config Either config name (string) or an array of name=>value pairs.
		 * @param string|null $value (Optional) The $config's new value or null when $config is an array.
		 */
		public static function set($config,$value=null){
			if(is_array($config)){
				foreach($config as $name=>$value)
					if(self::$override || !isset(self::$store[$name]))
						self::$store[$name]=$value;
			}elseif(self::$override || !isset(self::$store[$config]))
				self::$store[$config]=$value;
		}
		/**
		 * Returns the value of a configuration or the default value if not set.
		 * @param string $config The configuration name.
		 * @param mixed $default (Optional) returned when $config doesn't exist.
		 * @return mixed Configuration or default value.
		 */
		public static function get($config,$default=null){
			return isset(self::$store[$config]) ? self::$store[$config] : $default;
		}
		/**
		 * Returns whether a config exists or not.
		 * @param string $config The configuration name.
		 * @return boolean Whether it exists or not.
		 */
		public static function exists($config){
			return isset(self::$store[$config]);
		}
		/**
		 * Returns all of the current config.
		 * @return array Array of config details.
		 */
		public static function all(){
			return self::$store;
		}
	}

	/**
	 * Load any existing configuration in $GLOBALS['K2F_AUTOCONF'].<br>
	 * This feature is used in cases where you want to suppply certain settings<br>
	 * from outside of the framework (perhaps a main CMS or config file).<br>
	 * <b>IMPORTANT</b> Any configuration in this variable supersedes settings<br>
	 * in K2F's config.php, however, settings can be changed later on normally.
	 * <b>IMPORTANT</b> This global variable is removed later on, as a<br>
	 * security precaution.
	 */
	if(isset($GLOBALS['K2F_AUTOCONF'])){
		CFG::set($GLOBALS['K2F_AUTOCONF']);
		unset($GLOBALS['K2F_AUTOCONF']);
	}

	/**
	 * Load configuration details.
	 */
	CFG::$override=false;
	require_once('config.php');
	CFG::$override=true;

	/**
	 * This holds initial logging information.
	 */
	$GLOBALS['K2F_XLGBF']=array();
	
	/**
	 * This is a buffered version of xlog; data is stored internally until
	 * xlog is initialized. After xlog initialization, any calls to this
	 * function are routed directly to xlog.
	 */
	function xlogb(){
		$args=func_get_args();
		if(is_array($GLOBALS['K2F_XLGBF']))
			$GLOBALS['K2F_XLGBF'][]=$args;
		else
			call_user_func_array('xlog',$args);
	}
	/**
	 * This function is called to handled any buffered xlog calls.
	 */
	function xlogh(){
		foreach($GLOBALS['K2F_XLGBF'] as $args)
			call_user_func_array('xlog',$args);
		$GLOBALS['K2F_XLGBF']=null;
	}

	if(CFG::get('DEBUG_VERBOSE')){
		xlogb('Verbose debug mode is on.');
		xlogb('Configuration loaded:',CFG::all());
	}

	$GLOBALS['K2F_FILES']=array();
	$GLOBALS['K2F_BINDT']=0;
	/**
	 * This function is a better way for including extra files.
	 * @param string Any number of parameters will be loaded as files.
	 * @todo Needs a complete rewrite, too much unintuitive code!
	 */
	function uses(){
		foreach(func_get_args() as $file){
			if(strpos($file,CFG::get('ABS_K2F'))===false)$file=CFG::get('ABS_K2F').$file;
			$file=truepath($file);
			if(!isset($GLOBALS['K2F_FILES'][$file])){
				$prof=microtime(true);
				$GLOBALS['K2F_BINDT']++;
				xlogb(str_repeat('  ',$GLOBALS['K2F_BINDT']).'Boot is loading "'.basename($file).'"');
				$GLOBALS['K2F_FILES'][$file]=false;
				if(!(include_once $file)){
					xlogb(str_repeat('  ',$GLOBALS['K2F_BINDT']).'Error: included file "'.$file.'" not found');
					die('A required file could not be found.');
				}
				xlogb(str_repeat('  ',$GLOBALS['K2F_BINDT']).'Boot loaded "'.basename($file).'" in '
					.number_format(microtime(true)-$prof,6).'s. Headers? '.(headers_sent()?'Sent':'None'));
				$GLOBALS['K2F_BINDT']--;
				$GLOBALS['K2F_FILES'][$file]=true;
			}
		}
	}
	uses('core/core.php');
	xlogh();

	/**
	 * Boot sequence supervisor and general profiler.
	 */
	function _k2f_boot_check3(){ // the real supervisor function (complete with profiling)
		foreach(array_reverse($GLOBALS['K2F_FILES']) as $file=>$load)
			if(!$load){
				xlog('Error: Boot sequence aborted due to faulty extension: ',$file,'Last Known Error:',error_get_last());
				die('Error: Boot sequence aborted due to faulty extension: '.basename($file));
				break;
			}
		// the following is a list of possibles errors thrown by careless CMSes and which we don't we'll just ignore.
		$ignore=E_NOTICE | E_USER_NOTICE | E_STRICT | E_RECOVERABLE_ERROR | E_DEPRECATED | E_USER_DEPRECATED;
		if(is_object($err=error_get_last()) && ($err->type & $ignore))
			xlog('Error: General Script Failure',$err);
		$t=number_format(microtime(true)-$GLOBALS['K2F-PROF']['t'],6);
		$m=bytes_to_human(memory_get_usage()-$GLOBALS['K2F-PROF']['m']);
		$p=bytes_to_human(memory_get_peak_usage(true));
		xlog('K2F Finished in ~'.$t.'s taking ~'.$m.' (peak '.$p.').');
	}
	// gather initial profiler details
	$GLOBALS['K2F-PROF']=array('t'=>microtime(true),'m'=>memory_get_usage());

	// the following code ensures the real function is called at the very last
	function _k2f_boot_check1(){ register_shutdown_function('_k2f_boot_check2'); }
	function _k2f_boot_check2(){ register_shutdown_function('_k2f_boot_check3'); }
	register_shutdown_function('_k2f_boot_check1');

	// trigger event if Event support is enabled (before boot?)
	if(class_exists('Events'))Events::call('on_before_boot');

	// load core classes
	foreach(glob(CFG::get('ABS_K2F').'core/*.php') as $file)
		uses('core/'.basename($file));
	
	// trigger event if Event support is enabled
	if(class_exists('Events'))Events::call('on_after_boot');

?>