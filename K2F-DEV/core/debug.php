<?php defined('K2F') or die;

	/**
	 * Provides a simple interface debugging classes.<br>
	 * <b>NB:</b> In order to use it with your class (for example "MyClass"):<br>
	 *   <b>1.</b> Inlcude this file via <code>uses('core/debug.php');</code>
	 *   <b>2.</b> Ensure your class uses the interface (change "class MyClass" to "class MyClass implements Debugable".<br>
	 *   <b>3.</b> Create a public static method named "onDebug", it should return debug info (any data type).<br>
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 06/12/2010 - Initial implementation.
	 */
	interface Debugable {
		public static function onDebug();
	}

	/**
	 * Cross-version instanceof replacement.
	 * @param object $obj The instance to check.
	 * @param stirng $cls The class/interface name to look for.
	 * @return boolean Whether an object extends or implements a particular class
	 *     or interface.
	 * @link http://stackoverflow.com/questions/4365567/php-instanceof-over-strings-and-non-initializable-classes
	 */
	function is_instance_of($obj,$cls){
		if(class_exists('ReflectionClass')){
			$rc=new ReflectionClass($obj);
			return $rc->implementsInterface($cls);
		}elseif(function_exists('is_a')){
			return is_a($obj,strtolower($cls));
		}elseif(PHP_MAJOR_VERSION>4){
			return eval('return $obj instanceof strtolower($cls);'); // ugly ugly hack!
		}
		return false;
	}

	function k2f_do_debug(){
		foreach(get_declared_classes() as $class)
			if(is_instance_of($class,'Debugable'))
				xlog('Debug of class '.$class,call_user_func(array($class,'onDebug')));
	}
	if(CFG::get('DEBUG_VERBOSE'))
		register_shutdown_function('k2f_do_debug');

	class AssertException extends Exception { }

	class Debug {
		public static function assert($check, $level=E_WARNING){
			if(!$check){
				$trace=debug_backtrace(); $trace=array_shift($trace); list($file,$line)=array_values($trace);
				throw new AssertException("An assertion failed ($file:$line).", E_WARNING);
			}
		}
	}

?>