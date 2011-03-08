<?php

	/**
	 * This script ensures all files have a main fully qualified PHPDoc comment.
	 */

	uses('exts/phpdoc.php');
	
	function check_phpdoc($dir){
		xlog('Checking folder "'.$dir.'"...');
		foreach(glob(CFG::get('ABS_K2F').$dir.'/*') as $file)
			if(file_exists($file)){
				if(is_file($file) && strtolower(substr($file,-4))=='.php'){
					$pdcs=PhpDoc::parse(file_get_contents($file));
					xlog(isset($pdcs[0]) ? 'Passed: "'.$file.'".' : 'Error: "'.$file.'".' );
				}elseif(is_dir($file))
					check_phpdoc($dir.'/'.basename($file));
			}
	}

	foreach(array('apps','core','exts') as $dir)check_phpdoc($dir);
	
?>