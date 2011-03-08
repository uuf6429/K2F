<?php

	/**
	 * This is a lean version of K2F framework containing classes required by wkhtmltox to function.
	 * @copyright 2010-2011 Covac Software
	 * @author Christian Sciberras
	 * @version 25/02/2011
	 */

	define('K2F','');

	function uses(){}
	function xlog(){}
	interface Debugable {}

	/**
	 * File: core/system.php
	 */

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
			fwrite($pipes[0],$stdin);					fclose($pipes[0]);
			$stdout=stream_get_contents($pipes[1]); 	fclose($pipes[1]);
			$stderr=stream_get_contents($pipes[2]);		fclose($pipes[2]);
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

	/**
	 * File: core/mime.php
	 */

	uses('core/debug.php');

	/**
	 * A class for handling files types, magic numbers and mime types.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 2/03/2010
	 */
	class MimeTypes implements Debugable {
		/**
		 * Default mime type returned when deiscovery fails.
		 * @var string Default mime type.
		 */
		public static $mime_default='binary/octet-stream';
		/**
		 * An array for discovering mime types.
		 * @var array Array key is the matcher while the value is the MIME type.
		 */
		public static $mime_matches=array(
			"/^.*\.ez$/"		=>		"application/andrew-inset",
			"/^.*\.ai$/"		=>		"application/illustrator",
			"/^.*\.nb$/"		=>		"application/mathematica",
			"/^.*\.bin$/"		=>		"application/octet-stream",
			"/^.*\.oda$/"		=>		"application/oda",
			"/^.*\.pdf$/"		=>		"application/pdf",
			"/^.*\.xspf$/"		=>		"application/xspf+xml",
			"/^.*\.pla$/"		=>		"audio/x-iriver-pla",
			"/^.*\.pgp$/"		=>		"application/pgp-encrypted",
			"/^.*\.gpg$/"		=>		"application/pgp-encrypted",
			"/^.*\.asc$/"		=>		"application/pgp-encrypted",
			"/^.*\.skr$/"		=>		"application/pgp-keys",
			"/^.*\.pkr$/"		=>		"application/pgp-keys",
			"/^.*\.asc$/"		=>		"application/pgp-keys",
			"/^.*\.p7s$/"		=>		"application/pkcs7-signature",
			"/^.*\.p10$/"		=>		"application/pkcs10",
			"/^.*\.ps$/"		=>		"application/postscript",
			"/^.*\.rtf$/"		=>		"application/rtf",
			"/^.*\.siv$/"		=>		"application/sieve",
			"/^.*\.smil$/"		=>		"application/smil",
			"/^.*\.smi$/"		=>		"application/smil",
			"/^.*\.sml$/"		=>		"application/smil",
			"/^.*\.kino$/"		=>		"application/smil",
			"/^.*\.sit$/"		=>		"application/stuffit",
			"/^.*\.ged$/"		=>		"application/x-gedcom",
			"/^.*\.gedcom$/"	=>		"application/x-gedcom",
			"/^.*\.flv$/"		=>		"application/x-flash-video",
			"/^.*\.sgf$/"		=>		"application/x-go-sgf",
			"/^.*\.xlf$/"		=>		"application/x-xliff",
			"/^.*\.xliff$/"		=>		"application/x-xliff",
			"/^.*\.cdr$/"		=>		"application/vnd.corel-draw",
			"/^.*\.hpgl$/"		=>		"application/vnd.hp-hpgl",
			"/^.*\.pcl$/"		=>		"application/vnd.hp-pcl",
			"/^.*\.123$/"		=>		"application/vnd.lotus-1-2-3",
			"/^.*\.wk1$/"		=>		"application/vnd.lotus-1-2-3",
			"/^.*\.wk3$/"		=>		"application/vnd.lotus-1-2-3",
			"/^.*\.wk4$/"		=>		"application/vnd.lotus-1-2-3",
			"/^.*\.wks$/"		=>		"application/vnd.lotus-1-2-3",
			"/^.*\.xul$/"		=>		"application/vnd.mozilla.xul+xml",
			"/^.*\.mdb$/"		=>		"application/vnd.ms-access",
			"/^.*\.xls$/"		=>		"application/vnd.ms-excel",
			"/^.*\.xlc$/"		=>		"application/vnd.ms-excel",
			"/^.*\.xll$/"		=>		"application/vnd.ms-excel",
			"/^.*\.xlm$/"		=>		"application/vnd.ms-excel",
			"/^.*\.xlw$/"		=>		"application/vnd.ms-excel",
			"/^.*\.xla$/"		=>		"application/vnd.ms-excel",
			"/^.*\.xlt$/"		=>		"application/vnd.ms-excel",
			"/^.*\.xld$/"		=>		"application/vnd.ms-excel",
			"/^.*\.ppz$/"		=>		"application/vnd.ms-powerpoint",
			"/^.*\.ppt$/"		=>		"application/vnd.ms-powerpoint",
			"/^.*\.pps$/"		=>		"application/vnd.ms-powerpoint",
			"/^.*\.pot$/"		=>		"application/vnd.ms-powerpoint",
			"/^.*\.xps$/"		=>		"application/vnd.ms-xpsdocument",
			"/^.*\.doc$/"		=>		"application/msword",
			"/^.*\.tnef$/"		=>		"application/vnd.ms-tnef",
			"/^.*\.tnf$/"		=>		"application/vnd.ms-tnef",
			"/^winmail\.dat$/"	=>		"application/vnd.ms-tnef",
			"/^.*\.sdc$/"		=>		"application/vnd.stardivision.calc",
			"/^.*\.sds$/"		=>		"application/vnd.stardivision.chart",
			"/^.*\.sda$/"		=>		"application/vnd.stardivision.draw",
			"/^.*\.sdd$/"		=>		"application/vnd.stardivision.impress",
			"/^.*\.sdp$/"		=>		"application/vnd.stardivision.impress",
			"/^.*\.smd$/"		=>		"application/vnd.stardivision.mail",
			"/^.*\.smf$/"		=>		"application/vnd.stardivision.math",
			"/^.*\.sdw$/"		=>		"application/vnd.stardivision.writer",
			"/^.*\.vor$/"		=>		"application/vnd.stardivision.writer",
			"/^.*\.sgl$/"		=>		"application/vnd.stardivision.writer",
			"/^.*\.sxc$/"		=>		"application/vnd.sun.xml.calc",
			"/^.*\.stc$/"		=>		"application/vnd.sun.xml.calc.template",
			"/^.*\.sxd$/"		=>		"application/vnd.sun.xml.draw",
			"/^.*\.std$/"		=>		"application/vnd.sun.xml.draw.template",
			"/^.*\.sxi$/"		=>		"application/vnd.sun.xml.impress",
			"/^.*\.sti$/"		=>		"application/vnd.sun.xml.impress.template",
			"/^.*\.sxm$/"		=>		"application/vnd.sun.xml.math",
			"/^.*\.sxw$/"		=>		"application/vnd.sun.xml.writer",
			"/^.*\.sxg$/"		=>		"application/vnd.sun.xml.writer.global",
			"/^.*\.stw$/"		=>		"application/vnd.sun.xml.writer.template",
			"/^.*\.odt$/"		=>		"application/vnd.oasis.opendocument.text",
			"/^.*\.ott$/"		=>		"application/vnd.oasis.opendocument.text-template",
			"/^.*\.oth$/"		=>		"application/vnd.oasis.opendocument.text-web",
			"/^.*\.odm$/"		=>		"application/vnd.oasis.opendocument.text-master",
			"/^.*\.odg$/"		=>		"application/vnd.oasis.opendocument.graphics",
			"/^.*\.otg$/"		=>		"application/vnd.oasis.opendocument.graphics-template",
			"/^.*\.odp$/"		=>		"application/vnd.oasis.opendocument.presentation",
			"/^.*\.otp$/"		=>		"application/vnd.oasis.opendocument.presentation-template",
			"/^.*\.ods$/"		=>		"application/vnd.oasis.opendocument.spreadsheet",
			"/^.*\.ots$/"		=>		"application/vnd.oasis.opendocument.spreadsheet-template",
			"/^.*\.odc$/"		=>		"application/vnd.oasis.opendocument.chart",
			"/^.*\.odf$/"		=>		"application/vnd.oasis.opendocument.formula",
			"/^.*\.odb$/"		=>		"application/vnd.oasis.opendocument.database",
			"/^.*\.odi$/"		=>		"application/vnd.oasis.opendocument.image",
			"/^.*\.sis$/"		=>		"application/vnd.symbian.install",
			"/^.*\.sisx$/"		=>		"x-epoc/x-sisx-app",
			"/^.*\.wp$/"		=>		"application/vnd.wordperfect",
			"/^.*\.wp4$/"		=>		"application/vnd.wordperfect",
			"/^.*\.wp5$/"		=>		"application/vnd.wordperfect",
			"/^.*\.wp6$/"		=>		"application/vnd.wordperfect",
			"/^.*\.wpd$/"		=>		"application/vnd.wordperfect",
			"/^.*\.wpp$/"		=>		"application/vnd.wordperfect",
			"/^.*\.xbel$/"		=>		"application/x-xbel",
			"/^.*\.7z$/"		=>		"application/x-7z-compressed",
			"/^.*\.abw$/"		=>		"application/x-abiword",
			"/^.*\.abw\.CRASHED$/"=>	"application/x-abiword",
			"/^.*\.abw\.gz$/"	=>		"application/x-abiword",
			"/^.*\.zabw$/"		=>		"application/x-abiword",
			"/^.*\.cue$/"		=>		"application/x-cue",
			"/^.*\.sam$/"		=>		"application/x-amipro",
			"/^.*\.as$/"		=>		"application/x-applix-spreadsheet",
			"/^.*\.aw$/"		=>		"application/x-applix-word",
			"/^.*\.a$/"			=>		"application/x-archive",
			"/^.*\.arj$/"		=>		"application/x-arj",
			"/^.*\.asp$/"		=>		"application/x-asp",
			"/^.*\.bcpio$/"		=>		"application/x-bcpio",
			"/^.*\.torrent$/"	=>		"application/x-bittorrent",
			"/^.*\.blender$/"	=>		"application/x-blender",
			"/^.*\.blend$/"		=>		"application/x-blender",
			"/^.*\.BLEND$/"		=>		"application/x-blender",
			"/^.*\.dvi\.bz2$/"	=>		"application/x-bzdvi",
			"/^.*\.bz$/"		=>		"application/x-bzip",
			"/^.*\.bz2$/"		=>		"application/x-bzip",
			"/^.*\.tar\.bz$/"	=>		"application/x-bzip-compressed-tar",
			"/^.*\.tar\.bz2$/"	=>		"application/x-bzip-compressed-tar",
			"/^.*\.tbz$/"		=>		"application/x-bzip-compressed-tar",
			"/^.*\.tbz2$/"		=>		"application/x-bzip-compressed-tar",
			"/^.*\.pdf\.bz2$/"	=>		"application/x-bzpdf",
			"/^.*\.ps\.bz2$/"	=>		"application/x-bzpostscript",
			"/^.*\.cbr$/"		=>		"application/x-cbr",
			"/^.*\.cbz$/"		=>		"application/x-cbz",
			"/^.*\.iso$/"		=>		"application/x-cd-image",
			"/^.*\.iso9660$/"	=>		"application/x-cd-image",
			"/^.*\.cgi$/"		=>		"application/x-cgi",
			"/^.*\.pgn$/"		=>		"application/x-chess-pgn",
			"/^.*\.chm$/"		=>		"application/x-chm",
			"/^.*\.Z$/"			=>		"application/x-compress",
			"/^.*\.tar\.gz$/"	=>		"application/x-compressed-tar",
			"/^.*\.tgz$/"		=>		"application/x-compressed-tar",
			"/^core$/"			=>		"application/x-core",
			"/^.*\.cpio$/"		=>		"application/x-cpio",
			"/^.*\.cpio\.gz$/"	=>		"application/x-cpio-compressed",
			"/^.*\.csh$/"		=>		"application/x-csh",
			"/^.*\.dbf$/"		=>		"application/x-dbf",
			"/^.*\.es$/"		=>		"application/ecmascript",
			"/^.*\.dc$/"		=>		"application/x-dc-rom",
			"/^.*\.nds$/"		=>		"application/x-nintendo-ds-rom",
			"/^.*\.deb$/"		=>		"application/x-deb",
			"/^.*\.ui$/"		=>		"application/x-designer",
			"/^.*\.desktop$/"	=>		"application/x-desktop",
			"/^.*\.kdelnk$/"	=>		"application/x-desktop",
			"/^.*\.dia$/"		=>		"application/x-dia-diagram",
			"/^.*\.dvi$/"		=>		"application/x-dvi",
			"/^.*\.etheme$/"	=>		"application/x-e-theme",
			"/^.*\.egon$/"		=>		"application/x-egon",
			"/^.*\.exe$/"		=>		"application/x-executable",
			"/^.*\.pfa$/"		=>		"application/x-font-type1",
			"/^.*\.pfb$/"		=>		"application/x-font-type1",
			"/^.*\.gsf$/"		=>		"application/x-font-type1",
			"/^.*\.afm$/"		=>		"application/x-font-afm",
			"/^.*\.bdf$/"		=>		"application/x-font-bdf",
			"/^.*\.psf$/"		=>		"application/x-font-linux-psf",
			"/^.*\.psf\.gz$/"	=>		"application/x-gz-font-linux-psf",
			"/^.*\.pcf$/"		=>		"application/x-font-pcf",
			"/^.*\.pcf\.Z$/"	=>		"application/x-font-pcf",
			"/^.*\.pcf\.gz$/"	=>		"application/x-font-pcf",
			"/^.*\.spd$/"		=>		"application/x-font-speedo",
			"/^.*\.ttf$/"		=>		"application/x-font-ttf",
			"/^.*\.ttc$/"		=>		"application/x-font-ttf",
			"/^.*\.gb$/"		=>		"application/x-gameboy-rom",
			"/^.*\.gba$/"		=>		"application/x-gba-rom",
			"/^.*\.gen$/"		=>		"application/x-genesis-rom",
			"/^.*\.md$/"		=>		"application/x-genesis-rom",
			"/^.*\.gmo$/"		=>		"application/x-gettext-translation",
			"/^.*\.mo$/"		=>		"application/x-gettext-translation",
			"/^.*\.glade$/"		=>		"application/x-glade",
			"/^.*\.gnucash$/"	=>		"application/x-gnucash",
			"/^.*\.gnc$/"		=>		"application/x-gnucash",
			"/^.*\.xac$/"		=>		"application/x-gnucash",
			"/^.*\.gnumeric$/"	=>		"application/x-gnumeric",
			"/^.*\.gp$/"		=>		"application/x-gnuplot",
			"/^.*\.gplt$/"		=>		"application/x-gnuplot",
			"/^.*\.gnuplot$/"	=>		"application/x-gnuplot",
			"/^.*\.gra$/"		=>		"application/x-graphite",
			"/^.*\.dvi\.gz$/"	=>		"application/x-gzdvi",
			"/^.*\.gz$/"		=>		"application/x-gzip",
			"/^.*\.pdf\.gz$/"	=>		"application/x-gzpdf",
			"/^.*\.ps\.gz$/"	=>		"application/x-gzpostscript",
			"/^.*\.hdf$/"		=>		"application/x-hdf",
			"/^.*\.jar$/"		=>		"application/x-java-archive",
			"/^.*\.class$/"		=>		"application/x-java",
			"/^.*\.jnlp$/"		=>		"application/x-java-jnlp-file",
			"/^.*\.js$/"		=>		"application/javascript",
			"/^.*\.jpr$/"		=>		"application/x-jbuilder-project",
			"/^.*\.jpx$/"		=>		"application/x-jbuilder-project",
			"/^.*\.karbon$/"	=>		"application/x-karbon",
			"/^.*\.chrt$/"		=>		"application/x-kchart",
			"/^.*\.kfo$/"		=>		"application/x-kformula",
			"/^.*\.kil$/"		=>		"application/x-killustrator",
			"/^.*\.flw$/"		=>		"application/x-kivio",
			"/^.*\.kon$/"		=>		"application/x-kontour",
			"/^.*\.kpm$/"		=>		"application/x-kpovmodeler",
			"/^.*\.kpr$/"		=>		"application/x-kpresenter",
			"/^.*\.kpt$/"		=>		"application/x-kpresenter",
			"/^.*\.kra$/"		=>		"application/x-krita",
			"/^.*\.ksp$/"		=>		"application/x-kspread",
			"/^.*\.kud$/"		=>		"application/x-kugar",
			"/^.*\.kwd$/"		=>		"application/x-kword",
			"/^.*\.kwt$/"		=>		"application/x-kword",
			"/^.*\.lha$/"		=>		"application/x-lha",
			"/^.*\.lzh$/"		=>		"application/x-lha",
			"/^.*\.lhz$/"		=>		"application/x-lhz",
			"/^.*\.ts$/"		=>		"application/x-linguist",
			"/^.*\.lyx$/"		=>		"application/x-lyx",
			"/^.*\.lzo$/"		=>		"application/x-lzop",
			"/^.*\.mgp$/"		=>		"application/x-magicpoint",
			"/^.*\.mkv$/"		=>		"video/x-matroska",
			"/^.*\.mka$/"		=>		"audio/x-matroska",
			"/^.*\.ocl$/"		=>		"text/x-ocl",
			"/^.*\.mif$/"		=>		"application/x-mif",
			"/^.*\.exe$/"		=>		"application/x-ms-dos-executable",
			"/^.*\.wri$/"		=>		"application/x-mswrite",
			"/^.*\.msx$/"		=>		"application/x-msx-rom",
			"/^.*\.m4$/"		=>		"application/x-m4",
			"/^.*\.n64$/"		=>		"application/x-n64-rom",
			"/^.*\.nes$/"		=>		"application/x-nes-rom",
			"/^.*\.cdf$/"		=>		"application/x-netcdf",
			"/^.*\.nc$/"		=>		"application/x-netcdf",
			"/^.*\.o$/"			=>		"application/x-object",
			"/^.*\.ogg$/"		=>		"application/ogg",
			"/^.*\.ogx$/"		=>		"application/ogg",
			"/^.*\.oga$/"		=>		"audio/ogg",
			"/^.*\.ogv$/"		=>		"video/ogg",
			"/^.*\.ogg$/"		=>		"audio/x-vorbis+ogg",
			"/^.*\.ogg$/"		=>		"audio/x-flac+ogg",
			"/^.*\.ogg$/"		=>		"audio/x-speex+ogg",
			"/^.*\.spx$/"		=>		"audio/x-speex",
			"/^.*\.ogg$/"		=>		"video/x-theora+ogg",
			"/^.*\.ogm$/"		=>		"video/x-ogm+ogg",
			"/^.*\.oleo$/"		=>		"application/x-oleo",
			"/^.*\.pak$/"		=>		"application/x-pak",
			"/^.*\.pdb$/"		=>		"application/x-palm-database",
			"/^.*\.prc$/"		=>		"application/x-palm-database",
			"/^.*\.PAR2$/"		=>		"application/x-par2",
			"/^.*\.par2$/"		=>		"application/x-par2",
			"/^.*\.pl$/"		=>		"application/x-perl",
			"/^.*\.pm$/"		=>		"application/x-perl",
			"/^.*\.al$/"		=>		"application/x-perl",
			"/^.*\.perl$/"		=>		"application/x-perl",
			"/^.*\.php$/"		=>		"application/x-php",
			"/^.*\.php3$/"		=>		"application/x-php",
			"/^.*\.php4$/"		=>		"application/x-php",
			"/^.*\.p12$/"		=>		"application/x-pkcs12",
			"/^.*\.pfx$/"		=>		"application/x-pkcs12",
			"/^.*\.pln$/"		=>		"application/x-planperfect",
			"/^gmon\.out$/"		=>		"application/x-profile",
			"/^.*\.pw$/"		=>		"application/x-pw",
			"/^.*\.pyc$/"		=>		"application/x-python-bytecode",
			"/^.*\.pyo$/"		=>		"application/x-python-bytecode",
			"/^.*\.wb1$/"		=>		"application/x-quattropro",
			"/^.*\.wb2$/"		=>		"application/x-quattropro",
			"/^.*\.wb3$/"		=>		"application/x-quattropro",
			"/^.*\.qtl$/"		=>		"application/x-quicktime-media-link",
			"/^.*\.qif$/"		=>		"application/x-qw",
			"/^.*\.rar$/"		=>		"application/x-rar",
			"/^.*\.dar$/"		=>		"application/x-dar",
			"/^.*\.rej$/"		=>		"application/x-reject",
			"/^.*\.rpm$/"		=>		"application/x-rpm",
			"/^.*\.rb$/"		=>		"application/x-ruby",
			"/^.*\.mab$/"		=>		"application/x-markaby",
			"/^.*\.shar$/"		=>		"application/x-shar",
			"/^.*\.la$/"		=>		"application/x-shared-library-la",
			"/^.*\.so$/"		=>		"application/x-sharedlib",
			"/^.*\.sh$/"		=>		"application/x-shellscript",
			"/^.*\.swf$/"		=>		"application/x-shockwave-flash",
			"/^.*\.spl$/"		=>		"application/x-shockwave-flash",
			"/^.*\.shn$/"		=>		"application/x-shorten",
			"/^.*\.siag$/"		=>		"application/x-siag",
			"/^.*\.sms$/"		=>		"application/x-sms-rom",
			"/^.*\.gg$/"		=>		"application/x-sms-rom",
			"/^.*\.smc$/"		=>		"application/x-snes-rom",
			"/^.*\.srt$/"		=>		"application/x-subrip",
			"/^.*\.smi$/"		=>		"application/x-sami",
			"/^.*\.sami$/"		=>		"application/x-sami",
			"/^.*\.sub$/"		=>		"text/x-microdvd",
			"/^.*\.sub$/"		=>		"text/x-mpsub",
			"/^.*\.ssa$/"		=>		"text/x-ssa",
			"/^.*\.ass$/"		=>		"text/x-ssa",
			"/^.*\.sv4cpio$/"	=>		"application/x-sv4cpio",
			"/^.*\.sv4crc$/"	=>		"application/x-sv4crc",
			"/^.*\.tar$/"		=>		"application/x-tar",
			"/^.*\.gtar$/"		=>		"application/x-tar",
			"/^.*\.tar\.Z$/"	=>		"application/x-tarz",
			"/^.*\.gf$/"		=>		"application/x-tex-gf",
			"/^.*\.pk$/"		=>		"application/x-tex-pk",
			"/^.*\.obj$/"		=>		"application/x-tgif",
			"/^.*\.theme$/"		=>		"application/x-theme",
			"/^.*~$/"			=>		"application/x-trash",
			"/^.*%$/"			=>		"application/x-trash",
			"/^.*\.bak$/"		=>		"application/x-trash",
			"/^.*\.old$/"		=>		"application/x-trash",
			"/^.*\.sik$/"		=>		"application/x-trash",
			"/^.*\.tr$/"		=>		"text/troff",
			"/^.*\.roff$/"		=>		"text/troff",
			"/^.*\.t$/"			=>		"text/troff",
			"/^.*\.man$/"		=>		"application/x-troff-man",
			"/^.*\.tar\.lzo$/"	=>		"application/x-tzo",
			"/^.*\.tzo$/"		=>		"application/x-tzo",
			"/^.*\.ustar$/"		=>		"application/x-ustar",
			"/^.*\.src$/"		=>		"application/x-wais-source",
			"/^.*\.wpg$/"		=>		"application/x-wpg",
			"/^.*\.der$/"		=>		"application/x-x509-ca-cert",
			"/^.*\.cer$/"		=>		"application/x-x509-ca-cert",
			"/^.*\.crt$/"		=>		"application/x-x509-ca-cert",
			"/^.*\.cert$/"		=>		"application/x-x509-ca-cert",
			"/^.*\.pem$/"		=>		"application/x-x509-ca-cert",
			"/^.*\.zoo$/"		=>		"application/x-zoo",
			"/^.*\.xhtml$/"		=>		"application/xhtml+xml",
			"/^.*\.zip$/"		=>		"application/zip",
			"/^.*\.ac3$/"		=>		"audio/ac3",
			"/^.*\.amr$/"		=>		"audio/AMR",
			"/^.*\.awb$/"		=>		"audio/AMR-WB",
			"/^.*\.au$/"		=>		"audio/basic",
			"/^.*\.snd$/"		=>		"audio/basic",
			"/^.*\.sid$/"		=>		"audio/prs.sid",
			"/^.*\.psid$/"		=>		"audio/prs.sid",
			"/^.*\.aiff$/"		=>		"audio/x-aiff",
			"/^.*\.aif$/"		=>		"audio/x-aiff",
			"/^.*\.aifc$/"		=>		"audio/x-aiff",
			"/^.*\.ape$/"		=>		"audio/x-ape",
			"/^.*\.it$/"		=>		"audio/x-it",
			"/^.*\.flac$/"		=>		"audio/x-flac",
			"/^.*\.wv$/"		=>		"audio/x-wavpack",
			"/^.*\.wvp$/"		=>		"audio/x-wavpack",
			"/^.*\.wvc$/"		=>		"audio/x-wavpack-correction",
			"/^.*\.mid$/"		=>		"audio/midi",
			"/^.*\.midi$/"		=>		"audio/midi",
			"/^.*\.kar$/"		=>		"audio/midi",
			"/^.*\.m4a$/"		=>		"audio/mp4",
			"/^.*\.aac$/"		=>		"audio/mp4",
			"/^.*\.mp4$/"		=>		"video/mp4",
			"/^.*\.m4v$/"		=>		"video/mp4",
			"/^.*\.m4b$/"		=>		"audio/x-m4b",
			"/^.*\.3gp$/"		=>		"video/3gpp",
			"/^.*\.3gpp$/"		=>		"video/3gpp",
			"/^.*\.amr$/"		=>		"video/3gpp",
			"/^.*\.mod$/"		=>		"audio/x-mod",
			"/^.*\.ult$/"		=>		"audio/x-mod",
			"/^.*\.uni$/"		=>		"audio/x-mod",
			"/^.*\.m15$/"		=>		"audio/x-mod",
			"/^.*\.mtm$/"		=>		"audio/x-mod",
			"/^.*\.669$/"		=>		"audio/x-mod",
			"/^.*\.mp2$/"		=>		"audio/mp2",
			"/^.*\.mp3$/"		=>		"audio/mpeg",
			"/^.*\.mpga$/"		=>		"audio/mpeg",
			"/^.*\.m3u$/"		=>		"audio/x-mpegurl",
			"/^.*\.vlc$/"		=>		"audio/x-mpegurl",
			"/^.*\.asx$/"		=>		"audio/x-ms-asx",
			"/^.*\.wax$/"		=>		"audio/x-ms-asx",
			"/^.*\.wvx$/"		=>		"audio/x-ms-asx",
			"/^.*\.wmx$/"		=>		"audio/x-ms-asx",
			"/^.*\.psf$/"		=>		"audio/x-psf",
			"/^.*\.minipsf$/"	=>		"audio/x-minipsf",
			"/^.*\.psflib$/"	=>		"audio/x-psflib",
			"/^.*\.wma$/"		=>		"audio/x-ms-wma",
			"/^.*\.mpc$/"		=>		"audio/x-musepack",
			"/^.*\.mpp$/"		=>		"audio/x-musepack",
			"/^.*\.mp[+]$/"		=>		"audio/x-musepack",
			"/^.*\.ra$/"		=>		"audio/vnd.rn-realaudio",
			"/^.*\.rax$/"		=>		"audio/vnd.rn-realaudio",
			"/^.*\.ram$/"		=>		"application/ram",
			"/^.*\.rv$/"		=>		"video/vnd.rn-realvideo",
			"/^.*\.rvx$/"		=>		"video/vnd.rn-realvideo",
			"/^.*\.rm$/"		=>		"application/vnd.rn-realmedia",
			"/^.*\.rmj$/"		=>		"application/vnd.rn-realmedia",
			"/^.*\.rmm$/"		=>		"application/vnd.rn-realmedia",
			"/^.*\.rms$/"		=>		"application/vnd.rn-realmedia",
			"/^.*\.rmx$/"		=>		"application/vnd.rn-realmedia",
			"/^.*\.rmvb$/"		=>		"application/vnd.rn-realmedia",
			"/^.*\.rp$/"		=>		"image/vnd.rn-realpix",
			"/^.*\.rt$/"		=>		"text/vnd.rn-realtext",
			"/^.*\.s3m$/"		=>		"audio/x-s3m",
			"/^.*\.pls$/"		=>		"audio/x-scpls",
			"/^.*\.stm$/"		=>		"audio/x-stm",
			"/^.*\.voc$/"		=>		"audio/x-voc",
			"/^.*\.wav$/"		=>		"audio/x-wav",
			"/^.*\.xi$/"		=>		"audio/x-xi",
			"/^.*\.xm$/"		=>		"audio/x-xm",
			"/^.*\.tta$/"		=>		"audio/x-tta",
			"/^.*\.bmp$/"		=>		"image/bmp",
			"/^.*\.wbmp$/"		=>		"image/vnd.wap.wbmp",
			"/^.*\.cgm$/"		=>		"image/cgm",
			"/^.*\.g3$/"		=>		"image/fax-g3",
			"/^.*\.gif$/"		=>		"image/gif",
			"/^.*\.ief$/"		=>		"image/ief",
			"/^.*\.jpeg$/"		=>		"image/jpeg",
			"/^.*\.jpg$/"		=>		"image/jpeg",
			"/^.*\.jpe$/"		=>		"image/jpeg",
			"/^.*\.jp2$/"		=>		"image/jp2",
			"/^.*\.jpc$/"		=>		"image/jp2",
			"/^.*\.jpx$/"		=>		"image/jp2",
			"/^.*\.j2k$/"		=>		"image/jp2",
			"/^.*\.jpf$/"		=>		"image/jp2",
			"/^.*\.dds$/"		=>		"image/x-dds",
			"/^.*\.pict$/"		=>		"image/x-pict",
			"/^.*\.pict1$/"		=>		"image/x-pict",
			"/^.*\.pict2$/"		=>		"image/x-pict",
			"/^.*\.ufraw$/"		=>		"application/x-ufraw",
			"/^.*\.dng$/"		=>		"image/x-adobe-dng",
			"/^.*\.crw$/"		=>		"image/x-canon-crw",
			"/^.*\.cr2$/"		=>		"image/x-canon-cr2",
			"/^.*\.raf$/"		=>		"image/x-fuji-raf",
			"/^.*\.dcr$/"		=>		"image/x-kodak-dcr",
			"/^.*\.k25$/"		=>		"image/x-kodak-k25",
			"/^.*\.kdc$/"		=>		"image/x-kodak-kdc",
			"/^.*\.mrw$/"		=>		"image/x-minolta-mrw",
			"/^.*\.nef$/"		=>		"image/x-nikon-nef",
			"/^.*\.orf$/"		=>		"image/x-olympus-orf",
			"/^.*\.raw$/"		=>		"image/x-panasonic-raw",
			"/^.*\.pef$/"		=>		"image/x-pentax-pef",
			"/^.*\.x3f$/"		=>		"image/x-sigma-x3f",
			"/^.*\.srf$/"		=>		"image/x-sony-srf",
			"/^.*\.sr2$/"		=>		"image/x-sony-sr2",
			"/^.*\.arw$/"		=>		"image/x-sony-arw",
			"/^.*\.png$/"		=>		"image/png",
			"/^.*\.rle$/"		=>		"image/rle",
			"/^.*\.svg$/"		=>		"image/svg+xml",
			"/^.*\.svgz$/"		=>		"image/svg+xml-compressed",
			"/^.*\.tif$/"		=>		"image/tiff",
			"/^.*\.tiff$/"		=>		"image/tiff",
			"/^.*\.dwg$/"		=>		"image/vnd.dwg",
			"/^.*\.dxf$/"		=>		"image/vnd.dxf",
			"/^.*\.3ds$/"		=>		"image/x-3ds",
			"/^.*\.ag$/"		=>		"image/x-applix-graphics",
			"/^.*\.eps\.bz2$/"	=>		"image/x-bzeps",
			"/^.*\.epsi\.bz2$/"	=>		"image/x-bzeps",
			"/^.*\.epsf\.bz2$/"	=>		"image/x-bzeps",
			"/^.*\.ras$/"		=>		"image/x-cmu-raster",
			"/^.*\.xcf\.gz$/"	=>		"image/x-compressed-xcf",
			"/^.*\.xcf\.bz2$/"	=>		"image/x-compressed-xcf",
			"/^.*\.dcm$/"		=>		"application/dicom",
			"/^.*\.docbook$/"	=>		"application/docbook+xml",
			"/^.*\.djvu$/"		=>		"image/vnd.djvu",
			"/^.*\.djv$/"		=>		"image/vnd.djvu",
			"/^.*\.eps$/"		=>		"image/x-eps",
			"/^.*\.epsi$/"		=>		"image/x-eps",
			"/^.*\.epsf$/"		=>		"image/x-eps",
			"/^.*\.fits$/"		=>		"image/x-fits",
			"/^.*\.eps\.gz$/"	=>		"image/x-gzeps",
			"/^.*\.epsi\.gz$/"	=>		"image/x-gzeps",
			"/^.*\.epsf\.gz$/"	=>		"image/x-gzeps",
			"/^.*\.ico$/"		=>		"image/x-ico",
			"/^.*\.icns$/"		=>		"image/x-icns",
			"/^.*\.iff$/"		=>		"image/x-iff",
			"/^.*\.ilbm$/"		=>		"image/x-ilbm",
			"/^.*\.jng$/"		=>		"image/x-jng",
			"/^.*\.lwo$/"		=>		"image/x-lwo",
			"/^.*\.lwob$/"		=>		"image/x-lwo",
			"/^.*\.lws$/"		=>		"image/x-lws",
			"/^.*\.pntg$/"		=>		"image/x-macpaint",
			"/^.*\.msod$/"		=>		"image/x-msod",
			"/^.*\.pcd$/"		=>		"image/x-photo-cd",
			"/^.*\.pnm$/"		=>		"image/x-portable-anymap",
			"/^.*\.pbm$/"		=>		"image/x-portable-bitmap",
			"/^.*\.pgm$/"		=>		"image/x-portable-graymap",
			"/^.*\.ppm$/"		=>		"image/x-portable-pixmap",
			"/^.*\.psd$/"		=>		"image/x-psd",
			"/^.*\.rgb$/"		=>		"image/x-rgb",
			"/^.*\.sgi$/"		=>		"image/x-sgi",
			"/^.*\.sun$/"		=>		"image/x-sun-raster",
			"/^.*\.icb$/"		=>		"image/x-tga",
			"/^.*\.tga$/"		=>		"image/x-tga",
			"/^.*\.tpic$/"		=>		"image/x-tga",
			"/^.*\.vda$/"		=>		"image/x-tga",
			"/^.*\.vst$/"		=>		"image/x-tga",
			"/^.*\.cur$/"		=>		"image/x-win-bitmap",
			"/^.*\.emf$/"		=>		"image/x-emf",
			"/^.*\.wmf$/"		=>		"image/x-wmf",
			"/^.*\.xbm$/"		=>		"image/x-xbitmap",
			"/^.*\.xcf$/"		=>		"image/x-xcf",
			"/^.*\.fig$/"		=>		"image/x-xfig",
			"/^.*\.xpm$/"		=>		"image/x-xpixmap",
			"/^.*\.xwd$/"		=>		"image/x-xwindowdump",
			"/^RMAIL$/"			=>		"message/x-gnu-rmail",
			"/^.*\.wrl$/"		=>		"model/vrml",
			"/^.*\.vcs$/"		=>		"text/calendar",
			"/^.*\.ics$/"		=>		"text/calendar",
			"/^.*\.css$/"		=>		"text/css",
			"/^.*\.CSSL$/"		=>		"text/css",
			"/^.*\.vcf$/"		=>		"text/directory",
			"/^.*\.vct$/"		=>		"text/directory",
			"/^.*\.gcrd$/"		=>		"text/directory",
			"/^.*\.t2t$/"		=>		"text/x-txt2tags",
			"/^.*\.vhd$/"		=>		"text/x-vhdl",
			"/^.*\.vhdl$/"		=>		"text/x-vhdl",
			"/^.*\.mml$/"		=>		"text/mathml",
			"/^.*\.txt$/"		=>		"text/plain",
			"/^.*\.asc$/"		=>		"text/plain",
			"/^.*\.rdf$/"		=>		"text/rdf",
			"/^.*\.rdfs$/"		=>		"text/rdf",
			"/^.*\.owl$/"		=>		"text/rdf",
			"/^.*\.rtx$/"		=>		"text/richtext",
			"/^.*\.rss$/"		=>		"application/rss+xml",
			"/^.*\.atom$/"		=>		"application/atom+xml",
			"/^.*\.opml$/"		=>		"text/x-opml+xml",
			"/^.*\.sgml$/"		=>		"text/sgml",
			"/^.*\.sgm$/"		=>		"text/sgml",
			"/^.*\.sylk$/"		=>		"text/spreadsheet",
			"/^.*\.slk$/"		=>		"text/spreadsheet",
			"/^.*\.tsv$/"		=>		"text/tab-separated-values",
			"/^.*\.jad$/"		=>		"text/vnd.sun.j2me.app-descriptor",
			"/^.*\.wml$/"		=>		"text/vnd.wap.wml",
			"/^.*\.wmls$/"		=>		"text/vnd.wap.wmlscript",
			"/^.*\.ace$/"		=>		"application/x-ace",
			"/^.*\.adb$/"		=>		"text/x-adasrc",
			"/^.*\.ads$/"		=>		"text/x-adasrc",
			"/^AUTHORS$/"		=>		"text/x-authors",
			"/^.*\.bib$/"		=>		"text/x-bibtex",
			"/^.*\.hh$/"		=>		"text/x-c++hdr",
			"/^.*\.hp$/"		=>		"text/x-c++hdr",
			"/^.*\.hpp$/"		=>		"text/x-c++hdr",
			"/^.*\.h[+][+]$/"	=>		"text/x-c++hdr",
			"/^.*\.hxx$/"		=>		"text/x-c++hdr",
			"/^.*\.cpp$/"		=>		"text/x-c++src",
			"/^.*\.cxx$/"		=>		"text/x-c++src",
			"/^.*\.cc$/"		=>		"text/x-c++src",
			"/^.*\.C$/"			=>		"text/x-c++src",
			"/^.*\.c[+][+]$/"	=>		"text/x-c++src",
			"/^ChangeLog$/"		=>		"text/x-changelog",
			"/^.*\.h$/"			=>		"text/x-chdr",
			"/^.*\.csv$/"		=>		"text/csv",
			"/^COPYING$/"		=>		"text/x-copying",
			"/^CREDITS$/"		=>		"text/x-credits",
			"/^.*\.c$/"			=>		"text/x-csrc",
			"/^.*\.cs$/"		=>		"text/x-csharp",
			"/^.*\.vala$/"		=>		"text/x-vala",
			"/^.*\.dcl$/"		=>		"text/x-dcl",
			"/^.*\.dsl$/"		=>		"text/x-dsl",
			"/^.*\.d$/"			=>		"text/x-dsrc",
			"/^.*\.dtd$/"		=>		"text/x-dtd",
			"/^.*\.el$/"		=>		"text/x-emacs-lisp",
			"/^.*\.erl$/"		=>		"text/x-erlang",
			"/^.*\.[fF]$/"		=>		"text/x-fortran",
			"/^.*\.[fF]9[05]$/"	=>		"text/x-fortran",
			"/^.*\.for$/"		=>		"text/x-fortran",
			"/^.*\.po$/"		=>		"text/x-gettext-translation",
			"/^.*\.pot$/"		=>		"text/x-gettext-translation-template",
			"/^.*\.html$/"		=>		"text/html",
			"/^.*\.htm$/"		=>		"text/html",
			"/^gtkrc$/"			=>		"text/x-gtkrc",
			"/^\.gtkrc$/"		=>		"text/x-gtkrc",
			"/^.*\.gvp$/"		=>		"text/x-google-video-pointer",
			"/^.*\.hs$/"		=>		"text/x-haskell",
			"/^.*\.idl$/"		=>		"text/x-idl",
			"/^INSTALL$/"		=>		"text/x-install",
			"/^.*\.java$/"		=>		"text/x-java",
			"/^.*\.ldif$/"		=>		"text/x-ldif",
			"/^.*\.lhs$/"		=>		"text/x-literate-haskell",
			"/^.*\.log$/"		=>		"text/x-log",
			"/^[Mm]akefile$/"	=>		"text/x-makefile",
			"/^GNUmakefile$/"	=>		"text/x-makefile",
			"/^.*\.moc$/"		=>		"text/x-moc",
			"/^.*\.mup$/"		=>		"text/x-mup",
			"/^.*\.not$/"		=>		"text/x-mup",
			"/^.*\.m$/"			=>		"text/x-objcsrc",
			"/^.*\.ml$/"		=>		"text/x-ocaml",
			"/^.*\.mli$/"		=>		"text/x-ocaml",
			"/^.*\.m$/"			=>		"text/x-matlab",
			"/^.*\.p$/"			=>		"text/x-pascal",
			"/^.*\.pas$/"		=>		"text/x-pascal",
			"/^.*\.diff$/"		=>		"text/x-patch",
			"/^.*\.patch$/"		=>		"text/x-patch",
			"/^.*\.py$/"		=>		"text/x-python",
			"/^.*\.lua$/"		=>		"text/x-lua",
			"/^README*$/"		=>		"text/x-readme",
			"/^.*\.nfo$/"		=>		"text/x-readme",
			"/^.*\.spec$/"		=>		"text/x-rpm-spec",
			"/^.*\.scm$/"		=>		"text/x-scheme",
			"/^.*\.etx$/"		=>		"text/x-setext",
			"/^.*\.sql$/"		=>		"text/x-sql",
			"/^.*\.tcl$/"		=>		"text/x-tcl",
			"/^.*\.tk$/"		=>		"text/x-tcl",
			"/^.*\.tex$/"		=>		"text/x-tex",
			"/^.*\.ltx$/"		=>		"text/x-tex",
			"/^.*\.sty$/"		=>		"text/x-tex",
			"/^.*\.cls$/"		=>		"text/x-tex",
			"/^.*\.dtx$/"		=>		"text/x-tex",
			"/^.*\.ins$/"		=>		"text/x-tex",
			"/^.*\.latex$/"		=>		"text/x-tex",
			"/^.*\.texi$/"		=>		"text/x-texinfo",
			"/^.*\.texinfo$/"	=>		"text/x-texinfo",
			"/^.*\.me$/"		=>		"text/x-troff-me",
			"/^.*\.mm$/"		=>		"text/x-troff-mm",
			"/^.*\.ms$/"		=>		"text/x-troff-ms",
			"/^.*\.uil$/"		=>		"text/x-uil",
			"/^.*\.uri$/"		=>		"text/x-uri",
			"/^.*\.url$/"		=>		"text/x-uri",
			"/^.*\.xmi$/"		=>		"text/x-xmi",
			"/^.*\.fo$/"		=>		"text/x-xslfo",
			"/^.*\.xslfo$/"		=>		"text/x-xslfo",
			"/^.*\.xml$/"		=>		"application/xml",
			"/^.*\.xsl$/"		=>		"application/xml",
			"/^.*\.xslt$/"		=>		"application/xml",
			"/^.*\.xbl$/"		=>		"application/xml",
			"/^.*\.dv$/"		=>		"video/dv",
			"/^.*\.mpeg$/"		=>		"video/mpeg",
			"/^.*\.mpg$/"		=>		"video/mpeg",
			"/^.*\.mp2$/"		=>		"video/mpeg",
			"/^.*\.mpe$/"		=>		"video/mpeg",
			"/^.*\.vob$/"		=>		"video/mpeg",
			"/^.*\.m2t$/"		=>		"video/mpeg",
			"/^.*\.qt$/"		=>		"video/quicktime",
			"/^.*\.mov$/"		=>		"video/quicktime",
			"/^.*\.moov$/"		=>		"video/quicktime",
			"/^.*\.qtvr$/"		=>		"video/quicktime",
			"/^.*\.qtif$/"		=>		"image/x-quicktime",
			"/^.*\.qif$/"		=>		"image/x-quicktime",
			"/^.*\.viv$/"		=>		"video/vivo",
			"/^.*\.vivo$/"		=>		"video/vivo",
			"/^.*\.anim[1-9j]$/"=>		"video/x-anim",
			"/^.*\.fli$/"		=>		"video/x-flic",
			"/^.*\.flc$/"		=>		"video/x-flic",
			"/^.*\.hwp$/"		=>		"application/x-hwp",
			"/^.*\.hwt$/"		=>		"application/x-hwt",
			"/^.*\.mng$/"		=>		"video/x-mng",
			"/^.*\.asf$/"		=>		"video/x-ms-asf",
			"/^.*\.nsc$/"		=>		"application/x-netshow-channel",
			"/^.*\.wmv$/"		=>		"video/x-ms-wmv",
			"/^.*\.avi$/"		=>		"video/x-msvideo",
			"/^.*\.divx$/"		=>		"video/x-msvideo",
			"/^.*\.nsv$/"		=>		"video/x-nsv",
			"/^.*\.sdp$/"		=>		"application/sdp",
			"/^.*\.movie$/"		=>		"video/x-sgi-movie",
			"/^.*\.emp$/"		=>		"application/vnd.emusic-emusic_package",
			"/^.*\.ica$/"		=>		"application/x-ica",
			"/^.*\.xul$/"		=>		"application/vnd.mozilla.xul+xml",
			"/^.*\.602$/"		=>		"application/x-t602"
		);
		/**
		 * Attempts to retrieve the mime type given a file, path or extension.
		 * @param string $filename File, extensions or pathname.
		 * @return string The MIME Type or the default one.
		 */
		public static function get_extension_mimetype($filename){
			$filename=strtolower(basename($filename));
			foreach(self::$mime_matches as $regex=>$mime)if(preg_match($regex,$filename))return $mime; // found
			return self::$mime_default; // not found
		}
		/**
		 * Returns whether the file is an image.<br>
		 * !! INSECURE AGAINST NULL BYTE POISONING !!
		 * @deprecated Phase out this function in some near future.
		 * @param string $file Target filename.
		 * @return boolean Whether target file is an image.
		 */
		public static function is_image($file){
			$ext=pathinfo($file,PATHINFO_EXTENSION);
			$exts=array('ico','bmp','png','gif','jpg','tif');
			return in_array($ext,$exts);
		}
		/**
		 * Debugger functionality.
		 * @return array Debug data.
		 */
		public static function onDebug(){
			return array(
				'Default MIME type'=>self::$mime_default,
				'Total known MIME types'=>count(self::$mime_matches),
				'All MIME types'=>self::$mime_matches
			);
		}
	}

	/**
	 * File: boot.php
	 */
	class CFG {
		/**
		 * @var boolean (default is true) Set this to false if you want to
		 * lock existing configuration settings so that they won't be overwritten
		 * this is useful in case where you want to set some config before
		 * actually loading the config file, so you ensure that the config file
		 * will not mess any settings you did earlier.
		 * @example
		 *     CFG::set('DEBUG_MODE','console');
		 *     CFG::$override=false;
		 *     include_once('config.php');
		 */
		public static $override=true;
		/**
		 * @var array Configuration storage.
		 */
		private static $store=array();
		/**
		 * Set value of a configuration.
		 * @param string|array $config Either config name (string) or an array of name=>value pairs.
		 * @param string|null $value (Optional) The $config's new value or null when $config is an array.
		 */
		public static function set($config,$value=null){
			if(is_array($config)){
				foreach($config as $name=>$value)
					if(self::$override || !isset(self::$store[$name]))
						self::$store[$name]=$value;
			}elseif(self::$override || !isset(self::$store[$config]))
				self::$store[$config]=$value;
		}
		/**
		 * Returns the value of a configuration or the default value if not set.
		 * @param string $config The configuration name.
		 * @param mixed $default (Optional) returned when $config doesn't exist.
		 * @return mixed Configuration or default value.
		 */
		public static function get($config,$default=null){
			return isset(self::$store[$config]) ? self::$store[$config] : $default;
		}
	}

	/**
	 * File: config.php
	 */

	CFG::set(array(
		'DEBUG_MODE'=>'none',
		'DEBUG_VERBOSE'=>false,
		'ABS_K2F'=>str_replace(array('/','//','\\','\\\\','\\/','/\\'),DIRECTORY_SEPARATOR,dirname(__FILE__).'/')
	));

?>