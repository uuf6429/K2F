<?php defined('K2F') or die;

	/**
	 * A simple error capturing and handling class.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 01/06/2010
	 */

	/**
	 * Run-time notices. Enable this to receive warnings about code
	 * that will not work in future versions.
	 * @link http://php.net/manual/en/errorfunc.constants.php
	 */
	defined('E_DEPRECATED') or define('E_DEPRECATED',8192);
	/**
	 * User-generated warning message. This is like an
	 * E_DEPRECATED, except it is generated in PHP code by
	 * using the PHP function trigger_error.
	 * @link http://php.net/manual/en/errorfunc.constants.php
	 */
	defined('E_USER_DEPRECATED') or define('E_USER_DEPRECATED',16384);

	class Errors {
		/**
		 * @var array List of error codes which are considered non-fatal.
		 */
		public static $NON_FATAL_CODES=array(
			E_WARNING,E_NOTICE,E_CORE_WARNING,E_COMPILE_WARNING,E_USER_WARNING,E_USER_NOTICE,E_DEPRECATED,E_USER_DEPRECATED
		);
		/**
		 * @var boolean Whether errors are being shown or not.
		 */
		public static $SHOWING_ERRORS=false;
		/**
		 * Write the nice & friendly Debug Mode warning
		 */
		public static function write_debug_warning(){
			if(CFG::get('DEBUG_MODE')!='none' && CFG::get('DEBUG_WARN') && !Ajax::is_on()){
				$ie6h=(stripos(str_replace(' ','',$_SERVER['HTTP_USER_AGENT']),'MSIE6')!==false); // ie6 hotfix
				?><div style="-moz-border-radius:8px; -webkit-border-radius:8px; border-radius:8px;
					-moz-box-shadow:0 0 8px #000; -webkit-box-shadow:0 0 8px #000; box-shadow:0 0 8px #000;
					background:#FEE; border:2px solid #F00; font-family:arial; font-size:12px; font-weight:bold;
					margin:2px; padding:0 4px; position:fixed; right:4px; text-shadow:0 0 10px #A00; z-index:10;
					top:4px; position:<?php echo $ie6h?'absolute':'fixed'; ?>;">DEBUG MODE</div><?php
			}
		}
		/**
		 * Access Error output function. Internal use only.
		 * @param string $header The header to output on fatal errors (eg; "HTTP/1.0 500 Internal Server Error")
		 * @param string $error The kind of message in short (eg; "Server DB error")
		 * @param string $description More details on the error (eg; "A server error occured, database credentials are incorrect.")
		 */
		private static function show_error($errno,$errstr,$errfile,$errline){
			$is_fatal=!in_array($errno,self::$NON_FATAL_CODES);
			// disable any stray errors
			self::hide_errors();
			// clear anything buffered
			if($is_fatal)if(count(ob_list_handlers())>0)ob_end_clean();
			// write error header if possible
			if($is_fatal && !headers_sent())header('HTTP/1.0 500 Internal Server Error');
			// output error
			xlog(($is_fatal ? 'Error' : 'Warning').':',$errno,strip_tags($errstr),$errfile,$errline,debug_backtrace(true));
			// enable error tracking to override PHP's
			self::show_errors();
			// stop destructors from happening
			if($is_fatal)throw new Exception('HiddenDummyException');
			// exit, with fatal error
			if($is_fatal)die(1);
		}
		/**
		 * PHP error handler. Private use only.
		 * @param string $errno Error code.
		 * @param string $errstr Error message.
		 * @param string $errfile Error file.
		 * @param string $errline Error line.
		 * @return boolean Handling status.
		 * @internal
		 */
		public static function handle_errors($errno,$errstr,$errfile,$errline){
			// handle error message
			self::show_error($errno,$errstr,$errfile,$errline);
			// return handled status
			return true;
		}
		/**
		 * PHP exception handler. Private use only.
		 * @param exception $exception The exception object.
		 * @return boolean Handling status.
		 * @internal
		 */
		public static function handle_exceptions($exception){
			// handle error message
			if($exception->getMessage()!='HiddenDummyException')
				self::show_error($exception->getCode(),$exception->getMessage(),$exception->getFile(),$exception->getLine());
			// return handled status
			return true;
		}
		/**
		 * Starts error handling and reporting.
		 */
		public static function show_errors(){
			self::$SHOWING_ERRORS=true;
			set_error_handler(array('Errors','handle_errors'),E_ALL | E_STRICT);
			set_exception_handler(array('Errors','handle_exceptions'));
		}
		/**
		 * Stops error handling and reporting.
		 */
		public static function hide_errors(){
			self::$SHOWING_ERRORS=false;
			restore_error_handler();
			restore_exception_handler();
		}
		/**
		 * Cause a fatal system fault error. Use with freaken care!
		 * @param integer $code The error code (number).
		 * @param string $message Some text describing the error.
		 * @param string $file Error where file was caused.
		 * @param integer $line Line of code where error was caused.
		 */
		public static function throw_error($code=-1,$message='[unknown error]',$file='[unknown]',$line='[unknown]'){
			self::handle_errors($code,$message,$file,$line);
		}
		/**
		 * Cause a fatal system fault exception. Use with freaken care!
		 * @param object $exception The exception object.
		 */
		public static function throw_exception($exception=null){
			self::handle_exceptions($exception);
		}
		/**
		 * @var integer Number of errors since last error trap was set.
		 */
		protected static $trappederrors=0;
		/**
		 * @var unknown Holds state of error handling before trapping started.
		 */
		protected static $trapprevhndlr=null;
		/**
		 * Function used to handle errors and work as a trap.
		 * @return integer Number of errors since last trap was set.
		 * @internal
		 */
		public static function handle_trap(){
			if(!func_num_args()){				// new trap; return error count and reset variables
				$buffer=self::$trappederrors;
				self::$trappederrors=0;
				return $buffer;
			} else self::$trappederrors++;		// return current trap errors
		}
		/**
		 * Start trapping errors; any errors thrown will not be shown.
		 */
		public static function trap_errors_begin(){
			self::$trapprevhndlr=set_error_handler(array('Errors','handle_trap'));
		}
		/**
		 * Stop trapping errors; state is restored to previous state.
		 * @return integer Number of errors cought since trapping was started.
		 */
		public static function trap_errors_end(){
			restore_error_handler(self::$trapprevhndlr);
			return self::handle_trap();
		}
	}

	// hanlde critical errors...bad hack :/
	if(!function_exists('k2f_error_handler')){
		function k2f_error_handler($output){
			if(Errors::$SHOWING_ERRORS){
				$error=error_get_last();
				if(($error!==null) && ($error['message']!='HiddenDummyError')){
					$is_fatal=!in_array($errno,Errors::$NON_FATAL_CODES);
					$output.=xlogr(($is_fatal ? 'Error' : 'Warning').':',$error['type'],strip_tags($error['message']),$error['file'],$error['line'],debug_backtrace(true));
					@trigger_error('HiddenDummyError');
				}
			}
			return $output;
		}
	}
	ob_start('k2f_error_handler');

	// intialize errors system
	Errors::show_errors();
	Errors::write_debug_warning();

?>