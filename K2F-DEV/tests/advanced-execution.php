<?php

	/**
	 * Does some testing over executable invokation (with pipes).
	 */

	define('EXAMPLE_CRASH',true); // whether to run testcase or not

	if(isset($argc)){
		// load framework
		$GLOBALS['K2F_AUTOCONF']=array('DEBUG_MODE'=>'none');
		require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'boot.php');

		//System::write(str_repeat('O',EXAMPLE_CRASH ? 20060 : 1023));
		//die(100);
		
		// run cli testcase
		System::write(str_repeat('o',EXAMPLE_CRASH ? 1030 : 1023));
		System::error(str_repeat('e',EXAMPLE_CRASH ? 1030 : 1023));
		System::write(str_repeat('O',EXAMPLE_CRASH ? 1030 : 1023));
		//System::error(str_repeat('E',EXAMPLE_CRASH ? 1030 : 1023));
		//System::writeln();
		//System::writeln('What is your name?');
		//System::writeln('Pleased to meet with you, '.System::readln().'!');
		die(128);

	}else{
		// find path to PHP interpreter
		if(System::os_type()=='windows'){ // windows
			$php=explode(chr(10),shell_exec('wmic process where "handle='.getmypid().'" get CommandLine'));
			$php=isset($php[1]) ? trim($php[1],' "'.CR.LF) : null;
		}else{ // unix
			$php=explode(chr(10),shell_exec('ps -p '.getmypid().' -o cmd'));
			$php=isset($php[1]) ? trim($php[1],' "'.CR.LF) : null;
		}
		if(!$php || strpos($php,'php')===false)$php='C:\wamp\bin\php\php5.3.1\php.exe';
		$php=escapeshellarg(str_ireplace('php-cgi','php',$php));
		// run execution testcase
		die_r(System::execute($php.' -f '.escapeshellarg(__FILE__),"Chris\n",false));

	}

?>