<?php

	/**
	 * This script generates a report on all subclasses which do not properly
	 * extended their respective parents.
	 * This is especially useful to determine which classes are still under
	 * development and how much left they've got.
	 */

	/**
	 * Returns all sub classes of $class in a hierarchal manner.
	 * @param string $class Target class name to get it's children from.
	 * @return array Class names in arrays forming a tree.
	 */
	function get_class_hierarchy($class){
		$sub=array();
		foreach(get_class_children($class) as $cls)
			if($cls!=$class)
				$sub[$cls]=get_class_hierarchy($cls);
		return $sub;
	}

	// load all files in /exts to ensure we operate on all classes.
	foreach(glob(CFG::get('ABS_K2F').'exts/*.php') as $file)
		uses('exts/'.basename($file));

	// build class hierarchal tree
	$classes=array('stdClass'=>get_class_hierarchy('stdClass'));

	// todo: actually compare methods of child-parent relationship of each such combination.
	// important: this is the most important aspect of this script!!

	xlog('Hierchal Tree of Classes',$classes);

?>