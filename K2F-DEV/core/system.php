<?php defined('K2F') or die;

	/**
	 * A class for accessing information and run code related to the operating system.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 19/09/2010
	 */
	class System {
		/**
		 * Write some text with a new line character to standard output.
		 * @param string $data Text contents.
		 */
		public static function writeln($data){
			self::write($data.CR);
		}
		/**
		 * Write some text to standard output.
		 * @param string $data Text contents.
		 */
		public static function write($data){
			if(!self::is_cli())trigger_error('Error, PHP was not invoked from command line.');
			fwrite(STDOUT,$data);
		}
		/**
		 * Write an error message to standard errors stream.
		 * @param string $data Message contents.
		 */
		public static function errorln($data){
			self::error($data.CR);
		}
		/**
		 * Write an error message to standard errors stream.
		 * @param string $data Message contents.
		 */
		public static function error($data){
			if(!self::is_cli())trigger_error('Error, PHP was not invoked from command line.');
			fwrite(STDERR,$data);
		}
		/**
		 * Waits the program until the user enters some text followed with a new line.
		 * @return string The text up till RETURN was pressed.
		 */
		public static function readln(){
			if(!self::is_cli())trigger_error('Error, PHP was not invoked from command line.');
			return trim(fgets(STDIN)); // TODO: The use of trim() sound dubious.
		}
		/**
		 * Returns whether PHP was invoked from CLI or not.
		 * @return boolean True if script executed from CLI, false otherwise.
		 */
		public static function is_cli(){
			return isset($argc);
		}
		/**
		 * Returns whether PHP was invoked from CGI or not.
		 * @see http://www.php.net/manual/en/features.commandline.php#105043
		 * @return boolean True if script executed from CGI, false otherwise.
		 */
		public static function is_cgi(){
			return substr(PHP_SAPI, 0, 3) == 'cgi' ? true : false;
		}
		/**
		 * Executes a program and waits for it to finish, taking pipes into account.
		 * @param string $cmd Command line to execute, including any arguments.
		 * @param string $input Initial data for standard input.
		 * @param boolean $log Whether to log execution failures or not (defaults to true).
		 * @param integer $timeout Execution timeout in seconds.
		 * @param string $callback Function polled after each buffer read and before each buffer write,
		 *                         which you might want to use to do conditional writes to $cmd's STDIN.
		 * @return array Array of:
		 * <br/>stdout - (string) The program's STDOUT data.
		 * <br/>stderr - (string) The program's STDERR data.
		 * <br/>return - (integer) The program's exit status code.
		 * <br/>taken - (float) Time taken for command to run (in seconds).
		 */
		public static function execute($cmd,$stdin=null,$log=true,$timeout=0.002,$callback=null){
			// This hack fixes a legacy issue in popen not handling escaped command filenames on Windows.
			// Basically, if we're on windows and the first command part is double quoted, we CD into the
			// directory and execute the command from there.
			// Example: '"C:\a test\b.exe" -h'  ->  'cd "C:\a test\" && b.exe -h'
			if(self::os_type()=='windows')
				if(is_string($ok=preg_replace(
						'/^(\s*)"([^"]*\\\\)(.*?)"(.*)/s', // pattern
						'$1cd "$2" && "$3" $4',            // replacement
						$cmd ))) $cmd=$ok;                 // success!
			
			// create new process with STDIO pipes
			$proc=proc_open($cmd,array(
				0=>array('pipe','r'), // STDIN
				1=>array('pipe','w'), // STDOUT
				2=>array('pipe','w')  // STDERR
			),$pipes);
			
			// initialize some variables
			$write  = array($pipes[0]);
			$read   = array($pipes[1], $pipes[2]);
			$except = null; $stdout = ''; $stderr = '';
			$taken  = microtime(true); // for profiler

			// write initial stdin (if any)
			if($stdin!==null)
				fwrite($write[0], $stdin);
				
//function stcon($stream,$pipes){ static $names=array('STDIN','STDOUT','STDERR'); return $names[array_search($stream,$pipes)]; }
				
//aslog('init',$pipes);
			// wait for stream updates
			while(($r = stream_select($read, $write, $except, null, round($timeout*1000000)))>1){
//aslog('  while start',$pipes,$read,$write);
				// handle OUTPUT
				//die_r($read);
//aslog('  foreach start',$pipes,$r);
				foreach($read as $stream){
					// read stream into buffer
					$buffer=''; $chunk=''; $len=8000;
//aslog('    while read start',$pipes,$r,$stream);
					do {
//aslog('      while read iter start',$pipes,$r,$stream,'c=0 b='.strlen($buffer),stream_get_meta_data($stream),(int)feof($stream));
						$chunk=stream_get_contents($stream);
						$buffer.=$chunk;
//aslog('      while read iter end',$pipes,$r,$stream,'c='.strlen($chunk).' b='.strlen($buffer),stream_get_meta_data($stream));
					}while(!feof($stream));
//aslog('    while read end',$pipes,$r,$stream);
					// put buffer into right STDx
					if($stream===$pipes[1])$stdout.=$buffer;
					if($stream===$pipes[2])$stderr.=$buffer;
				}
//aslog('  foreach end',$pipes,$r);
				// handle INPUT
				/*if(isset($write[0])){
					// no input to send, call callback to get more data (if any)
					if($stdin!='' && $callback)
						$stdin=$callback($stdout,$stderr);
					// convert string to buffer
					if(is_string($stdin) && $stdin!='')
						$stdin=str_split($stdin, 8000);
					// buffer not empty, send next chunk from buffer
					if(is_array($stdin)){
						fwrite($write[0], array_shift($stdin));
						if(!count($stdin))$stdin='';
					}
				}*/
//aslog('  while end',$pipes,$read,$write);
			}
//aslog('fini',$pipes);

			// close used resources
			//foreach($pipes as $pipe)fclose($pipe);
			$return=proc_close($proc);

			// calculate time taken
			$taken = microtime(true)-$taken;

			// log if execution failed or debugging in verbose, log execution
			if(CFG::get('DEBUG_VERBOSE'))
				xlog('Executed shell command "'.$cmd.'" in '.number_format($taken,4).' seconds.',$stdout,$stderr,$return);
			elseif($return!=0 && $log)
				xlog('Error: Program execution returned failure.',$stdout,$stderr,$return);

			// return result
			return array( 'stdout'=>$stdout, 'stderr'=>$stderr, 'return'=>$return, 'taken'=>$taken );
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
			// I give up....what the heck are you running on?!
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
					// TODO This needs testing.
					$res=self::execute('system_profiler SPHardwareDataType | grep Processor\ Name');
					$procid=trim(strtolower($res['stdout']));
					if($procid==''){ // for older PPC-based models
						$res=self::execute('system_profiler SPHardwareDataType | grep CPU\ Type');
						$procid=trim(strtolower($res['stdout']));
					}
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
				case 'windows': // @todo: This doesn't work everywhere.
					static $cached_win_bits=null;
					if(!$cached_win_bits){
						$data=base64_decode('
							TVqQAAMAAAAEAAAA//8AALgAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAA4AAAAA4fug4AtAnNIbgBTM0hVGhpcyBwcm9ncmFtIGNhbm5vdCBiZSBydW4gaW4gRE9TIG1v
							ZGUuDQ0KJAAAAAAAAABPOJSqC1n6+QtZ+vkLWfr5LJ+B+Q9Z+vksn5T5Cln6+Syfh/kKWfr5LJ+X
							+R9Z+vnIVqf5CFn6+QtZ+/k0Wfr5LJ+I+QlZ+vksn4L5Cln6+VJpY2gLWfr5AAAAAAAAAABQRQAA
							TAEDALR130oAAAAAAAAAAOAAAwELAQgAABAAAAAQAAAAUAAAYGsAAABgAAAAcAAAAABAAAAQAAAA
							AgAABAAAAAAAAAAEAAAAAAAAAACAAAAAEAAAAAAAAAMAAAAAABAAABAAAAAAEAAAEAAAAAAAABAA
							AAAAAAAAAAAAALBxAAAkAQAAAHAAALABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOhsAABIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAFVQWDAAAAAAAFAAAAAQAAAAAAAAAAQAAAAAAAAAAAAAAAAAAIAAAOBV
							UFgxAAAAAAAQAAAAYAAAAA4AAAAEAAAAAAAAAAAAAAAAAABAAADgLnJzcmMAAAAAEAAAAHAAAAAE
							AAAAEgAAAAAAAAAAAAAAAAAAQAAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							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
							ABAAAFBUagRTV//VjYf/AQAAgCB/gGAof1hQVFBTV//VWGGNRCSAagA5xHX6g+yA6SWp//9IAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							MEAAACJAAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAE
							AAAAAAABABgAAAAYAACAAAAAAAAAAAAEAAAAAAABAAEAAAAwAACAAAAAAAAAAAAEAAAAAAABAAkE
							AABIAAAAXHAAAFIBAADkBAAAAAAAAFhAAAA8YXNzZW1ibHkgeG1sbnM9InVybjpzY2hlbWFzLW1p
							Y3Jvc29mdC1jb206YXNtLnYxIiBtYW5pZmVzdFZlcnNpb249IjEuMCI+DQogIDxkZXBlbmRlbmN5
							Pg0KICAgIDxkZXBlbmRlbnRBc3NlbWJseT4NCiAgICAgIDxhc3NlbWJseUlkZW50aXR5IHR5cGU9
							IndpbjMyIiBuYW1lPSJNaWNyb3NvZnQuVkM4MC5DUlQiIHZlcnNpb249IjguMC41MDYwOC4wIiBw
							cm9jZXNzb3JBcmNoaXRlY3R1cmU9Ing4NiIgcHVibGljS2V5VG9rZW49IjFmYzhiM2I5YTFlMThl
							M2IiPjwvYXNzZW1ibHlJZGVudGl0eT4NCiAgICA8L2RlcGVuZGVudEFzc2VtYmx5Pg0KICA8L2Rl
							cGVuZGVuY3k+DQo8L2Fzc2VtYmx5PlBBAAAAAAAAAAAAAAAALHIAAAByAAAAAAAAAAAAAAAAAAA5
							cgAAHHIAAAAAAAAAAAAAAAAAAEVyAAAkcgAAAAAAAAAAAAAAAAAAAAAAAAAAAABQcgAAXnIAAG5y
							AAB+cgAAjHIAAJpyAAAAAAAAqHIAAAAAAADKcgAAAAAAAEtFUk5FTDMyLkRMTABNU1ZDUDgwLmRs
							bABNU1ZDUjgwLmRsbAAATG9hZExpYnJhcnlBAABHZXRQcm9jQWRkcmVzcwAAVmlydHVhbFByb3Rl
							Y3QAAFZpcnR1YWxBbGxvYwAAVmlydHVhbEZyZWUAAABFeGl0UHJvY2VzcwAAAD91bmNhdWdodF9l
							eGNlcHRpb25Ac3RkQEBZQV9OWFoAAABleGl0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
							AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==');
						$file=System::temporary(System::TMP_FILE,sys_get_temp_dir(),'','.exe',false);
						file_put_contents($file,$data);
						$res=self::execute($file,'',CFG::get('DEBUG_VERBOSE'));
						$cached_win_bits=(int)$res['stdout'];
						if($res['return']!=0 || !$cached_win_bits)
							xlog('Warning: Windows Bitness Check executable failed.',$file);
					}
					return $cached_win_bits;
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
			if($root=='')$root=sys_get_temp_dir();
			if(substr($root,-1,1)!=DIRECTORY_SEPARATOR)$root.=DIRECTORY_SEPARATOR;
			if($type==self::TMP_FILE){
				while(!($file=@fopen($name=$root.$prefix.round(microtime(true)*10000).'-'.mt_rand().$suffix,'xb')));
				@fclose($file);
				if($autoclean)$GLOBALS['K2F-TFD']['f'][]=$name;
				return $name;
			}elseif($type==self::TMP_DIR){
				while(!@mkdir($name=$root.$prefix.round(microtime(true)*10000).'-'.mt_rand().$suffix,true));
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