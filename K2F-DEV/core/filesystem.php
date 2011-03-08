<?php defined('K2F') or die;

	if(CFG::Get('FTP_HOST')!='')uses('exts/filesystem.ftp.php');

	/**
	 * A class which provides more accurate and powerful means of filesystem (FS) management.
	 * <br><b>IMPORTANT:</b> This is a straigh-forward and absolute system, each command should be assumed to work unconditionally.
	 * <br>Given a set of settings, (FTP credentials granting root access etc), the class should always work as intended.
	 * <br>In special cases such as not enough disk space, it is up to the developer to handle the situation before hand.
	 * <br>As required by the K2F Coding Guidelines, each considerable event is logged, even in the case of errors.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 04/10/2010
	 * @todo 1. Create functionality :-)
	 */
	class Filesystem {
		/**
		 * Returns file contents as a string.
		 * @param string $file Path and name to file.
		 * @return string The file's content or an empty string if the file cannot be read, does not exist or is empty.
		 */
		public static function read($file){

		}
		/**
		 * Sets the contents of a file; the file is either <b>overwritten</b> or when non-existent, it is created!
		 * @param string $file Path and name to file.
		 * @param string $contents The new contents.
		 */
		public static function write($file,$contents){

		}
		/**
		 * Returns an array of file system objects (files, folders, symlinks etc) given a path and a wildcard.
		 * <br>For wildcard support, see (unix column): http://en.wikipedia.org/wiki/Glob_%28programming%29#Syntax
		 * @param string $path Path name and ending with wildcard.
		 */
		public static function listing($path){
			
		}
	}

?>