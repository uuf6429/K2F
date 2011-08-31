<?php defined('K2F') or die;

	/**
	 * Add FTP streamwrapper support to PHP.
	 * @example ftp://username:password@host/path/to/file
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 04/10/2010
	 * @todo 1. Double check existing features (file write etc).<br>
	 *       2. Change class so that certain resources are shared/cached, such as:<br>
	 *        3. directory listings - they're used all the time + performance gain<br>
	 *        4. curl handle - used all the time + huge gain in performance<br>
	 */
	class FtpStreamWrapper {
		/**
		 * Setup the main CURL object.
		 * @param string $url The FTP URL.
		 * @return resource The curl handle.
		 */
		private static function setup($url,$cred=':'){
			xlog('FTPWRP: Connecting to "'.$url.'" ('.($cred==':'?'no user/pass':$cred).')');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			if($cred!=':')curl_setopt($ch, CURLOPT_USERPWD, $cred);
			if(strstr($url,'ftps:')!==false){
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
				curl_setopt($ch, CURLOPT_FTP_SSL, CURLFTPSSL_TRY);
			}
			curl_setopt($ch, CURLOPT_FTP_USE_EPSV, TRUE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			return $ch;
		}
		/**
		 * Removes username:password from URL.
		 * @param string $url FTP URL with credentials.
		 * @return array An array with items url,credentials,file/folder,path
		 */
		private static function usrpwd($url){
			$prs=parse_url($url);
			//$prs['path']=(dirname($prs['path'])=='/')?'':dirname($prs['path']);
			if(!isset($prs['path']))$prs['path']='/';
			return array(
					$prs['scheme'].'://'.$prs['host'],
					(isset($prs['user'])&&isset($prs['pass']))?$prs['user'].':'.$prs['pass']:'',
					basename($prs['path']),
					$prs['path']
				);
		}
		/**
		 * Throws a CURL exception if a CURL error occured.
		 * @param resource $ch CURL handle to check.
		 */
		private static function error($ch){
			xlog('FTPWRP: Checking connection status: '.(curl_errno($ch)==0?'no errors':' Error: ('.curl_errno($ch).') '.curl_error($ch)));
		}
		/**
		 * Checks whether FTP is on a Windows system.
		 * @param string $path An FTP URL complete with credentials.
		 * @return boolean Whether system is on Windows or not.
		 */
		private static function isWinFtp($path){
			list($url,$cred,$trgt,$pth)=self::usrpwd($path);
			if(!isset($GLOBALS['isWinFtp']))$GLOBALS['isWinFtp']=array();
			if(!isset($GLOBALS['isWinFtp'][$url])){
				$GLOBALS['isWinFtp'][$url]=false;
				$GLOBALS['isWinFtpH']=$url;
				if(!function_exists('k2f_ftp_checkWinFtpHeaders')){
					function k2f_ftp_checkWinFtpHeaders($ch,$hdr){
						if(CFG::get('DEBUG_VERBOSE'))xlog('FTPWRP: Read header: ',$hdr);
						if(strstr(strtolower($hdr),'windows')!==false)
							$GLOBALS['isWinFtp'][$GLOBALS['isWinFtpH']]=true;
						return strlen($hdr);
					}
				}
				$ch=self::setup($url,$cred);
				curl_setopt($ch,CURLOPT_HEADERFUNCTION,'k2f_ftp_checkWinFtpHeaders');
				curl_setopt($ch,CURLOPT_QUOTE,array('SYST'));
				curl_exec($ch);
				curl_close($ch);
			}
			xlog('FTPWRP: Is FTP Server on Windows?',$GLOBALS['isWinFtp'][$url]);
			return $GLOBALS['isWinFtp'][$url];
		}
		/**
		 * Returns an array of FS objects (files,dirs,symlinks).
		 * @param string $path Fully qualified FTP url.
		 * @return array Array of FS objects.
		 */
		private static function getDirListing($path){
			list($url,$cred,$trgt,$pth)=self::usrpwd($path);
			$ch=self::setup($url,$cred);
			if(!function_exists('k2f_ftp_getDirListingHeaders')){
				function k2f_ftp_getDirListingHeaders($ch,$hdr){
					if(CFG::get('DEBUG_VERBOSE'))xlog('FTPWRP: Read header: ',$hdr);
					return strlen($hdr);
				}
			}
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'k2f_ftp_getDirListingHeaders');
			if(self::isWinFtp($path))curl_setopt($ch,CURLOPT_QUOTE,array('SITE DIRSTYLE'));
			$dirlist=curl_exec($ch);
			self::error($ch);
			curl_close($ch);
			$dirlist=FtpDirListingParser::parse($dirlist);
			xlog('FTPWRP: Getting listing of "'.$path.'":',$dirlist);
			return $dirlist;
		}
		/**
		 * Equivalent to file_get_contents().
		 * @param string $path Full FTP URL to file.
		 * @return string File data or False when file not found/readable.
		 */
		private static function getFileData($path){
			list($url,$cred,$trgt,$pth)=self::usrpwd($path);
			if(!function_exists('k2f_ftp_bufferWriteNextChunk')){
				function k2f_ftp_bufferWriteNextChunk($ch,$str){
					$GLOBALS['getFileData'].=$str;
					return strlen($str);
				}
			}
			$ch=self::setup($url.$pth,$cred);
			if(!function_exists('k2f_ftp_getFileDataHeaders')){
				function k2f_ftp_getFileDataHeaders($ch,$hdr){
					if(CFG::get('DEBUG_VERBOSE'))xlog('FTPWRP: Read header: ',$hdr);
					return strlen($hdr);
				}
			}
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'k2f_ftp_getFileDataHeaders');
			curl_setopt($ch,CURLOPT_QUOTE,array('TYPE I'));
			$GLOBALS['getFileData']='';
			curl_setopt($ch,CURLOPT_WRITEFUNCTION,'k2f_ftp_bufferWriteNextChunk');
			curl_exec($ch);
			self::error($ch);
			curl_close($ch);
			xlog('FTPWRP: Getting data of "'.$path.'"...');
			return $GLOBALS['getFileData'];
		}
		/**
		 * Equivalent to file_put_contents().
		 * @param string $path Full FTP URL to file.
		 * @param string $data The file's new content.
		 * @param boolean Returns success.
		 */
		private static function setFileData($path,$data){
			$GLOBALS['setFilePosn']=0;
			$GLOBALS['setFileData']=$data;
			list($url,$cred,$trgt,$pth)=self::usrpwd($path);
			if(!function_exists('k2f_ftp_bufferReadNextChunk')){
				function k2f_ftp_bufferReadNextChunk($ch,$fd,$length){
					$chunk=substr($GLOBALS['setFileData'],$GLOBALS['setFilePosn'],$length);
					$GLOBALS['setFilePosn']+=strlen($chunk);
					return ($chunk=='')?null:$chunk;
				}
			}
			$ch=self::setup($url.$pth,$cred);
			if(!function_exists('k2f_ftp_setFileDataHeaders')){
				function k2f_ftp_setFileDataHeaders($ch,$hdr){
					if(CFG::get('DEBUG_VERBOSE'))xlog('FTPWRP: Read header: ',$hdr);
					return strlen($hdr);
				}
			}
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'k2f_ftp_setFileDataHeaders');
			curl_setopt($ch,CURLOPT_UPLOAD,true);
			curl_setopt($ch,CURLOPT_QUOTE,array('TYPE I'));
			curl_setopt($ch,CURLOPT_INFILESIZE,strlen($data));
			curl_setopt($ch,CURLOPT_READFUNCTION,'k2f_ftp_bufferReadNextChunk');
			curl_exec($ch);
			self::error($ch);
			curl_close($ch);
			xlog('FTPWRP: Setting data of "'.$path.'"...');
			return (curl_errno($ch)==0);
		}
		public function __construct(){
			// UNUSED
		}
		public function dir_closedir(){
			$this->dirlist=array();
			$this->dirbckp=array();
			return true;
		}
		public function dir_opendir($path , $options){
			$this->dirbckp=array();
			$this->dirlist=self::getDirListing($path);
			foreach($this->dirlist as $file)
				$this->dirbckp[]=$file->name;
			$this->dirlist=$this->dirbckp;
			return true;
		}
		public function dir_readdir(){
			if(count($this->dirlist)==0)return false;
			return array_pop($this->dirlist);
		}
		public function dir_rewinddir(){
			$this->dirlist=$this->dirbckp;
			return true;
		}
		public function mkdir($path , $mode , $options){						// MKD $path
			// @TODO: Support $mode and maybe $options.
			list($url,$cred,$trgt,$pth)=self::usrpwd($path);
			$ch = self::setup($url,$cred);
			if(!function_exists('k2f_ftp_mkdirHeaders')){
				function k2f_ftp_mkdirHeaders($ch,$hdr){
					if(CFG::get('DEBUG_VERBOSE'))xlog('FTPWRP: Read header: ',$hdr);
					return strlen($hdr);
				}
			}
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'k2f_ftp_mkdirHeaders');
			xlog('FTPWRP: Creating folder "'.$pth.'"...');
			curl_setopt($ch,CURLOPT_QUOTE,array('MKD '.$pth));
			curl_exec($ch);
			self::error($ch);
			return curl_errno($ch)==0;
		}
		public function rename($path_from,$path_to){							// RNFR $from, RNTO $to
			list($url1,$cred1,$trgt1,$pth1)=self::usrpwd($path_from);
			list($url2,$cred2,$trgt2,$pth2)=self::usrpwd($path_to);
			$ch = self::setup($url1,$cred1);
			if(!function_exists('k2f_ftp_renameHeaders')){
				function k2f_ftp_renameHeaders($ch,$hdr){
					if(CFG::get('DEBUG_VERBOSE'))xlog('FTPWRP: Read header: ',$hdr);
					return strlen($hdr);
				}
			}
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'k2f_ftp_renameHeaders');
			xlog('FTPWRP: Renaming folder "'.$pth1.'" to "'.$pth2.'"...');
			curl_setopt($ch,CURLOPT_QUOTE,array('RNFR '.$pth1,'RNTO '.$pth2));
			curl_exec($ch);
			self::error($ch);
			return curl_errno($ch)==0;
		}
		public function rmdir($path , $options){								// RMD $path
			list($url,$cred,$trgt,$pth)=self::usrpwd($path);
			$ch = self::setup($url,$cred);
			if(!function_exists('k2f_ftp_rmdirHeaders')){
				function k2f_ftp_rmdirHeaders($ch,$hdr){
					if(CFG::get('DEBUG_VERBOSE'))xlog('FTPWRP: Read header: ',$hdr);
					return strlen($hdr);
				}
			}
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'k2f_ftp_rmdirHeaders');
			xlog('FTPWRP: Removing folder "'.$pth.'"...');
			curl_setopt($ch,CURLOPT_QUOTE,array('RMD '.$pth));
			curl_exec($ch);
			self::error($ch);
			return curl_errno($ch)==0;
		}
		public function stream_cast($cast_as){
			// UNUSED
		}
		public function stream_close(){
			// UNUSED
		}
		public function stream_eof(){
			return $this->stream_tell()>=strlen($this->data);
		}
		public function stream_flush(){
			// UNUSED
		}
		public function stream_lock($operation){
			// UNUSED
		}
		public function stream_open($path , $mode , $options , &$opened_path){
			$this->path=$path;
			$this->mode=str_ireplace(array('b','t'),'',$mode);
			$this->isbn=(strstr($mode,'t')===false);							// is binary mode? [ignored]
			$this->data=self::getFileData($path);
			$this->posn=0;
			switch($this->mode){
				case 'w': case 'w+':
					$this->data='';
					break;
				case 'a': case 'a+':
					$this->posn=strlen($this->data);
					break;
			}
			return true;
		}
		public function stream_read($count){
			$str=substr($this->data,$this->posn,$count);
			$this->posn+=strlen($str);
			return $str;
		}
		public function stream_seek($offset , $whence = SEEK_SET){
			switch($whence){
				case SEEK_CUR:
					$np=$this->posn+$offset;
					break;
				case SEEK_END:
					$np=strlen($this->data)+$offset;
					break;
				case SEEK_SET:
					$np=$offset;
					break;
			}
			if($np==$this->posn)return false;
			$this->posn=$np;    return true;
		}
		public function stream_set_option($option , $arg1 , $arg2){
			// UNUSED
		}
		public function stream_stat(){
			return $this->url_stat($this->path);
		}
		public function stream_tell(){
			return $this->posn;
		}
		public function stream_write($data){
			$ret=strlen($data);
			switch($this->mode){
				case 'r': // nothing is done
					$ret=0;
					break;
				case 'r+': // overwrite from position
					$this->data=substr_replace($this->data,$data,$this->posn,strlen($data));
					break;
				case 'w': case 'w+': // fill up to posn with nulls and append
				case 'x': case 'x+': // fill up to posn with nulls and append
					$this->data=str_pad($this->data,$this->posn,chr(0),STR_PAD_RIGHT).$data;
					break;
				case 'a': case 'a+': // always append
					$this->data.=$data;
					$this->posn+=strlen($data);
					break;
				default:
					$ret=0;
			}
			return self::setFileData($this->path,$this->data)?$ret:0;
		}
		public function unlink($path){											// DELE $path
			list($url,$cred,$trgt,$pth)=self::usrpwd($path);
			$ch = self::setup($url,$cred);
			if(!function_exists('k2f_ftp_unlinkHeaders')){
				function k2f_ftp_unlinkHeaders($ch,$hdr){
					xlog('Header: '.$hdr);
					return strlen($hdr);
				}
			}
			curl_setopt($ch,CURLOPT_HEADERFUNCTION,'k2f_ftp_unlinkHeaders');
			xlog('Remove file "'.$pth.'"');
			curl_setopt($ch,CURLOPT_QUOTE,array('DELE '.$pth));
			curl_exec($ch);
			self::error($ch);
			return curl_errno($ch)==0;
		}
		public function url_stat($path,$flags=0){
			$name=basename($path);
			// init variables
			$orig=array('dev','ino','mode','nlink','uid','gid','rdev','size','atime','mtime','ctime','blksize','blocks'); $ret=array();
			// do some FTP magic
			foreach(self::getDirListing(dirname($path)) as $file)
				if($file->name==$name){
					//$ret['dev']=$file->dev;
					//$ret['ino']=$file->ino;
					$ret['mode']=$file->mode;
					//$ret['nlink']=$file->nlink;
					//$ret['uid']=$file->uid;
					//$ret['gid']=$file->gid;
					//$ret['rdev']=$file->rdev;
					$ret['size']=$file->size;
					//$ret['atime']=$file->atime;
					$ret['mtime']=$file->time;
					//$ret['ctime']=$file->ctime;
					//$ret['blksize']=$file->blksize;
					//$ret['blocks']=$file->blocks;
				}
			// reformat result and return
			foreach($orig as $k=>$v){
				if(!isset($ret[$v]))$ret[$v]=0;
				$ret[$k]=$ret[$v];
			}
			return $ret;
		}
	}
	// Install custom wrapper if FTP wrapper is unsuported.
	//if(ini_get('allow_url_fopen')!='1'){ // stream wrappers disabled
		// if wrapper is already registered, unregister it first
		if(in_array('ftp',stream_get_wrappers()))stream_wrapper_unregister('ftp');
		// register new custom FTP wrapper
		stream_wrapper_register('ftp','FtpStreamWrapper');
	//}

	/**
	 * FTP Directory listing parser class. <b>INTERNAL USE ONLY</b>
	 * This is used internally in the FTP stream-wrapper class, to parse directory listings.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 24/09/2010
	 * @todo 1. The lasses misses certain FS object constants.<br>
	 *       2. parseMode() - Missing certain details/constants/flags.<br>
	 *       3. parseKind() - Missing certain parsing of certain FS object constants.<br>
	 *       4. parseLine() - Ensure this is compatible as much as possible.<br>
	 *
	 */
	class FtpDirListingParser {
		/**
		 * Some meaningful constants...
		 */
		const FLP_FILE    = 'FLP_FILE';
		const FLP_FOLDER  = 'FLP_FOLDER';
		const FLP_SYMLINK = 'FLP_SYMLINK';
		/**
		 * @var array Array of current items (being parsed).
		 */
		private static $items=array();
		/**
		 * Called on initialization of a new parsing.<br>
		 * Item store is cleared.
		 */
		private static function init(){
			self::$items=array();
		}
		/**
		 * Called on finalization of a new parsing.
		 * @return array An array of listing objects (see ::add for details).
		 */
		private static function fini(){
			return self::$items;
		}
		/**
		 * Add item to listing.
		 * @param string $raw Raw data.
		 * @param string $name Item name.
		 * @param string $uid Item user id/name.
		 * @param string $gid Item group id/name.
		 * @param integer $mode Item access mode.
		 * @param integer $size Item size.
		 * @param integer $time Item creation(?) time.
		 * @param string $kind Type of item (FLP_FILE, FLP_FOLDER or FLP_SYMLINK).
		 */
		private static function add($raw,$name,$uid,$gid,$mode,$size,$time,$kind){
			$line=new stdClass();
			$line->raw=$raw;
			$line->name=$name;
			$line->uid=$uid;
			$line->gid=$gid;
			$line->mode=$mode;
			$line->size=$size;
			$line->time=$time;
			$line->kind=$kind;
			self::$items[]=$line;
		}
		/**
		 * Return access mode as a decimal given human-readable form.
		 * @param string $human The original, human-readable mode string (eg: -r-xrw..).
		 * @return integer The real value of the access mode.
		 */
		private static function parseMode($human){
			$mode = 0;
			if ($human[0] == '?') $mode += 0000000; // '?' for any other file type.     // TODO: is this correct default value?
			if ($human[0] == 'p') $mode += 0010000; // 'p' for fifos
			if ($human[0] == 'c') $mode += 0020000; // 'c' for character special files
			if ($human[0] == 'd') $mode += 0040000; // 'd' for directories
			if ($human[0] == 'b') $mode += 0060000; // 'b' for block special files
//			if ($human[0] == 'm') $mode += 0000000; // 'm' for multiplexor files        // TODO: couldn't find constant for IFMPC
			if ($human[0] == '-') $mode += 0100000; // '-' for regular files
			if ($human[0] == 'l') $mode += 0120000; // 'l' for symbolic links
			if ($human[0] == 's') $mode += 0140000; // 's' for sockets
				if ($human[1] == 'r') $mode += 0400;
				if ($human[2] == 'w') $mode += 0200;
				if ($human[3] == 'x') $mode += 0100;
					else if ($human[3] == 's') $mode += 04100;
					else if ($human[3] == 'S') $mode += 04000;
				if ($human[4] == 'r') $mode += 040;
				if ($human[5] == 'w') $mode += 020;
				if ($human[6] == 'x') $mode += 010;
					else if ($human[6] == 's') $mode += 02010;
					else if ($human[6] == 'S') $mode += 02000;
				if ($human[7] == 'r') $mode += 04;
				if ($human[8] == 'w') $mode += 02;
				if ($human[9] == 'x') $mode += 01;
					else if ($human[9] == 't') $mode += 01001;
					else if ($human[9] == 'T') $mode += 01000;
			return $mode;
		}
		/**
		 * Returns the kind of object (file, directory, symlink).
		 * @param string $human The original, human-readable mode string (eg: -r-xrw..).
		 * @return string Item type constant (FLP_FILE, FLP_FOLDER or FLP_SYMLINK).
		 */
		private static function parseKind($human){
			if(strstr($human,'l')!==false)return self::FLP_SYMLINK;
			if(strstr($human,'d')!==false)return self::FLP_FOLDER;
			return self::FLP_FILE;
		}
		/**
		 * Parse unix-style (?) FTP listing.
		 * @param string $line A single line from a directory listing.
		 */
		private static function parseLine($line){
			if(ereg("([-dl][rwxstST-]+).* ([0-9 ]* )([a-zA-Z0-9]+).* ([a-zA-Z0-9]+).* ([0-9]*) ([a-zA-Z]+[0-9: ]*[0-9])[ ]+(([0-9]{2}:[0-9]{2})|[0-9]{4}) (.+)",$line,$regs))
				self::add($regs,$regs[9],$regs[3],$regs[4],self::parseMode($regs[1]),$regs[5],strtotime($regs[6].' '.$regs[7]),self::parseKind($regs[1]));
		}
		/**
		 * This is the main parsing function.
		 * @param string $listing Lines of a directory listing.
		 * @return array An array of objects each with properties:
		 *                $name,$uid,$gid,$mode,$size,$time,$kind
		 */
		public static function parse($listing){
			self::init();
			$listing=explode(CR,str_replace(CR.CR,CR,str_replace(LF,CR,trim($listing))));
			foreach($listing as $line)self::parseLine($line);
			return self::fini();
		}
	}

?>