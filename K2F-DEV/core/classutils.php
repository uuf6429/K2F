<?php defined('K2F') or die;

	/**
	 * Class/object utility routines.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 01/11/2010 - Initial implementation.
	 *          24/02/2011 - Fixed bug in get_class_parent() concerning get_parent_class() returnign false for non-parented classes.
	 */

	/**
	 * Returns an array of direct child classes.
	 * @param object|string $class Either the object in question or it's classname.
	 * @return array An array of (string) class names.
	 */
	function get_class_children($class){
		if(is_object($class))$class=get_class($class);
		$classes=get_declared_classes();
		foreach($classes as $i=>$child)
			if(get_class_parent($classes[$i])!=$class)
				unset($classes[$i]);
		return array_values($classes);
	}

	/**
	 * Returns an array of all child classes (and their children).
	 * @param object|string $class Either the object in question or it's classname.
	 * @return array An array of (string) class names.
	 */
	function get_class_grandchildren($class){
		if(is_object($class))$class=get_class($class);
		$classes=get_declared_classes();
		foreach($classes as $i=>$child)
			if(!in_array($class,get_class_ancestors($child)))
				unset($classes[$i]);
		return array_values($classes);
	}

	/**
	 * Returns an array of all classes which are a direct child to this class' parent, excluding this class.
	 * @param object|string $class Either the object in question or it's classname.
	 * @return array An array of (string) class names.
	 */
	function get_class_siblings($class){
		if(is_object($class))$class=get_class($class);
		$classes=get_class_children(get_class_parent($class));
		unset($classes[array_search($class,$classes)]);
		return array_values($classes);
	}

	/**
	 * Returns an array of all classes that are parent to a class, including stdClass.
	 * @param object|string $class Either the object in question or it's classname.
	 * @return array An array of (string) class names.
	 */
	function get_class_ancestors($class){
		if(is_object($class))$class=get_class($class);
		$classes=array();
		while($class=get_class_parent($class))
			$classes[]=$class;
		return $classes;
	}

	/**
	 * Returns the class' parent (a fixed alias for get_parent_class).
	 * @param string|object $class Class name or object.
	 * @return string Parent class name or empty string on failure.
	 * @link http://www.php.net/manual/en/function.get-parent-class.php#82564 PHP returns false for non-parented classes.
	 */
	function get_class_parent($class){
		return $class=='stdClass' ? '' : (($cls=get_parent_class($class))!==false ? $cls : (class_exists($class) ? 'stdClass' : ''));
	}

	/**
	 * Similar to get_called_class(), this returns the last class that called this function.
	 * Ironically, it works better than the PHP one...
	 * @return string Class name or false on error.
	 */
	function get_class_name(){
		if(function_exists('get_called_class') && (__CLASS__!='') && ($class=@get_called_class()))return $class; // performance hotfix
		$main=''; $class='';
		foreach(debug_backtrace() as $trace){
			if($main!=''){
				if(!isset($trace['class']))break;
				if($trace['class']!=$main && !in_array($main,get_class_ancestors($trace['class'])))break;
				$class=$trace['class'];
			}elseif(isset($trace['class'])){
				if($trace['function']=='__construct')
					return get_class($trace['object']); // special case: constructors
				$main=$trace['class'];
			}
		}
		return $class!='' ? $class : ($main!='' ? $main : false);
	}

	/**
	 * Get the static property of a class name or property of an object instance.
	 * @param string|object $class Target class name or object instance.
	 * @param string $prop The property's name.
	 * @return mixed The value of the object or class property.
	 */
	function get_class_prop($class,$prop){
		if(is_string($class) && class_exists($class)){
			$vars=get_class_vars($class);
			return isset($vars[$prop]) ? $vars[$prop] : null;
		}elseif(is_object($class))
			return $class->$prop;
		return null;
	}

?>