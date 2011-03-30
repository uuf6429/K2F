<?php
/**
 * This software was created for the WKHTMLTOPDF (now WKHTMLTOX) project.
 * It has been adapted for the WKHTMLTOX library in K2F, using K2F codebase.
 * The original comments contained:
 *
 * Since setting up WKHTMLTOPDF in a PHP environment is particularly troublesome,
 * I've decided to write this script to check for preconditions and test the current
 * enviroment and various system settings.  Christian Sciberras 27/06/2010
 *
 * !! SECURITY WARNING !!
 * This file may disclose sensitive info about your server.
 * REMOVE THIS FILE FROM YOUR PRODUCTION SERVER.
 */


uses('exts/wkhtmltox.php','core/system.php');

class WKDBG extends WKCORE { // debugger class extends WKCORE class to gain access to protected methods
	public $result=array(); // publish a property
	/**
	 * @var array This array holds md5 hashes of known executable versions. This is to detect the file version, NOT a security measure!!
	 */
	protected static $versions=array(
		'hash'                             => 'version',
		'fa6553e6db1b4e5f139a4a227305868c' => '0.10.0 rc1',
		'af2e951fd859233d3c749ae2f62d1857' => '0.10.0 rc1',
		'2870d5c819196f5fe6cccfe9c621a0b7' => '0.10.0 rc1',
		'43aa77d943a6b3053422aea8a4b0f75a' => '0.10.0 beta4 64bit',
		'1f47dce2755ee7d5f8f9ba20f8ad5b3d' => '0.10.0 beta4 32bit',
		'cd8c2d843aa2527a27ea20c7fd011051' => '0.10.0 beta3 64bit',
		'957c193e4bb94a8837d9d11bd31473b9' => '0.10.0 beta3 32bit',
		'2b2ef37c75aaa7fd5138ed63e5867cff' => '0.9.0 64bit',
		'5fd04b20c815964746feae5d205ad25d' => '0.9.0 32bit',
		'b5005eba4fc70aaa7497ac31256ad801' => '0.9.0 beta2 64bit',
		'ae5bbcc5482a5c2ce2f18b299b34c0e3' => '0.9.0 beta2 32bit',
		'598c17fc1a00d06139f0d860002c45f1' => '0.9.0 beta3 64bit',
		'6b7b124fe8cbf18b567cbd036e0eceb0' => '0.9.0 beta3 32bit',
		'b7aacd9965adf777da0aa9889ddb1f3c' => '0.9.0 beta4 64bit',
		'1131e03d6bd6a8cedea5c290dd827ecd' => '0.9.0 beta4 32bit',
		'5f3027450421f753a42cb82b10957dbc' => '0.9.1 64bit',
		'a0dae0d6ce0e0fe8754069947b00a7b4' => '0.9.1 32bit',
		'238f39412b15acfa21b69697e6568697' => '0.9.2 64bit',
		'839ce650620b6582032e66fad456ac11' => '0.9.2 32bit',
		'65291ca8cc371863b74ae9c6ac0acfe1' => '0.9.3 64bit',
		'f5978ecedf1d86898f9fa25cb7090609' => '0.9.3 32bit',
		'4913ba06cb0898d21f4e5e8f607f1ddc' => '0.9.4 64bit',
		'323981838611b2f39b2ea07aab99780f' => '0.9.4 32bit',
		'89df50be1c69eb099bb4bfbbc8e7c1ba' => '0.9.5 64bit',
		'aeca4b7d77238e07a47fc2f254b4c0c3' => '0.9.5 32bit',
		'1c039b81e9d539805992a04fee96f192' => '0.9.6 64bit',
		'eeabdab03bff108d969876dc80af1c43' => '0.9.6 32bit',
		'63f855f8bc7a22d63d8ec1e86f8f8788' => '0.9.7 64bit',
		'8c4c289e6cc38a74c31f7312b1be59c9' => '0.9.7 32bit',
		'63f855f8bc7a22d63d8ec1e86f8f8788' => '0.9.8 64bit',
		'8c4c289e6cc38a74c31f7312b1be59c9' => '0.9.8 32bit',
		'bbbf179ae462305d51ad9a762d73e2a5' => '0.9.9 64bit',
		'6c5b72184e88805d30e0bfdab2e22a53' => '0.9.9 32bit',
	);
	/**
	 * Logs assertion resulted together with debug message.
	 * @param boolean $assertion True if assertion indicates success, false otherwise.
	 * @param mixed $success Message shown on success.
	 * @param mixed $failure (Optional) Message shown on failure.
	 */
	protected static function assert($assertion,$success,$failure=null){
		if($failure===null)$failure=$success; // for repeated messages
		if(!$assertion && !is_string($success)){
			xlog('Error:',$failure);
		}else{
			xlog($assertion ? $success : (is_string($failure) ? 'Error: '.$failure : array('Error: ',$failure)));
		}
	}
	/**
	 * Function for attempting to recognize executable version from file data.
	 * @return string|null If recognized, the version is returned as a string, otherwise null is returned.
	 */
	protected static function detect(){
		if(!file_exists(self::exe('wkhtmltopdf')))return false;
		$hash=md5_file(self::exe('wkhtmltopdf'));
		return isset(self::$versions[$hash]) ? self::$versions[$hash] : null;
	}
	/**
	 * Perform testing routines.
	 */
	public static function test(){
		$pev=self::detect();
		$exe=is_executable(self::exe('wkhtmltopdf'));
		$fex=file_exists(self::exe('wkhtmltopdf'));
		$ecd='';
		switch(substr(System::os_type(),0,3)){
			case 'win': $ecd=System::execute('CD',null,false);  break;
			case 'lin': $ecd=System::execute('PWD',null,false); break;
			case 'osx': $ecd=System::execute('PWD',null,false); break;
		}

		// check php stuff
		self::assert(substr(phpversion(),0,1)!='4.','PHP Version is '.phpversion(),'PHP version is unsupported (too old).');
		self::assert(!ini_get('safe_mode'),'Safe mode is OFF.','Safe mode should be turned OFF (it is ON).');
		self::assert(getcwd()!==false, 'PHP Script CWD: '.getcwd());
		self::assert($ecd!='' && $ecd['return']==0,'PHP Exec CWD: '.$ecd['stdout'],'Failed getting current working directory!');

		// check system stuff
		self::assert(System::os_type()!='','OS Type: '.System::os_type());
		self::assert(System::cpu_make()!='','CPU Make: '.System::cpu_make(),'Failed determining CPU vendor!');
		self::assert(System::php_bits()!=0,'Bit Mode: '.System::php_bits().'bit','Failed determining bit mode!');

		// check wkhtmltox stuff
		self::assert(self::exe('wkhtmltopdf')!='','Executable Path: '.self::$exe,'Failed generating executable path!');
		self::assert($fex,'Executable MD5: '.md5_file(self::exe('wkhtmltopdf')),'Executable file Not Found.');
		self::assert($exe,'Executable file is actually executable.','Executable file is *not* executable or doesn\'t exist!');
		self::assert($pev,'Program version '.$pev.'.','Program version not recognized (could be that it\'s too new, which is ok).');

		// run some demo commands
		$cmd='dir';
		$out=System::execute($cmd,null,false);
		$out=array_merge(array('cmd'=>$cmd),$out);
		self::assert($out['return']==0,$out);

		// strace run
		$wcf=' is not recognized as an internal or external command,'.chr(13).chr(10).'operable program or batch file.'.chr(13).chr(10);
		if( (($cmd='strace "'  .self::exe('wkhtmltopdf').'" --version') && ($out=System::execute($cmd,null,false)) && $out['stderr']!='\'strace\''  .$wcf)
		 || (($cmd='stracent "'.self::exe('wkhtmltopdf').'" --version') && ($out=System::execute($cmd,null,false)) && $out['stderr']!='\'stracent\''.$wcf) ){
			$out=array_merge(array('cmd'=>$cmd),$out);
			self::assert($out['return']==0,$out);
		}else self::assert(false,array(
				'The `strace` utility is not installed. THIS UTILITY IS NOT MANDATORY. It helps in debugging native code. You can get it here:',
				'http://www.intellectualheaven.com/default.asp?BH=projects&H=strace.htm',
				'Download v0.8 and extract the files somewhere. Finally, move "StraceNT.exe" to your system folder, usually "C:\\WINDOWS\\".'
			));

		// advanced cpu info
		switch(substr(System::os_type(),0,3)){
			case 'win':
				self::assert(true,'Architecture: '.getenv('PROCESSOR_ARCHITECTURE'));
				self::assert(true,'Identifier: '.getenv('PROCESSOR_IDENTIFIER'));
				break;
			case 'osx':
				$cmd='machine';
				$out=System::execute($cmd,null,false);
				$out=array_merge(array('cmd'=>$cmd),$out);
				self::assert($out['return']==0,$out);
				break;
			case 'lin':
				$cmd='cat /proc/cpuinfo';
				$out=System::execute($cmd,null,false);
				$out=array_merge(array('cmd'=>$cmd),$out);
				self::assert($out['return']==0,$out);
				break;
		}

		// let's screenshoot google :)
		try {
			self::assert($img=new WKIMG(),'Test IMG Render: Started.','Test IMG Render: Start failed.');
			$img->set_source(WKIMG::SRC_URL,'http://www.google.com/');
			$img->set_format($fmt=WKIMG::FMT_JPG);
			self::assert($img->render(),'Test IMG Render: Rendering....done!','Test IMG Render: Rendering....failed!');
			$file=CFG::get('ABS_K2F').'libs/wkhtmltox/demo-'.time().'.'.$fmt;
			self::assert($img->output(WKIMG::OUT_SAVE,$file),'Test IMG Render: Finished successfully.','Test IMG Render: Output failed!');
			echo '<img src="'.str_replace(CFG::get('ABS_K2F'),CFG::get('REL_K2F'),$file).'" width="1024"/>';
		} catch (Exception $e) {
			self::asert(false,'',$e);
		}

		// let's adobify google :)
		try {
			self::assert($pdf=new WKPDF(),'Test PDF Render: Started.','Test PDF Render: Start failed.');
			$pdf->set_source(WKPDF::SRC_URL,'http://www.google.com/');
			$pdf->set_margins('0px','0px','0px','0px');
			self::assert($pdf->render(),'Test PDF Render: Rendering....done!','Test PDF Render: Rendering....failed!');
			$file=CFG::get('ABS_K2F').'libs/wkhtmltox/demo-'.time().'.pdf';
			self::assert($pdf->output(self::OUT_SAVE,$file),'Test PDF Render: Finished successfully.','Test PDF Render: Output failed!');
			echo '<iframe src="'.str_replace(CFG::get('ABS_K2F'),CFG::get('REL_K2F'),$file).'" width="1024" height="400"></iframe>';
		} catch (Exception $e) {
			self::asert(false,'',$e);
		}

		// let's adobify some sites... :)
		try {
			self::assert($pdf=new WKPDF_MULTI(),'Test PDF_MULTI Render: Started.','Test PDF_MULTI Render: Start failed.');
			$pdf->add_source(WKPDF::SRC_URL,'http://www.google.com/');
			$pdf->add_source(WKPDF::SRC_HTML,
				'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
				<head><title>TEST TEST TEST</title></head><body><h1>Local Content!!!</h1></body></html>');
			$pdf->add_source(WKPDF::SRC_URL,'http://www.facebook.com/');
			$pdf->set_margins('0px','0px','0px','0px');
			self::assert($pdf->render(),'Test PDF_MULTI Render: Rendering....done!','Test PDF_MULTI Render: Rendering....failed!');
			$file=CFG::get('ABS_K2F').'libs/wkhtmltox/demo-'.time().'.pdf';
			self::assert($pdf->output(self::OUT_SAVE,$file),'Test PDF_MULTI Render: Finished successfully.','Test PDF_MULTI Render: Output failed!');
			echo '<iframe src="'.str_replace(CFG::get('ABS_K2F'),CFG::get('REL_K2F'),$file).'" width="1024" height="400"></iframe>';
		} catch (Exception $e) {
			self::asert(false,'',$e);
		}

		// render PDF for some heavyweight website...
		try {
			self::assert($pdf=new WKPDF(),'Test PDF Render: Started.','Test PDF Render: Start failed.');
			$pdf->set_source(WKPDF::SRC_URL,'http://epicagency.net/');
			$pdf->set_margins('0px','0px','0px','0px');
			self::assert($pdf->render(),'Test PDF Render: Rendering....done!','Test PDF Render: Rendering....failed!');
			$file=CFG::get('ABS_K2F').'libs/wkhtmltox/demo-'.time().'.pdf';
			self::assert($pdf->output(self::OUT_SAVE,$file),'Test PDF Render: Finished successfully.','Test PDF Render: Output failed!');
			echo '<iframe src="'.str_replace(CFG::get('ABS_K2F'),CFG::get('REL_K2F'),$file).'" width="1024" height="400"></iframe>';
		} catch (Exception $e) {
			self::asert(false,'',$e);
		}

	}
	/// SATISFY ABSTRACT METHODS ///
	public function cmd($tmp){}
	public function output($mode=self::OUT_DOWNLOAD,$file=null){}
}

// Start testing!!
WKDBG::test();

?>