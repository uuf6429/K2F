<?php

	/**
	 * This script reports if any PHP files have trailing whitespace at the end.
	 * This would potentially break up systems which rely on setting headers
	 * and cookies etc.
	 */

	uses('core/connect.php');

	function check_eofw($dir){
		xlog('Checking folder "'.$dir.'"...');
		foreach(glob(CFG::get('ABS_K2F').$dir.'/*') as $file)
			if(file_exists($file)){
				if(is_file($file) && strtolower(substr($file,-4))=='.php' && strtolower(basename($file))!='index.php'){
					$data=file_get_contents($file);
					xlog(strlen(trim($data))==strlen($data)
						? 'Passed: "'.$file.'".' : 'Error: "'.$file.'".' );
				}
				if(is_dir($file))
					check_eofw($dir.'/'.basename($file));
			}
	}

	foreach(array('apps','core','exts') as $dir)check_eofw($dir);

?>