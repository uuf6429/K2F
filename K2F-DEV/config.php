<?php defined('K2F') or die;

	/**
	 * This is your own configuration file.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 28/08/2010
	 */

	/**
	 * By default, we're in Malta! How's that for some patriotism, eh?
	 */
	date_default_timezone_set('Europe/Malta');

	/**
	 * Array of combinations to replace with native directory separator
	 */
	$tmpseps=array('/','//','\\','\\\\','\\/','/\\');

	/**
	 * Utility function to return the common part between two strings (from left).
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return string The common string.
	 * @example str_common('123abc','1234567'); => '123'
	 */
	function str_common($str1,$str2){
		$ret='';
		for($i=0; $i<min(strlen($str1),strlen($str2)); $i++){
			if($str1{$i}!=$str2{$i})break;
			$ret.=$str1{$i};
		}
		return $ret;
	}
	/**
	 * Utility function to return the common part between two strings (from right).
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 * @return string The common string.
	 * @example str_common('123abc','89abc'); => 'abc'
	 */
	function str_rcommon($str1,$str2){
		return strrev(str_common(strrev($str1),strrev($str2)));
	}

	/**
	 * Convert file size from bytes to human-readable/compact format.
	 * @param integer $size Original file size in bytes.
	 * @return string Human-readable size.
	 */
	function bytes_to_human($size){
		$type=array('bytes','KB','MB','GB','TB','PB','EB','ZB','YB');
		$i=0;
		while($size>=1024){
			$size/=1024;
			$i++;
		}
		return (ceil($size*100)/100).' '.$type[$i];
	}

	CFG::set(array(
		/**
		 * The salt is a 32 byte (characters) used in several security mechanisms
		 * such as password hashing in user accounts. It is important that you
		 * change it each time you create a different K2F project.
		 */
		'SALT'=>'6"!Kk5%YsD-krO4Tfgw4+*_b)*2q#gRi',

		/**
		 * Database connection details. Used to store non-volatile data, like
		 * categories and products of a store website.
		 */
		'DB_TYPE'=>'mysql',
		'DB_HOST'=>'127.0.0.1',
		'DB_USER'=>'root',
		'DB_PASS'=>'',
		'DB_NAME'=>'mysql',
		'DB_PRFX'=>'krk_',

		/**
		 * FTP Login credentials, used to access remote file systems or when
		 * accessing local file system is problematic due to permissions (thanks
		 * to unix...).
		 */
		'FTP_HOST'=>'localhost',
		'FTP_USER'=>'',
		'FTP_PASS'=>'',

		/**
		 * The type of CMS driving K2F. As a value, it could be 'none', empty or
		 * the CMS [name] (from "cms.[name].php": eg "cms.joomla.php"=>"joomla" )
		 */
		'CMS_HOST'=>'none',

		/**
		 * This option controls whether to show the debug warning or not.
		 */
		'DEBUG_WARN'=>true,

		/**
		 * This option controls debug mode. On production servers, ensure this is
		 * set to "none" (that is, turned off).
		 * Possible values are: console, html, comment or none
		 */
		'DEBUG_MODE'=>'console',

		/**
		 * Controls how much information is to be shown. When true, a (really) huge
		 * lot of information is spit out. When false, only important details are
		 * logged (such as errors and generic framework stuff).
		 */
		'DEBUG_VERBOSE'=>true,

		/**
		 * This controls whether the whole site should be running from SSL, thus
		 * ensuring that any link is contains https instead of http.
		 */
		'SSL_MODE'=>false

	));

	/* YOU DON'T NEED TO CHANGE SETTINGS BELOW THIS POINT */

	/**
	 * Server name (eg: test.com or www.test.com or www3.test.com or sub.test.com).
	 */
	CFG::set('SITE_NAME', $_SERVER['SERVER_NAME']);


	/**
	 * Absolute filesystem path to web root install (aka docroot).
	 * @example C:/wamp/www/ OR /home/visitgoz/public_html/
	 */
	if(strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT'])===false){
		// how it works on reseller accounts...
		CFG::set('ABS_WWW', str_replace($tmpseps,DIRECTORY_SEPARATOR,str_common(getcwd(),__FILE__)));
	}else{
		// how it normally works...
		CFG::set('ABS_WWW', str_replace($tmpseps,DIRECTORY_SEPARATOR,truepath($_SERVER['DOCUMENT_ROOT']).'/'));
	}

	/**
	 * Absolute filesystem path to K2F install (docroot+K2F).
	 * @example C:/wamp/www/K2F/ OR /home/visitgoz/public_html/K2F/
	 */
	CFG::set('ABS_K2F', str_replace($tmpseps,DIRECTORY_SEPARATOR,dirname(__FILE__).'/'));

	/**
	 * K2F path relative to web root.
	 * @example /K2F/
	 */
	if(strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT'])===false){
		// how it works on reseller accounts... (reflex action: "aw god, what the heck is that?!?")
		// oh, and a little "good luck" to any future maintainer of that code ;)
		CFG::set('REL_K2F', str_replace(array('//','\\'),'/',str_replace(str_rcommon($_SERVER['SCRIPT_FILENAME'],$_SERVER['SCRIPT_NAME']),'',$_SERVER['SCRIPT_NAME']).str_replace(CFG::get('ABS_WWW'),'/',CFG::get('ABS_K2F'))));
	}else{
		// how it normally works...
		CFG::set('REL_K2F', str_replace(array('//','\\'),'/',str_replace(CFG::get('ABS_WWW'),'/',CFG::get('ABS_K2F'))));
	}

	/**
	 * Current file path relative to web root.
	 * <br>This is the currently executed, "main" file.
	 * @example C:/wamp/www/ OR /home/visitgoz/public_html/
	 */
	CFG::set('REL_WWW', dirname($_SERVER['PHP_SELF']).'/');


?>