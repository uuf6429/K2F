<?php

	/**
	 * This is the main update script for K2F, providing an all-in-one update system.
	 * <b>IMPORTANT:</b> Simply running this script won't work.
	 * K2F update requires that you first need to define the following constants:
	 * K2F - The current K2F versions, would look like "2.3.0d".
	 * ABS_WWW - Same value as that of CFG::get('ABS_WWW').
	 * ABS_K2F - Same value as that of CFG::get('ABS_K2F').
	 * REL_WWW - Same value as that of CFG::get('REL_WWW').
	 * REL_K2F - Same value as that of CFG::get('REL_K2F').
	 * @author Christian Sciberras
	 * @copyright 2011 Covac Software
	 * @version 28/02/2011 - Initial implementation.
	 */

	// precondition/security checks
	defined('K2F') or die; // K2F not set?! Unforgivable!!
	(defined('ABS_WWW') && file_exists(ABS_WWW)) or die('ABS_WWW is wrong.');
	(defined('ABS_K2F') && file_exists(ABS_K2F)) or die('ABS_K2F is wrong.');
	(defined('REL_WWW')) or die('REL_WWW is wrong.');
	(defined('REL_K2F')) or die('REL_K2F is wrong.');
	// todo: more precondition checks go here, to ensure all requirements are met

	// calculate initial url
	$url=array(); $nra=array('step'); // non-repeatable args
	foreach($_GET as $k=>$v)if(!in_array($k,$nra))$url[]=urlencode($k).'='.urlencode($v);
	$url=REL_WWW.'?'.implode('&',$url);
	
	// approximate 404 detector :)
	$step=isset($_GET['step']) ? (int)$_GET['step'] : 1;
	if($step<1 || $step>3)header('HTTP/1.0 404 Not Found',true,404);

	// rudimentary website pages system
	echo '<div class="k2f-update">';
	switch($step){
		case 1: // run sanity check and find update changes
			echo '<h2>Checking your system...</h2>';
			$files=array(
				'core'=>glob(ABS_K2F.'core/*.php'),
				'exts'=>glob(ABS_K2F.'exts/*.php'),
				'libs'=>glob(ABS_K2F.'libs/*'),
				'apps'=>glob(ABS_K2F.'apps/*')
			);
			echo '<b>Core Files:</b> '.implode(', ',array_map('basename',$files['core'])).'<br/>';
			echo '<b>Extensions:</b> '.implode(', ',array_map('basename',$files['exts'])).'<br/>';
			echo '<b>Libraries:</b> '.implode(', ',array_map('basename',$files['libs'])).'<br/>';
			echo '<b>Applications:</b>'.implode(', ',array_map('basename',$files['apps'])).'<br/>';
			echo '<h2>Checking for updates...</h2>';
			break;
		case 2: // select update options
			break;
		case 3: // synchronize files with master (may take some time)
			break;
		default:
			echo 'Sorry, requested resource was not found on the server.';
	}
	echo '</div>';

?>