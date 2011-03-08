<?php defined('K2F') or die;

	uses('core/system.php','core/mime.php');

	/**
	 * The base class for WKHTMLTOX system. Do not call or instantiate this class directly.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 */
	abstract class WKCORE {
		
		/// CONSTANTS ///
		
		/**
		 * The source is some HTML.
		 */
		const SRC_HTML = 0;
		/**
		 * Source is from a URL.
		 */
		const SRC_URL  = 1;

		/**
		 * Rendered data is pushed to client as a (forced) download.
		 * The second parameter is file name as seen in the download dialog box.
		 */
		const OUT_DOWNLOAD = 0;
		/**
		 * Rendered data is pushed to client for viewing (or embedding).
		 */
		const OUT_EMBED    = 1;
		/**
		 * Rendered data is returned by this function as a string.
		 */
		const OUT_RETURN   = 2;
		/**
		 * Rendered data is saved to a file on the server.
		 * The second parameter is the file path and name.
		 */
		const OUT_SAVE     = 3;
		
		/// PROPERTIES ///
		
		/**
		 * @var null Internal cache variables.
		 */
		protected static $os=null,$cpu=null,$exe=null;
		
		/**
		 * @var integer Source type.
		 */
		protected $type=self::SRC_HTML;
		/**
		 * @var null The source.
		 */
		protected $source=null;
		/**
		 * @var array|null When rendered, this is the executable STD responses.
		 */
		protected $result=null;
		
		/// UTILITY METHODS ///
		
		/**
		 * Returns the operating system name (eg: -win, -lin etc...).
		 * @return string Name of the current OS.
		 */
		protected static function os(){
			if(!self::$os)
				self::$os='-'.substr(System::os_type(),0,3);
			return self::$os;
		}
		/**
		 * Returns special CPU vendor names (eg: -ppc, -via, etc...).
		 * @return string Name of special (non-conventional) CPUs.
		 */
		protected static function cpu(){
			// intel/amd are compatible defaults, so we ignore them
			if(!self::$cpu && (self::$cpu=System::cpu_make()))
				self::$cpu=((self::$cpu=='intel' || self::$cpu=='amd') ? '' : '-'.self::$cpu);
			return self::$cpu;
		}
		/**
		 * Returns path to executable or null on failure (+triggering an error).
		 * @param string $bin The binary file name (eg: wkhtmltoimg).
		 * @return string|null Pathname on success, null on failure.
		 */
		protected static function exe($bin){
			if(!self::$exe){
				$bits=System::php_bits()*2;
				$root=CFG::get('ABS_K2F').'libs'.DIRECTORY_SEPARATOR.'wkhtmltox'.DIRECTORY_SEPARATOR.$bin.self::os().'-';
				while(!file_exists(self::$exe) && ($bits=round($bits/2))>4) // easter egg; it checks for 8 bit as well :D
					self::$exe=$root.$bits.self::cpu().(self::os()=='-win' ? '.exe' : '');
				if(!file_exists(self::$exe)){
					trigger_error('WKIMG error: Could not find executable for the current platform.');
					return null;
				}
				self::$exe=str_replace($bin,'{:bin:}',self::$exe);
			}
			return str_replace('{:bin:}',$bin,self::$exe);
		}
		/**
		 * Generates a new, unique file name and allocates an empty file. Characters used: a-z A-Z 0-9
		 * @param string $prefix (Optional) Any text to be used at the start of file name.
		 * @param string $suffix (Optional) Some text added at the end of file (such as file extension).
		 */
		protected static function tmp($prefix='',$suffix=''){
			return System::temporary(System::TMP_FILE,sys_get_temp_dir(),$prefix,$suffix,true);
		}
		/**
		 * Builds and returns the command line string for execution.
		 * @param string $tmp Name and path of a temporary file generated for this particular execution.
		 * @return string Command line program options (not cached!).
		 */
		abstract protected function cmd($tmp);
		
		/// PUBLIC METHODS ///
		
		/**
		 * Set the render source (eg: url or html).
		 * @param integer $type Type of source (see SRC_* constants).
		 * @param mixed $source The source (value depends on type).
		 */
		public function set_source($type,$source){
			$this->type=(int)$type;
			$this->source=$source;
		}
		/**
		 * Performs the conversion (aka rendering).
		 * @return boolean True on success, false otherwise.
		 */
		public function render(){
			// create temporary file only if needed (performance hotfix)
			$tmp=($this->type==self::SRC_HTML) ? self::tmp('out','.html') : '';
			// execute command line
			$this->result=System::execute($this->cmd($tmp));
			// ensure program returned *something*.
			if($this->result['stdout']=='')
				trigger_error('WKHTMLTOX error: program returned empty result.');
			// handle execution return codes (only 0 means success).
			switch($this->result['return']){
				case 7:
					trigger_error('WKHTMLTOX system error 7: malformed executable (execution failure).');
					return false;
				case 3:
					trigger_error('WKHTMLTOX error 401: recieved "unauthorized access" for target url.');
					return false;
				case 2:
					trigger_error('WKHTMLTOX error 404: recieved "file not found" for target url.');
					return false;
				case 1:
					trigger_error('WKHTMLTOX system error 1: a generic error occured, possibly due to wrong parameters.');
					return false;
				case 0:
					return true;
				default:
					trigger_error('WKHTMLTOX error '.$this->result['return'].': Unidentified error code.');
					return false;
			}
		}
		/**
		 * Outputs rendered data with various options.
		 * @param integer $mode Determine how two output (see OUT_* constants).
		 * @param string $file The use of this parameter depends on $mode.
		 * @return string|boolean Depending on $mode, this may be success (boolean) or data (string).
		 */
		abstract public function output($mode=self::OUT_DOWNLOAD,$file=null);
	}

	class WKIMG extends WKCORE {

		/// CONSTANTS ///

		/**
		 * Windows Bitmap
		 */
		const FMT_BMP = 'bmp';
		/**
		 * Joint Photographic Experts Group
		 */
		const FMT_JPG = 'jpg';
		/**
		 * Portable Network Graphics
		 */
		const FMT_PNG = 'png';
		/**
		 * Portable Pixmap
		 */
		const FMT_PPM = 'ppm';
		/**
		 * Tagged Image File Format
		 */
		const FMT_TIFF = 'tiff';
		/**
		 * X11 Bitmap
		 */
		const FMT_XBM = 'xbm';
		/**
		 * X11 Pixmap
		 */
		const FMT_XPM = 'xpm';
		/**
		 * Scalable Vector Graphics
		 */
		const FMT_SVG = 'svg';

		/// PROPERTIES ///

		/**
		 * @var null|array Array of cropping values (x,y,w,h) or null.
		 */
		protected $crop=null;
		/**
		 * @var string|null File format of output image.
		 */
		protected $format=null;
		/**
		 * @var integer|null Number of milliseconds to wait for javascript to load.
		 */
		protected $delay=null;
		/**
		 * @var array|null HTTP Authorization credentials.
		 */
		protected $login=null;
		/**
		 * @var integer|null Output image quality. Default is 94.
		 */
		protected $quality=null;
		/**
		 * @var boolean|null Generates transparent output images.
		 */
		protected $transparent=null;
		/**
		 * @var integer|null The renderer width. Default value is undefined and platform-dependent.
		 */
		protected $width=null;
		/**
		 * @var float|null The page zoom factor. Default value is 1.
		 */
		protected $zoom=null;

		/// UTILITY METHODS ///

		/**
		 * Builds and returns the command line string for execution.
		 * @param string $tmp Name and path of a temporary file generated for this particular execution.
		 * @return string Command line program options (not cached!).
		 */
		protected function cmd($tmp){
			// Handle different source types
			if($this->type==self::SRC_HTML){
				file_put_contents($tmp,$this->source);
				$this->source='file:///'.$tmp;
			}
			// Generate cmd options
			$cmd=(self::os()!=='-win' ? self::exe('wkhtmltoimg') : 'cd '.escapeshellarg(dirname(self::exe('wkhtmltoimg'))).' && '.basename(self::exe('wkhtmltoimg')));
			if($this->crop){
				if($this->crop[0])$cmd.=' --crop-x '.escapeshellarg($this->crop[0]);
				if($this->crop[1])$cmd.=' --crop-y '.escapeshellarg($this->crop[1]);
				if($this->crop[2])$cmd.=' --crop-w '.escapeshellarg($this->crop[2]);
				if($this->crop[3])$cmd.=' --crop-h '.escapeshellarg($this->crop[3]);
			}
			if($this->format!==null)$cmd.=' --format '.escapeshellarg($this->format);
			if($this->delay!==null)$cmd.=' --javascript-delay '.(int)$this->delay;
			if($this->login){
				if($this->login[0])$cmd.=' --username '.escapeshellarg($this->login[0]);
				if($this->login[1])$cmd.=' --password '.escapeshellarg($this->login[1]);
			}
			if($this->quality!==null)$cmd.=' --quality '.(int)$this->quality;
			if($this->transparent)$cmd.=' --transparent';
			if($this->width!==null)$cmd.=' --width '.(int)$this->width;
			if($this->zoom!==null)$cmd.=' --zoom '.(float)$this->zoom;
			// Return generated cmd
			return $cmd.' --enable-local-file-access '.escapeshellarg($this->source).' -';
		}

		/// PUBLIC METHODS ///

		/**
		 * Used to only save a specific part of the full image.<br/>
		 * Leave any value to NULL to disable it.
		 * @param integer|null $left
		 * @param integer|null $top
		 * @param integer|null $width
		 * @param integer|null $height
		 */
		public function set_crop($left=null,$top=null,$width=null,$height=null){
			$this->crop=array($left,$top,$width,$height);
		}
		/**
		 * Set the output image file format.
		 * @param string|null $format File format (see FMT_* constants).
		 */
		public function set_format($format=null){
			$this->format=$format;
		}
		/**
		 * Number of milliseconds to wait for javascript to load.
		 * @param integer|null $delay Delay in milliseconds. If null, default value is used (200 milliseconds).
		 */
		public function set_delay($delay=null){
			$this->delay=$delay;
		}
		/**
		 * Set authorization credentials (for HTTP Authentication only!!).
		 * @param string|null $username The username.
		 * @param string|null $password The password.
		 */
		public function set_credentials($username=null,$password=null){
			$this->login=($username || $password) ? array($username,$password) : null;
		}
		/**
		 * Set output image quality (for lossy formats), lower quality *might* decrease file size.
		 * @param integer|null $quality The quality between 0(low-quality) and 100(full quality). Default(null) is 94.
		 */
		public function set_quality($quality=null){
			$this->quality=$quality;
		}
		/**
		 * By default, the output image is not made transparent. you can make it so with this.
		 * @param boolean|null $enable Enable output transparency. Since the default is not, both false and null disables this.
		 */
		public function set_transparent($enable=null){
			$this->transparent=$enable;
		}
		/**
		 * Set renderer width. This is like setting the browser window's width.
		 * @param integer|null $width The width in pixels. WARNING: the default value is platform-dependent.
		 */
		public function set_width($width=null){
			$this->width=$width;
		}
		/**
		 * Set the zooming factor (default is 1).
		 * @param float|null $factor Zooming factor, example: a float value of 0.5 is two zoom out in half whereas a value of 2 is zooming the page twice it's original.
		 */
		public function set_zoom($factor=null){
			$this->zoom=$factor;
		}

		/**
		 * Return image with various options.
		 * @param integer $mode Determine how two output (see OUT_* constants).
		 * @param string $file The of this parameter depends on $mode.
		 * @return string|boolean Depending on $mode, this may be success (boolean) or image data.
		 */
		public function output($mode=self::OUT_DOWNLOAD,$file=null){
			if($file===null)$file='output.'.$this->format;
			switch($mode){
				case self::OUT_DOWNLOAD:
					if(!@headers_sent()){
						ob_end_clean();
						header('Content-Description: File Transfer');
						header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
						header('Pragma: public');
						header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
						header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
						// force download dialog
						header('Content-Type: application/force-download');
						header('Content-Type: application/octet-stream', false);
						header('Content-Type: application/download', false);
						header('Content-Type: '.MimeTypes::get_extension_mimetype($file), false);
						// use the Content-Disposition header to supply a recommended filename
						header('Content-Disposition: attachment; filename="'.basename($file).'";');
						header('Content-Transfer-Encoding: binary');
						header('Content-Length: '.strlen($this->result['stdout']));
						echo $this->result['stdout'];
						return true;
					}else{
						trigger_error('WKHTMLTOX error: HTTP headers were already sent.');
						return false;
					}
				case self::OUT_RETURN:
					return $this->result['stdout']===null ? false : $this->result['stdout'];
				case self::OUT_EMBED:
					if(!@headers_sent()){
						header('Content-Type: '.MimeTypes::get_extension_mimetype($file));
						header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
						header('Pragma: public');
						header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
						header('Last-Modified: '.@gmdate('D, d M Y H:i:s').' GMT');
						header('Content-Length: '.strlen($this->result['stdout']));
						header('Content-Disposition: inline; filename="'.basename($file).'";');
						echo $this->result['stdout'];
						return true;
					}else{
						trigger_error('WKHTMLTOX error: HTTP headers were already sent.');
						return false;
					}
				case self::OUT_SAVE:
					return file_put_contents($file,$this->result['stdout']);
				default:
					trigger_error('WKHTMLTOX error: unsupported or unrecognized $mode value passed to output().');
					return false;
			}
		}
	}

	class WKPDF extends WKCORE {

		/// CONSTANTS ///

		/**
		 * Landscape (width is larger than height).
		 */
		const OR_LANDSCAPE = 'landscape';
		/**
		 * Portrait (height is larger than width).
		 */
		const OR_PORTRAIT = 'portrait';

		/**
		 * A0 (841 x 1189 mm)
		 */
		const SIZE_A0 = 'A0';
		/**
		 * A1 (594 x 841 mm)
		 */
		const SIZE_A1 = 'A1';
		/**
		 * A2 (420 x 594 mm)
		 */
		const SIZE_A2 = 'A2';
		/**
		 * A3 (297 x 420 mm)
		 */
		const SIZE_A3 = 'A3';
		/**
		 * A4 (210 x 297 mm, 8.26 x 11.69 inches)
		 */
		const SIZE_A4 = 'A4';
		/**
		 * A5 (148 x 210 mm)
		 */
		const SIZE_A5 = 'A5';
		/**
		 * A6 (105 x 148 mm)
		 */
		const SIZE_A6 = 'A6';
		/**
		 * A7 (74 x 105 mm)
		 */
		const SIZE_A7 = 'A7';
		/**
		 * A8 (52 x 74 mm)
		 */
		const SIZE_A8 = 'A8';
		/**
		 * A9 (37 x 52 mm)
		 */
		const SIZE_A9 = 'A9';
		/**
		 * B0 (1000 x 1414 mm)
		 */
		const SIZE_B0 = 'B0';
		/**
		 * B1 (707 x 1000 mm)
		 */
		const SIZE_B1 = 'B1';
		/**
		 * B2 (500 x 707 mm)
		 */
		const SIZE_B2 = 'B2';
		/**
		 * B3 (353 x 500 mm)
		 */
		const SIZE_B3 = 'B3';
		/**
		 * B4 (250 x 353 mm)
		 */
		const SIZE_B4 = 'B4';
		/**
		 * B5 (176 x 250 mm, 6.93 x 9.84 inches)
		 */
		const SIZE_B5 = 'B5';
		/**
		 * B6 (125 x 176 mm)
		 */
		const SIZE_B6 = 'B6';
		/**
		 * B7 (88 x 125 mm)
		 */
		const SIZE_B7 = 'B7';
		/**
		 * B8 (62 x 88 mm)
		 */
		const SIZE_B8 = 'B8';
		/**
		 * B9 (33 x 62 mm)
		 */
		const SIZE_B9 = 'B9';
		/**
		 * B10 (31 x 44 mm)
		 */
		const SIZE_B10 = 'B10';
		/**
		 * C5E (163 x 229 mm)
		 */
		const SIZE_C5E = 'C5E';
		/**
		 * Comm10E (105 x 241 mm, U.S. Common 10 Envelope)
		 */
		const SIZE_COMM10E = 'COMM10E';
		/**
		 * DLE (110 x 220 mm)
		 */
		const SIZE_DLE = 'DLE';
		/**
		 * Executive (7.5 x 10 inches, 190.5 x 254 mm)
		 */
		const SIZE_EXECUTIVE = 'EXECUTIVE';
		/**
		 * Folio (210 x 330 mm)
		 */
		const SIZE_FOLIO = 'FOLIO';
		/**
		 * Ledger 431.8 x 279.4 mm)
		 */
		const SIZE_LEDGER = 'LEDGER';
		/**
		 * Legal (8.5 x 14 inches, 215.9 x 355.6 mm)
		 */
		const SIZE_LEGAL = 'LEGAL';
		/**
		 * Letter (8.5 x 11 inches, 215.9 x 279.4 mm)
		 */
		const SIZE_LETTER = 'LETTER';
		/**
		 * Tabloid (279.4 x 431.8 mm)
		 */
		const SIZE_TABLOID = 'TABLOID';

		/// PROPERTIES ///

		/**
		 * @var null|integer The number of copies (default is 1).
		 */
		protected $copies=null;
		/**
		 * @var boolean|null Whether PDF is not colored or not (default is false).
		 */
		protected $grayscale=null;
		/**
		 * @var boolean|null Whether PDF is of low quality or not (default is false).
		 */
		protected $lowquality=null;
		/**
		 * @var array|null An array of margin widths (top right bottom left) (default is 10mm on each side).
		 */
		protected $margin=null;
		/**
		 * @var string|null Document's orientation (default is portrait).
		 */
		protected $orientation=null;
		/**
		 * @var string|null Output page size (default is A4).
		 */
		protected $size=null;
		/**
		 * @var string|null Document title (default is title of first web page).
		 */
		protected $title=null;
		/**
		 * @var integer|null Number of milliseconds to wait for javascript to load.
		 */
		protected $delay=null;
		/**
		 * @var array|null HTTP Authorization credentials.
		 */
		protected $login=null;
		/**
		 * @var float|null The page zoom factor. Default value is 1.
		 */
		protected $zoom=null;

		/// UTILITY METHODS ///

		/**
		 * Builds and returns the command line string for execution.
		 * @param string $tmp Name and path of a temporary file generated for this particular execution.
		 * @return string Command line program options (not cached!).
		 */
		protected function cmd($tmp){
			// Handle different source types
			if($this->type==self::SRC_HTML){
				file_put_contents($tmp,$this->source);
				$this->source='file:///'.$tmp;
			}
			// Generate cmd options
			$cmd=(self::os()!=='-win' ? self::exe('wkhtmltopdf') : 'cd '.escapeshellarg(dirname(self::exe('wkhtmltopdf'))).' && '.basename(self::exe('wkhtmltopdf')));
			if($this->copies!==null)$cmd.=' --copies '.(int)$this->copies;
			if($this->grayscale)$cmd.=' --grayscale';
			if($this->lowquality)$cmd.=' --lowquality';
			if($this->margin){
				if($this->margin[0])$cmd.=' --margin-top '.escapeshellarg($this->margin[0]);
				if($this->margin[1])$cmd.=' --margin-right '.escapeshellarg($this->margin[1]);
				if($this->margin[2])$cmd.=' --margin-bottom '.escapeshellarg($this->margin[2]);
				if($this->margin[3])$cmd.=' --margin-left '.escapeshellarg($this->margin[3]);
			}
			if($this->orientation!==null)$cmd.=' --orientation '.escapeshellarg($this->orientation);
			if($this->size!==null)$cmd.=' --page-size '.escapeshellarg($this->size);
			if($this->title!==null)$cmd.=' --title '.escapeshellarg($this->title);
			if($this->delay!==null)$cmd.=' --javascript-delay '.(int)$this->delay;
			if($this->login){
				if($this->login[0])$cmd.=' --username '.escapeshellarg($this->login[0]);
				if($this->login[1])$cmd.=' --password '.escapeshellarg($this->login[1]);
			}
			if($this->zoom!==null)$cmd.=' --zoom '.(float)$this->zoom;
			// Return generated cmd
			return $cmd.' --output-format pdf --enable-local-file-access --no-outline '.escapeshellarg($this->source).' -';
		} //                               '-- maybe make it an option?

		/// PUBLIC METHODS ///

		/**
		 * Set the amount of printed copies (in the same PDF file).
		 * @param integer|null $amount Number of copies (default is 1).
		 */
		public function set_copies($amount=null){
			$this->copies=$amount;
		}
		/**
		 * Set whether PDF should be in color or not.
		 * @param boolean|null $grayscale Enable grayscale printing or not (default is false).
		 */
		public function set_grayscale($grayscale=null){
			$this->grayscale=$grayscale;
		}
		/**
		 * Set the amount of printed copies (in the same PDF file).
		 * @param integer|null $amount Number of copies (default is 1).
		 */
		public function set_lowquality($low=null){
			$this->lowquality=$low;
		}
		/**
		 * Set the width of page margins. Leave any value to NULL to use the defult one.
		 * @param string|null $top Number of a specific unit (eg: mm, m, px, etc) or null (default is '10mm').
		 * @param string|null $right Number of a specific unit (eg: mm, m, px, etc) or null (default is '10mm').
		 * @param string|null $bottom Number of a specific unit (eg: mm, m, px, etc) or null (default is '10mm').
		 * @param string|null $left Number of a specific unit (eg: mm, m, px, etc) or null (default is '10mm').
		 */
		public function set_margins($top=null,$right=null,$bottom=null,$left=null){
			$this->margin=array($top,$right,$bottom,$left);
		}
		/**
		 * Set page orientation (see OR_* constants).
		 * @param string|null $orientation Document's orientation (default is portrait).
		 */
		public function set_orientation($orientation=null){
			$this->orientation=$orientation;
		}
		/**
		 * Set page size (see SIZE_* constants).
		 * @param string|null $size Output page size (default is A4).
		 */
		public function set_size($size=null){
			$this->size=$size;
		}
		/**
		 * Set the PDF's title text.
		 * @param string|null $title Document title (default is title of first web page).
		 */
		public function set_title($title=null){
			$this->title=$title;
		}
		/**
		 * Number of milliseconds to wait for javascript to load.
		 * @param integer|null $delay Delay in milliseconds. If null, default value is used (200 milliseconds).
		 */
		public function set_delay($delay=null){
			$this->delay=$delay;
		}
		/**
		 * Set authorization credentials (for HTTP Authentication only!!).
		 * @param string|null $username The username.
		 * @param string|null $password The password.
		 */
		public function set_credentials($username=null,$password=null){
			$this->login=($username || $password) ? array($username,$password) : null;
		}
		/**
		 * Set the zooming factor (default is 1).
		 * @param float|null $factor Zooming factor, example: a float value of 0.5 is two zoom out in half whereas a value of 2 is zooming the page twice it's original.
		 */
		public function set_zoom($factor=null){
			$this->zoom=$factor;
		}
		/**
		 * Return image with various options.
		 * @param integer $mode Determine how two output (see OUT_* constants).
		 * @param string $file The of this parameter depends on $mode.
		 * @return string|boolean Depending on $mode, this may be success (boolean) or image data.
		 */
		public function output($mode=self::OUT_DOWNLOAD,$file=null){
			if($file===null)$file='output.pdf';
			switch($mode){
				case self::OUT_DOWNLOAD:
					if(!@headers_sent()){
						ob_end_clean();
						header('Content-Description: File Transfer');
						header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
						header('Pragma: public');
						header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
						header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
						// force download dialog
						header('Content-Type: application/force-download');
						header('Content-Type: application/octet-stream', false);
						header('Content-Type: application/download', false);
						header('Content-Type: application/pdf', false);
						// use the Content-Disposition header to supply a recommended filename
						header('Content-Disposition: attachment; filename="'.basename($file).'";');
						header('Content-Transfer-Encoding: binary');
						header('Content-Length: '.strlen($this->result['stdout']));
						echo $this->result['stdout'];
						return true;
					}else{
						trigger_error('WKHTMLTOX error: HTTP headers were already sent.');
						return false;
					}
				case self::OUT_RETURN:
					return $this->result['stdout']===null ? false : $this->result['stdout'];
				case self::OUT_EMBED:
					if(!@headers_sent()){
						header('Content-Type: application/pdf');
						header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
						header('Pragma: public');
						header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
						header('Last-Modified: '.@gmdate('D, d M Y H:i:s').' GMT');
						header('Content-Length: '.strlen($this->result['stdout']));
						header('Content-Disposition: inline; filename="'.basename($file).'";');
						echo $this->result['stdout'];
						return true;
					}else{
						trigger_error('WKHTMLTOX error: HTTP headers were already sent.');
						return false;
					}
				case self::OUT_SAVE:
					return file_put_contents($file,$this->result['stdout']);
				default:
					trigger_error('WKHTMLTOX error: unsupported or unrecognized $mode value passed to output().');
					return false;
			}
		}
	}

?>