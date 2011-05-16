<?php defined('K2F') or die;

	/**
	 * A class for accessing information and run code related to the operating system.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 19/09/2010
	 */
	class System {
		/**
		 * Executes a program and waits for it to finish, taking pipes into account.
		 * @param string $cmd Command line to execute, including any arguments.
		 * @param string $input Data for standard input.
		 * @param boolean $log Whether to log execution failures or not (defaults to true).
		 * @return array Array of "stdout", "stderr" and "return".
		 */
		public static function execute($cmd,$stdin=null,$log=true){
			$proc=proc_open($cmd,array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
			fwrite($pipes[0],$stdin);                fclose($pipes[0]);
/// old code >>
			$stdout=stream_get_contents($pipes[1]);  fclose($pipes[1]);
			$stderr=stream_get_contents($pipes[2]);  fclose($pipes[2]);
/// << old | new >>
//			// See: http://code.google.com/p/wkhtmltopdf/wiki/IntegrationWithPhp  (Oct 19, 2010)
//			// From http://php.net/manual/en/function.proc-open.php#89338
//			$read_output = $read_error = false;
//			$buffer_len  = $prev_buffer_len = 0;
//			$ms          = 10;
//			$stdout      = '';		$stderr      = '';
//			$read_output = true;	$read_error  = true;
//			stream_set_blocking($pipes[1], 0);
//			stream_set_blocking($pipes[2], 0);
//			while($read_error || $read_output){
//				if($read_output){
//					if(feof($pipes[1])){
//						fclose($pipes[1]);
//						$read_output=false;
//					}else{
//						$str=fgets($pipes[1], 1024);
//						$len=strlen($str);
//						if($len){
//							$stdout.=$str;
//							$buffer_len+=$len;
//						}
//					}
//				}
//				if($read_error){
//					if(feof($pipes[2])){
//						fclose($pipes[2]);
//						$read_error=false;
//					}else{
//						$str=fgets($pipes[2], 1024);
//						$len=strlen($str);
//						if($len){
//							$stderr.=$str;
//							$buffer_len+=$len;
//						}
//					}
//				}
//				if($buffer_len>$prev_buffer_len){
//					$prev_buffer_len=$buffer_len;
//					$ms=10;
//				}else{
//					usleep($ms*1000); // sleep for $ms milliseconds
//					if($ms<160)$ms=$ms*2;
//				}
//			}
/// << new code
			$return=proc_close($proc);
			if($return!=0 && $log)xlog('Error: Program execution returned failure.',$stdout,$stderr,$return);
			return array( 'stdout'=>$stdout, 'stderr'=>$stderr, 'return'=>$return );
		}
		/**
		 * Returns operating system type. Possible values:<br>
		 * osx linux windows solaris netware aix hpux os400 os2 unknown
		 * riscos irix
		 * @link http://publib.boulder.ibm.com/tividd/td/ITCM/SC23-4712-01/en_US/HTML/cmmst170.htm#cegcihib
		 *       Big thanks to IBM for sharing!
		 * @return string OS type, without versions etc.
		 */
		public static function os_type(){
			$uname = strtolower(php_uname());
			// tested and well-known
			if(strpos($uname,'darwin') !==false)return 'osx';
			if(strpos($uname,'linux')  !==false)return 'linux';
			if(strpos($uname,'win')    !==false)return 'windows';
			// untested but according to IBM link
			if(strpos($uname,'sunos')  !==false)return 'solaris';				// @todo: untested
			if(strpos($uname,'netware')!==false)return 'netware';				// @todo: untested
			if(strpos($uname,'aix')    !==false)return 'aix';					// @todo: untested
			if(strpos($uname,'hp-ux')  !==false)return 'hpux';					// @todo: untested
			if(strpos($uname,'os 400') !==false)return 'os400';					// @todo: untested
			if(strpos($uname,'os/2')   !==false)return 'os2';					// @todo: untested
			// untested and ... uh ... an educated guess
			if(strpos($uname,'irix')   !==false)return 'irix';					// @todo: untested
			if(strpos($uname,'risc')   !==false)return 'risc';					// @todo: untested
			// I give up....what the heck re you running on?!
			xlog('Warning: Could not detect OS type ('.$uname.').');
			return 'unknown';
		}
		/**
		 * Returns the number of bits of this PHP process.<br>
		 * HOWEVER, this does not mean it is the same as the OS or CPU bitness;<br>
		 * The OS might be 32bit running on a 64bit CPU or it could be a 64bit OS running a 32bit PHP.
		 * @return integer Number of bits (or 0 on error/unknown).
		 */
		public static function php_bits(){
			return PHP_INT_SIZE*8;
		}
		/**
		 * Returns the CPU's vendor.
		 * @todo Support other OSes.
		 * @return string Vendor name (intel,ppc,amd) or empty string if not known.
		 */
		public static function cpu_make(){
			$procid='';
			// get vendor string
			switch(self::os_type()){
				case 'linux':
					$res=self::execute('grep -m 1 vendor_id /proc/cpuinfo');
					$procid=strtolower($res['stdout']);
					break;
				case 'osx':
xlog('Error: not yet available on osx');
					break;
				case 'windows':
					$procid=strtolower(getenv('PROCESSOR_IDENTIFIER'));
					break;
				default:
					xlog('Warning: Don\'t know how to get CPU type from your OS ('.self::os().').');
			}
			// detect vendor
			if(strpos($procid,'intel')  !==false)return 'intel';
			if(strpos($procid,'amd')    !==false)return 'amd';
			if(strpos($procid,'ppc')    !==false)return 'ppc';
			if(strpos($procid,'powerpc')!==false)return 'ppc';
			return '';
		}
		/**
		 * This is supposed to return the number of real CPU bits.<br>
		 * It is highly platform dependent and thus, very difficult to achieve
		 * and test on all platforms.
		 * @return integer Number of bits (or 0 on error/unknown).
		 */
		public static function cpu_bits(){
			switch(self::os_type()){
				case 'linux':
					$res=self::execute('grep -m 1 flags /proc/cpuinfo');
					$res=explode(' ',strtolower($res['stdout']));
					// rm=16 tm=32 lm=64
					if(array_search('rm',$res)!==false)return 16;
					if(array_search('tm',$res)!==false)return 32;
					if(array_search('lm',$res)!==false)return 64;
					return 0;
				case 'osx':
					$res=self::execute('sysctl hw.cpu64bit_capable > /dev/null 2>&1');
xlog('Error: CPU bitness check on OSX is not yet functional.',$res);
					return 0; // @todo: change this according to $res
				case 'windows':
					// the idea I had at this point, would be creating 3 files
					// dynamically and running them each (via self::execute()), and
					// then analysing the results. This could be done progressivey,
					// eg, if running a 64bit file works, return 64, otherwise if
					// running a 32bit file works, return 32...

					// executable bitness and data definition (descending)
					$exes=array(
						64=>'',
						32=>'',
						16=>''
					);
					// try each executable till one of them works
					foreach($exes as $bits=>$data){
						$file=CFG::get('ABS_K2F').'k2f-'.$bits.'-dummy.exe'; // use mt_rand() + microtime() instead
			//			file_put_contents($file,$data);
						$res=self::execute('"'.$file.'"','',CFG::get('DEBUG_VERBOSE'));
			//			unlink($file);
						if($res['return']==0)return $bits;
					}
					// none of them worked :(
					xlog('Warning: No predefined windows executable ran on your system.');
					break;
				default:
					// platform not supported
					xlog('Warning: Your platform is not supported for bitness check ('.self::os_type().').');
			}
			return 0;
		}
		/**
		 * Type constant for files.
		 */
		const TMP_FILE = 0;
		/**
		 * Type constant for directories.
		 */
		const TMP_DIR  = 1;
		/**
		 * Create a temporary filesystem object given some details.
		 * @param integer $type Defines what is to be created (see TMP_* constants).
		 * @param string $root Specifies the root path of where file/dir will be created.
		 * @param string $prefix Specifies a name prefix (eg: 'TMP').
		 * @param string $suffix Specifies a name suffix (useful for file extensions, eg: '.tmp').
		 * @param boolean $autoclean If true, the file/directory will be removed after script finishes running.
		 * @return string|null Full absolute name to newly created file/dir or null on failure.
		 */
		public static function temporary($type=-1,$root='',$prefix='',$suffix='',$autoclean=true){
			if($root!='' && substr($root,-1,1)!=DIRECTORY_SEPARATOR)$root.=DIRECTORY_SEPARATOR;
			if($type==self::TMP_FILE){
				while(!($file=@fopen($name=$root.$prefix.time().'-'.mt_rand().$suffix,'xb')));
				@fclose($file);
				if($autoclean)$GLOBALS['K2F-TFD']['f'][]=$name;
				return $name;
			}elseif($type==self::TMP_DIR){
				while(!@mkdir($name=$root.$prefix.time().'-'.mt_rand().$suffix,true));
				if($autoclean)$GLOBALS['K2F-TFD']['d'][]=$name;
				return $name;
			}else return null;
		}
	}
	$GLOBALS['K2F-TFD']=array('f'=>array(),'d'=>array());
	function _tmp_cleanup(){
		foreach($GLOBALS['K2F-TFD']['f'] as $file)@unlink($file);
		foreach($GLOBALS['K2F-TFD']['d'] as $dir)@rmdir($dir);
	}
	register_shutdown_function('_tmp_cleanup');

?>