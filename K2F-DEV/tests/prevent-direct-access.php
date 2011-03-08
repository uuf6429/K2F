<?php

	/**
	 * This script simply visits all files in CMS directly.
	 * If anything else than an empty string is returned, the test fails.
	 * The only file that is not checked is index.php; it is the developer's
	 * responsability to ensure proper coding (index.php files are meant to be
	 * accesible form the internet).
	 */

	uses('core/connect.php');

	function check_sec($dir){
		xlog('Checking folder "'.$dir.'"...');
		foreach(glob(CFG::get('ABS_K2F').$dir.'/*') as $file)
			if(file_exists($file)){
				if(is_file($file) && strtolower(substr($file,-4))=='.php' && strtolower(basename($file))!='index.php')
					xlog((($cont=Connect::get('http://'.CFG::get('SITE_NAME').CFG::get('REL_K2F').$dir.'/'.basename($file)))=='')
						? 'Passed: "'.$file.'".' : 'Error: "'.$file.'".', $cont );
				if(is_dir($file))
					check_sec($dir.DIRECTORY_SEPARATOR.basename($file));
			}else xlog('Error: File "'.$file.'" not found.');
	}

	foreach(array('apps','core','exts') as $dir)check_sec($dir);

?>